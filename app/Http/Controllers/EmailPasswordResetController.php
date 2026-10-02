<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class EmailPasswordResetController extends Controller
{
    /**
     * Envía el enlace de recuperación de contraseña al correo del usuario.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Por favor ingresa tu correo electrónico.',
            'email.email' => 'Debes proporcionar un correo electrónico válido.',
        ]);

        $email = trim(strtolower($request->input('email')));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No encontramos ninguna cuenta asociada a este correo electrónico.',
            ])->withInput();
        }

        // Validación de enfriamiento (cooldown de 60s)
        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($existing && $existing->created_at) {
            $created = Carbon::parse($existing->created_at);
            if ($created->diffInSeconds(now()) < 60) {
                $seconds = 60 - $created->diffInSeconds(now());
                return back()->withErrors([
                    'email' => "Por favor espera {$seconds} segundos antes de solicitar otro enlace.",
                ])->withInput();
            }
        }

        // Generar token seguro
        $rawToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $rawToken,
            'email' => $email,
        ]);

        // Aplicar configuración de correo en tiempo real
        MailConfigService::applyConfig();

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl, $rawToken));
        } catch (\Throwable $e) {
            Log::error("PASSWORD_RESET_MAIL_ERROR: No se pudo enviar el correo a [{$email}]. " . $e->getMessage());
        }

        // Si el mailer está en modo 'log', emitir flash simulator para pruebas inmediatas
        if (MailConfigService::isLog() || config('mail.default') === 'log') {
            session()->flash('simulator_reset_url', $resetUrl);
            Log::info("MAIL_SIMULATOR_PASSWORD_RESET_URL: [{$email}] -> {$resetUrl}");
        }

        return back()->with(
            'success',
            'Hemos enviado un enlace de recuperación a tu correo electrónico. Revisa tu bandeja de entrada.'
        );
    }

    /**
     * Muestra la pantalla para definir la nueva contraseña desde el enlace de correo.
     */
    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'El enlace de recuperación es incompleto o no contiene el correo electrónico.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'El enlace de recuperación ha expirado o ya fue utilizado. Por favor solicita uno nuevo.',
            ]);
        }

        return view('auth.reset-password-email', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Actualiza la contraseña validando el token de correo.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'token.required' => 'El token de verificación es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un carácter especial.',
        ]);

        $email = trim(strtolower($request->input('email')));
        $token = $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors([
                'email' => 'El enlace de recuperación ha expirado o no es válido.',
            ]);
        }

        if (!Hash::check($token, $record->token)) {
            return back()->withErrors([
                'email' => 'El token de seguridad proporcionado no es válido.',
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'No se encontró el usuario asociado a esta solicitud.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Eliminar el token para evitar re-utilización
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Registrar evento de seguridad en auditoría
        AuditService::log(
            'PASSWORD_RESET',
            'AUTH',
            'Restablecimiento de contraseña por correo electrónico exitoso',
            null,
            ['method' => 'EMAIL'],
            $user
        );

        return redirect()->route('login')->with(
            'success',
            '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión con tus nuevas credenciales.'
        );
    }
}