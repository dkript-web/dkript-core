<?php

namespace App\Services;

use App\Mail\TestSmtpMail;
use App\Models\Parameter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailConfigService
{
    /**
     * Aplica dinámicamente la configuración de correo en tiempo de ejecución.
     */
    public static function applyConfig(?Parameter $parameter = null): void
    {
        $settings = $parameter ?: Parameter::getSystemSettings();
        $mailer = strtolower($settings->mail_mailer ?: 'log');

        config(['mail.default' => $mailer]);

        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $settings->mail_host ?: '127.0.0.1',
                'mail.mailers.smtp.port' => (int)($settings->mail_port ?: 587),
                'mail.mailers.smtp.username' => $settings->mail_username,
                'mail.mailers.smtp.password' => $settings->mail_password,
                'mail.mailers.smtp.encryption' => ($settings->mail_encryption === 'none' ? null : ($settings->mail_encryption ?: 'tls')),
            ]);
        }

        $fromAddress = $settings->mail_from_address ?: config('mail.from.address', 'hello@example.com');
        $fromName = $settings->mail_from_name ?: \App\Services\BrandingService::name();

        config([
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);
    }

    /**
     * Prueba la conexión del servidor de correo enviando un mensaje de verificación.
     */
    public static function testConnection(string $toEmail, ?Parameter $parameter = null): array
    {
        static::applyConfig($parameter);
        $settings = $parameter ?: Parameter::getSystemSettings();
        $mailer = strtolower($settings->mail_mailer ?: 'log');

        if ($mailer === 'log') {
            Log::info("MAIL_SIMULATOR_TEST: Correo de prueba simulado para [{$toEmail}]");
            return [
                'success' => true,
                'mailer' => 'log',
                'message' => "Servicio en modo Log Simulator (Desarrollo). El correo de prueba fue registrado en los logs del sistema para {$toEmail}.",
            ];
        }

        try {
            Mail::to($toEmail)->send(new TestSmtpMail($settings));

            Log::info("SMTP_TEST_SUCCESS: Correo de prueba enviado exitosamente a [{$toEmail}] vía {$settings->mail_host}:{$settings->mail_port}");

            return [
                'success' => true,
                'mailer' => 'smtp',
                'message' => "¡Conexión exitosa! El servidor SMTP ({$settings->mail_host}:{$settings->mail_port}) entregó el correo de prueba a {$toEmail}.",
            ];
        } catch (\Throwable $e) {
            Log::error("SMTP_TEST_FAILED: Fallo de conexión con servidor de correo para [{$toEmail}]. Error: " . $e->getMessage());

            return [
                'success' => false,
                'mailer' => 'smtp',
                'message' => "Fallo de conexión SMTP ({$settings->mail_host}:{$settings->mail_port}): " . $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna si el servicio de correo actual se encuentra en modo log/simulador.
     */
    public static function isLog(): bool
    {
        $settings = Parameter::getSystemSettings();
        return strtolower($settings->mail_mailer ?? 'log') !== 'smtp';
    }
}