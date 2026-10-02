<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioDriver implements MessagingInterface
{
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $fromSms;
    protected ?string $fromWhatsapp;

    public function __construct(
        ?string $accountSid = null,
        ?string $authToken = null,
        ?string $fromSms = null,
        ?string $fromWhatsapp = null
    ) {
        $this->accountSid = $accountSid ?: config('services.twilio.sid');
        $this->authToken = $authToken ?: config('services.twilio.token');
        $this->fromSms = $fromSms ?: config('services.twilio.from');
        $this->fromWhatsapp = $fromWhatsapp ?: config('services.twilio.whatsapp_from');
    }

    public function sendOtp(string $to, string $code, string $channel = 'sms'): array
    {
        if (empty($this->accountSid) || empty($this->authToken)) {
            Log::error('TWILIO_ERROR: Credenciales de Twilio no configuradas (Account SID o Auth Token vacíos).');
            return [
                'success' => false,
                'provider' => 'twilio',
                'channel' => $channel,
                'to' => $to,
                'code' => null,
                'message_id' => null,
                'message' => 'Credenciales del proveedor Twilio no configuradas en el sistema.',
            ];
        }

        $messageBody = "Tu código de verificación de Dkript Inc. es: {$code}. Válido por 10 minutos.";
        $isWhatsapp = ($channel === 'whatsapp');

        $from = $isWhatsapp ? $this->fromWhatsapp : $this->fromSms;
        if (empty($from)) {
            $fromName = $isWhatsapp ? 'WhatsApp' : 'SMS';
            Log::error("TWILIO_ERROR: Número de origen de Twilio para {$fromName} no configurado.");
            return [
                'success' => false,
                'provider' => 'twilio',
                'channel' => $channel,
                'to' => $to,
                'code' => null,
                'message_id' => null,
                'message' => "Número emisor de Twilio para {$fromName} no configurado.",
            ];
        }

        $fromFormatted = $isWhatsapp ? (str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:' . $from) : $from;
        $toFormatted = $isWhatsapp ? (str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to) : $to;

        try {
            $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->timeout(15)
                ->post($endpoint, [
                    'From' => $fromFormatted,
                    'To' => $toFormatted,
                    'Body' => $messageBody,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("TWILIO_SUCCESS: Sent [{$channel}] to {$to}, SID=" . ($data['sid'] ?? 'N/A'));

                return [
                    'success' => true,
                    'provider' => 'twilio',
                    'channel' => $channel,
                    'to' => $to,
                    'code' => null,
                    'message_id' => $data['sid'] ?? null,
                    'message' => 'Código de verificación enviado exitosamente.',
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Error desconocido al contactar Twilio API.';
            Log::error("TWILIO_API_ERROR: HTTP {$response->status()} - {$errorMessage}");

            return [
                'success' => false,
                'provider' => 'twilio',
                'channel' => $channel,
                'to' => $to,
                'code' => null,
                'message_id' => null,
                'message' => "Error del servicio de mensajería: {$errorMessage}",
            ];
        } catch (\Throwable $e) {
            Log::error("TWILIO_EXCEPTION: " . $e->getMessage());

            return [
                'success' => false,
                'provider' => 'twilio',
                'channel' => $channel,
                'to' => $to,
                'code' => null,
                'message_id' => null,
                'message' => 'Ocurrió un error inesperado al intentar enviar el mensaje: ' . $e->getMessage(),
            ];
        }
    }
}