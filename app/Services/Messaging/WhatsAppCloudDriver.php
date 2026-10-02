<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudDriver implements MessagingInterface
{
    protected ?string $phoneNumberId;
    protected ?string $accessToken;
    protected ?string $businessAccountId;
    protected string $apiVersion;

    public function __construct(
        ?string $phoneNumberId = null,
        ?string $accessToken = null,
        ?string $businessAccountId = null,
        string $apiVersion = 'v20.0'
    ) {
        $this->phoneNumberId = $phoneNumberId ? trim($phoneNumberId) : null;
        $this->accessToken = $accessToken ? trim($accessToken) : null;
        $this->businessAccountId = $businessAccountId ? trim($businessAccountId) : null;
        $this->apiVersion = $apiVersion ? trim($apiVersion) : 'v20.0';
    }

    /**
     * Envía un código OTP mediante la API Oficial de WhatsApp Cloud (Meta Graph API)
     *
     * @param string $to Número telefónico destino (con código de país)
     * @param string $code Código numérico de verificación de 6 dígitos
     * @param string $channel Canal de entrega ('whatsapp')
     * @return array
     */
    public function sendOtp(string $to, string $code, string $channel = 'whatsapp'): array
    {
        $message = "🔒 Tu código de verificación de seguridad Dkript es: *{$code}*.\n\nEste código es confidencial y vence en 10 minutos. No lo compartas con nadie.";

        return $this->sendMessage($to, $message, $code);
    }

    /**
     * Envía un mensaje de texto plano o de diagnóstico a un destinatario
     *
     * @param string $to
     * @param string $bodyText
     * @param string|null $code
     * @return array
     */
    public function sendMessage(string $to, string $bodyText, ?string $code = null): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $to);

        if (empty($this->phoneNumberId) || empty($this->accessToken)) {
            Log::warning('WHATSAPP_CLOUD_CONFIG_MISSING', [
                'has_phone_id' => !empty($this->phoneNumberId),
                'has_token' => !empty($this->accessToken),
            ]);

            return [
                'success' => false,
                'provider' => 'meta_whatsapp',
                'message_id' => null,
                'code' => $code,
                'message' => 'Credenciales de Meta WhatsApp Cloud API incompletas (requiere Phone Number ID y Access Token).',
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $bodyText,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['messages'][0]['id'] ?? null;

                Log::info('WHATSAPP_CLOUD_SENT', [
                    'to' => $cleanPhone,
                    'message_id' => $messageId,
                ]);

                return [
                    'success' => true,
                    'provider' => 'meta_whatsapp',
                    'message_id' => $messageId,
                    'code' => $code,
                    'message' => 'Mensaje de WhatsApp enviado exitosamente vía Meta Cloud API.',
                    'details' => $data,
                ];
            }

            $errorMessage = $response->json('error.message') 
                ?: ($response->json('error.error_user_msg') ?: $response->body());

            Log::error('WHATSAPP_CLOUD_FAILED', [
                'to' => $cleanPhone,
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'provider' => 'meta_whatsapp',
                'message_id' => null,
                'code' => $code,
                'message' => 'Fallo al despachar mensaje de WhatsApp: ' . $errorMessage,
                'status_code' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('WHATSAPP_CLOUD_EXCEPTION', [
                'to' => $cleanPhone,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'provider' => 'meta_whatsapp',
                'message_id' => null,
                'code' => $code,
                'message' => 'Excepción de comunicación con Meta WhatsApp Cloud API: ' . $e->getMessage(),
            ];
        }
    }
}
