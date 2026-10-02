<?php

namespace App\Services\Messaging;

use App\Models\Parameter;

class MessagingService
{
    /**
     * Resolves and returns the configured messaging driver.
     */
    public static function getDriver(): MessagingInterface
    {
        $settings = Parameter::getSystemSettings();
        $provider = strtolower($settings->sms_provider ?? 'log');

        if ($provider === 'twilio') {
            return new TwilioDriver(
                $settings->twilio_account_sid,
                $settings->twilio_auth_token,
                $settings->twilio_phone_number,
                $settings->twilio_whatsapp_number
            );
        }

        if (in_array($provider, ['meta_whatsapp', 'whatsapp_cloud'])) {
            return new WhatsAppCloudDriver(
                $settings->whatsapp_phone_number_id,
                $settings->whatsapp_access_token,
                $settings->whatsapp_business_account_id,
                $settings->whatsapp_api_version ?: 'v20.0'
            );
        }

        return new LogSimulatorDriver();
    }

    /**
     * Sends OTP using the active driver.
     *
     * @param string $to
     * @param string $code
     * @param string $channel
     * @return array
     */
    public static function sendOtp(string $to, string $code, string $channel = 'sms'): array
    {
        return static::getDriver()->sendOtp($to, $code, $channel);
    }

    /**
     * Prueba la conexión y despacho de WhatsApp enviando un mensaje diagnóstico.
     *
     * @param string $to
     * @param string|null $message
     * @return array
     */
    public static function testWhatsAppConnection(string $to, ?string $message = null): array
    {
        $settings = Parameter::getSystemSettings();
        $provider = strtolower($settings->sms_provider ?? 'log');
        $systemName = \App\Services\BrandingService::name();
        $msg = $message ?: "🧪 Mensaje de diagnóstico {$systemName}: La conexión con el servicio de WhatsApp es exitosa.\n\n• Sistema: {$systemName}\n• Fecha: " . date('Y-m-d H:i:s');

        if (in_array($provider, ['meta_whatsapp', 'whatsapp_cloud'])) {
            $driver = new WhatsAppCloudDriver(
                $settings->whatsapp_phone_number_id,
                $settings->whatsapp_access_token,
                $settings->whatsapp_business_account_id,
                $settings->whatsapp_api_version ?: 'v20.0'
            );
            return $driver->sendMessage($to, $msg);
        }

        if ($provider === 'twilio') {
            $driver = new TwilioDriver(
                $settings->twilio_account_sid,
                $settings->twilio_auth_token,
                $settings->twilio_phone_number,
                $settings->twilio_whatsapp_number
            );
            return $driver->sendOtp($to, 'TEST-OK', 'whatsapp');
        }

        // Log Simulator
        \Illuminate\Support\Facades\Log::info("WHATSAPP_TEST_SIMULATOR_DISPATCH", [
            'to' => $to,
            'message' => $msg,
            'timestamp' => now()->toIso8601String(),
        ]);

        return [
            'success' => true,
            'provider' => 'log',
            'message_id' => 'sim_wa_' . uniqid(),
            'message' => 'Simulación exitosa: El mensaje de diagnóstico de WhatsApp ha sido registrado en los logs del sistema.',
        ];
    }

    /**
     * Returns true if running in local simulator mode.
     */
    public static function isSimulator(): bool
    {
        $settings = Parameter::getSystemSettings();
        return in_array(strtolower($settings->sms_provider ?? 'log'), ['log', 'simulator']);
    }
}