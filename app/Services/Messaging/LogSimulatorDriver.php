<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

class LogSimulatorDriver implements MessagingInterface
{
    public function sendOtp(string $to, string $code, string $channel = 'sms'): array
    {
        $channelUpper = strtoupper($channel);
        $messageBody = "Tu código de verificación de Dkript Inc. es: {$code}. Válido por 10 minutos. No compartas este código con nadie.";

        Log::info("TELEPHONY_SIMULATOR_OTP: [{$channelUpper}] to={$to} code={$code} message=\"{$messageBody}\"");

        return [
            'success' => true,
            'provider' => 'simulator',
            'channel' => $channel,
            'to' => $to,
            'code' => $code,
            'message_id' => 'sim_' . bin2hex(random_bytes(8)),
            'message' => "Código OTP simulado enviado con éxito a {$to} vía {$channelUpper}.",
        ];
    }
}