<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Parameter;
use App\Services\TwoFactorAuthService;
use App\Services\AuditService;

class TwoFactorAuthController extends Controller
{
    /**
     * Muestra la pantalla de desafío 2FA para usuarios en proceso de login
     */
    public function showChallenge(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));
        if (!$user || !$user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login');
        }

        // Enmascaramiento de correo para privacidad
        $parts = explode('@', $user->email);
        $namePart = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedName = strlen($namePart) > 3 ? substr($namePart, 0, 2) . str_repeat('*', strlen($namePart) - 3) . substr($namePart, -1) : substr($namePart, 0, 1) . '**';
        $maskedEmail = "{$maskedName}@{$domain}";

        return view('auth.two-factor-challenge', [
            'maskedEmail' => $maskedEmail,
            'user' => $user,
        ]);
    }

    /**
     * Valida el código TOTP o código de recuperación en el inicio de sesión
     */
    public function verifyChallenge(Request $request)
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('login.id'));
        if (!$user || !$user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login');
        }

        $throttleKey = '2fa|' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('AUTH_2FA_THROTTLED', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'seconds' => $seconds,
            ]);

            return back()->withErrors([
                'code' => "Demasiados intentos fallidos de autenticación en 2 pasos. Por seguridad, intente en {$seconds} segundos.",
            ]);
        }

        $isRecovery = $request->filled('recovery_code');

        if ($isRecovery) {
            $request->validate([
                'recovery_code' => ['required', 'string'],
            ]);
            $valid = TwoFactorAuthService::verifyAndConsumeRecoveryCode($user, $request->input('recovery_code'));
        } else {
            $request->validate([
                'code' => ['required', 'string'],
            ]);
            $valid = TwoFactorAuthService::verifyKey((string)$user->two_factor_secret, $request->input('code'));
        }

        if (!$valid) {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('AUTH_2FA_INVALID_ATTEMPT', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'is_recovery' => $isRecovery,
            ]);

            $errorField = $isRecovery ? 'recovery_code' : 'code';
            $errorMsg = $isRecovery 
                ? 'El código de recuperación ingresado no es válido o ya fue utilizado.' 
                : 'El código de verificación de 6 dígitos es incorrecto o ha caducado.';

            return back()->withErrors([$errorField => $errorMsg]);
        }

        RateLimiter::clear($throttleKey);

        $remember = $request->session()->get('login.remember', false);
        Auth::login($user, $remember);
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        Log::info('AUTH_2FA_SUCCESS', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'is_recovery' => $isRecovery,
        ]);

        AuditService::log(
            'LOGIN',
            '2FA',
            $isRecovery ? 'Inicio de sesión con código de recuperación 2FA' : 'Inicio de sesión completado con verificación 2FA exitosa',
            null,
            null,
            $user
        );

        AuthController::establishUserSession($user);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Inicia la configuración de 2FA generando el secreto y los códigos de recuperación
     */
    public function enable(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'status' => 'error',
                'message' => 'La autenticación de dos factores ya se encuentra activa en su cuenta.',
            ], 400);
        }

        $secret = TwoFactorAuthService::generateSecretKey(32);
        $recoveryCodes = TwoFactorAuthService::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null,
        ])->save();

        $systemSettings = Parameter::getSystemSettings();
        $appName = $systemSettings->system_name ?? \App\Services\BrandingService::name();

        $otpAuthUri = TwoFactorAuthService::getOtpAuthUri($appName, $user->email, $secret);
        $qrCodeUrl = TwoFactorAuthService::getQrCodeImageUrl($otpAuthUri, 200);

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de 2FA inicializada.',
            'secret' => $secret,
            'formatted_secret' => trim(chunk_split($secret, 4, ' ')),
            'qr_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Confirma la activación de 2FA mediante la validación del primer token OTP
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay una configuración 2FA pendiente de confirmación.',
            ], 400);
        }

        if (!TwoFactorAuthService::verifyKey((string)$user->two_factor_secret, $request->input('code'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código de verificación ingresado no es válido o ha expirado.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        AuditService::log('SECURITY', '2FA', 'Autenticación de dos factores (2FA TOTP) activada y confirmada con éxito');

        return response()->json([
            'status' => 'success',
            'message' => '¡Autenticación de dos factores activada exitosamente!',
            'recovery_codes' => $user->two_factor_recovery_codes ?? [],
        ]);
    }

    /**
     * Desactiva la autenticación de dos factores requiriendo la contraseña actual
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña actual ingresada es incorrecta.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditService::log('SECURITY', '2FA', 'Autenticación de dos factores (2FA TOTP) desactivada');

        return response()->json([
            'status' => 'success',
            'message' => 'La autenticación de dos factores ha sido desactivada.',
        ]);
    }

    /**
     * Obtiene los códigos de recuperación vigentes
     */
    public function getRecoveryCodes(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return response()->json([
                'status' => 'error',
                'message' => 'La autenticación de dos factores no está activa.',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'recovery_codes' => $user->two_factor_recovery_codes ?? [],
        ]);
    }

    /**
     * Regenera un nuevo lote de 8 códigos de recuperación
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña ingresada es incorrecta.',
            ], 422);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return response()->json([
                'status' => 'error',
                'message' => 'La autenticación de dos factores no está activa.',
            ], 400);
        }

        $newCodes = TwoFactorAuthService::generateRecoveryCodes(8);
        $user->forceFill([
            'two_factor_recovery_codes' => $newCodes,
        ])->save();

        AuditService::log('SECURITY', '2FA', 'Códigos de recuperación 2FA regenerados con éxito');

        return response()->json([
            'status' => 'success',
            'message' => 'Nuevos códigos de recuperación generados exitosamente.',
            'recovery_codes' => $newCodes,
        ]);
    }
}
