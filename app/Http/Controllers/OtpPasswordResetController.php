<?php

namespace App\Http\Controllers;

use App\Models\PhoneVerification;
use App\Models\Profile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Messaging\MessagingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class OtpPasswordResetController extends Controller
{
    /**
     * Muestra la pantalla inicial de recuperación de contraseña.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Genera y envía el código OTP vía SMS o WhatsApp al teléfono del usuario.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:25'],
            'channel' => ['required', 'in:sms,whatsapp'],
        ], [
            'phone.required' => 'Por favor ingresa tu número telefónico registrado.',
            'phone.min' => 'El número telefónico debe tener al menos 7 dígitos.',
            'channel.in' => 'El canal seleccionado no es válido.',
        ]);

        $rawPhone = $request->input('phone');
        $channel = $request->input('channel', 'sms');
        $normalizedPhone = preg_replace('/[^\d+]/', '', $rawPhone);

        // Búsqueda flexible de perfil por número telefónico
        $profile = Profile::where('phone', $normalizedPhone)
            ->orWhere('phone', ltrim($normalizedPhone, '+'))
            ->orWhere('phone', '+' . ltrim($normalizedPhone, '+'))
            ->first();

        if (!$profile || !$profile->user) {
            return back()->withErrors([
                'phone' => 'No encontramos ninguna cuenta asociada al número telefónico ingresado.',
            ])->withInput();
        }

        // Validación de enfriamiento (cooldown de 60 segundos entre envíos)
        $latestOtp = PhoneVerification::where('phone', $normalizedPhone)
            ->where('purpose', 'password_reset')
            ->latest('id')
            ->first();

        if ($latestOtp && $latestOtp->created_at->diffInSeconds(now()) < 60) {
            $secondsRemaining = 60 - $latestOtp->created_at->diffInSeconds(now());
            return back()->withErrors([
                'phone' => "Por favor espera {$secondsRemaining} segundos antes de solicitar otro código.",
            ])->withInput();
        }

        // Generación de código criptográficamente seguro de 6 dígitos
        $otpCode = sprintf('%06d', random_int(100000, 999999));

        // Invalidar códigos anteriores no verificados para este teléfono
        PhoneVerification::where('phone', $normalizedPhone)
            ->where('purpose', 'password_reset')
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);

        // Registrar nuevo registro de verificación
        $verification = PhoneVerification::create([
            'user_id' => $profile->user_id,
            'phone' => $normalizedPhone,
            'otp_code' => $otpCode,
            'channel' => $channel,
            'purpose' => 'password_reset',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Despachar mediante servicio de mensajería (Simulador o Twilio)
        $dispatchResult = MessagingService::sendOtp($normalizedPhone, $otpCode, $channel);

        // Guardar estado en sesión
        session([
            'otp_phone' => $normalizedPhone,
            'otp_channel' => $channel,
            'otp_verification_id' => $verification->id,
            'otp_last_sent_at' => now()->timestamp,
        ]);

        // En modo simulador, facilitar el código en flash para testing ágil sin costos
        if (MessagingService::isSimulator()) {
            session()->flash('simulator_otp_code', $otpCode);
        }

        $channelLabel = ($channel === 'whatsapp') ? 'WhatsApp' : 'SMS';

        return redirect()->route('password.verify-otp')->with(
            'success',
            "Hemos enviado un código de 6 dígitos a tu teléfono vía {$channelLabel}."
        );
    }

    /**
     * Muestra la pantalla para ingresar los 6 dígitos del OTP.
     */
    public function showVerifyOtp()
    {
        $phone = session('otp_phone');
        if (!$phone) {
            return redirect()->route('password.request')->with('info', 'Inicia el proceso ingresando tu número.');
        }

        $channel = session('otp_channel', 'sms');
        $maskedPhone = $this->maskPhoneNumber($phone);
        $simulatorOtp = session('simulator_otp_code');

        return view('auth.verify-otp', compact('phone', 'maskedPhone', 'channel', 'simulatorOtp'));
    }

    /**
     * Valida el código OTP ingresado por el usuario.
     */
    public function verifyOtp(Request $request)
    {
        $phone = session('otp_phone');
        $verificationId = session('otp_verification_id');

        if (!$phone || !$verificationId) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'La sesión de verificación ha expirado. Por favor inicia nuevamente.',
            ]);
        }

        // Permitir recibir el código como string 'code' o como arreglo de dígitos 'digits'
        $code = $request->input('code');
        if (empty($code) && $request->has('digits') && is_array($request->input('digits'))) {
            $code = implode('', $request->input('digits'));
        }

        $code = trim((string)$code);

        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return back()->withErrors([
                'otp_code' => 'Debes ingresar el código completo de 6 dígitos numéricos.',
            ]);
        }

        $verification = PhoneVerification::find($verificationId);

        if (!$verification || $verification->phone !== $phone) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'No se encontró la solicitud de verificación.',
            ]);
        }

        if ($verification->isExpired()) {
            return back()->withErrors([
                'otp_code' => 'El código OTP ha expirado. Solicita un nuevo código.',
            ]);
        }

        if ($verification->hasExceededAttempts(5)) {
            return back()->withErrors([
                'otp_code' => 'Has superado el límite de intentos (5). Solicita un nuevo código.',
            ]);
        }

        $verification->increment('attempts');

        if (!hash_equals((string)$verification->otp_code, $code)) {
            $remaining = max(0, 5 - $verification->attempts);
            return back()->withErrors([
                'otp_code' => "El código ingresado es incorrecto. Intentos restantes: {$remaining}.",
            ]);
        }

        // Código válido: Marcar como verificado
        $verification->update([
            'verified_at' => now(),
        ]);

        $resetToken = Str::random(64);

        session([
            'password_reset_verified' => true,
            'password_reset_user_id' => $verification->user_id,
            'password_reset_token' => $resetToken,
        ]);

        return redirect()->route('password.reset-form')->with(
            'success',
            'Identidad confirmada exitosamente. Establece tu nueva contraseña.'
        );
    }

    /**
     * Reenvía un nuevo código OTP respetando el enfriamiento de 60 segundos.
     */
    public function resendOtp(Request $request)
    {
        $phone = session('otp_phone');
        if (!$phone) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión expirada. Inicia nuevamente.',
                ], 419);
            }
            return redirect()->route('password.request');
        }

        $latestOtp = PhoneVerification::where('phone', $phone)
            ->where('purpose', 'password_reset')
            ->latest('id')
            ->first();

        if ($latestOtp && $latestOtp->created_at->diffInSeconds(now()) < 60) {
            $secondsRemaining = 60 - $latestOtp->created_at->diffInSeconds(now());
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Debes esperar {$secondsRemaining} segundos para reenviar.",
                    'seconds_remaining' => $secondsRemaining,
                ], 429);
            }
            return back()->withErrors([
                'otp_code' => "Debes esperar {$secondsRemaining} segundos para reenviar el código.",
            ]);
        }

        $channel = session('otp_channel', 'sms');
        $otpCode = sprintf('%06d', random_int(100000, 999999));

        $verification = PhoneVerification::create([
            'user_id' => $latestOtp ? $latestOtp->user_id : null,
            'phone' => $phone,
            'otp_code' => $otpCode,
            'channel' => $channel,
            'purpose' => 'password_reset',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        MessagingService::sendOtp($phone, $otpCode, $channel);

        session([
            'otp_verification_id' => $verification->id,
            'otp_last_sent_at' => now()->timestamp,
        ]);

        if (MessagingService::isSimulator()) {
            session()->flash('simulator_otp_code', $otpCode);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Nuevo código enviado exitosamente.',
                'simulator_code' => MessagingService::isSimulator() ? $otpCode : null,
            ]);
        }

        return back()->with('success', 'Hemos enviado un nuevo código a tu teléfono.');
    }

    /**
     * Muestra el formulario para definir la nueva contraseña.
     */
    public function showResetPassword()
    {
        if (!session('password_reset_verified') || !session('password_reset_user_id')) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'Debes verificar tu número antes de restablecer tu contraseña.',
            ]);
        }

        return view('auth.reset-password-otp');
    }

    /**
     * Actualiza la contraseña del usuario en la base de datos.
     */
    public function resetPassword(Request $request)
    {
        if (!session('password_reset_verified') || !session('password_reset_user_id')) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'La sesión de restablecimiento ha expirado.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un carácter especial.',
        ]);

        $user = User::find(session('password_reset_user_id'));

        if (!$user) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'Usuario no encontrado.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $channel = session('otp_channel', 'sms');

        // Registrar evento de seguridad en auditoría
        AuditService::log(
            'PASSWORD_RESET',
            'AUTH',
            'Restablecimiento de contraseña por código OTP exitoso',
            null,
            ['method' => 'OTP', 'channel' => $channel],
            $user
        );

        // Limpiar todas las variables temporales de sesión
        session()->forget([
            'otp_phone',
            'otp_channel',
            'otp_verification_id',
            'otp_last_sent_at',
            'password_reset_verified',
            'password_reset_user_id',
            'password_reset_token',
        ]);

        return redirect()->route('login')->with(
            'success',
            '¡Tu contraseña ha sido actualizada exitosamente! Ya puedes iniciar sesión con tus nuevas credenciales.'
        );
    }

    /**
     * Enmascara el número de teléfono para privacidad en pantalla.
     * Ejemplo: +52 55 •••• 5678
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        $len = strlen($clean);

        if ($len <= 4) {
            return $clean;
        }

        $visibleStart = 3;
        $visibleEnd = 2;

        if ($len <= 8) {
            $visibleStart = 2;
            $visibleEnd = 2;
        }

        $start = substr($clean, 0, $visibleStart);
        $end = substr($clean, -$visibleEnd);
        $hiddenCount = max(3, $len - $visibleStart - $visibleEnd);

        return $start . ' ' . str_repeat('•', min(6, $hiddenCount)) . ' ' . $end;
    }
}