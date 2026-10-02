<?php

namespace App\Services\Messaging;

interface MessagingInterface
{
    /**
     * Send OTP verification code to a phone number.
     *
     * @param string $to Phone number in E.164 format or standard format
     * @param string $code 6-digit verification code
     * @param string $channel 'sms' or 'whatsapp'
     * @return array ['success' => bool, 'provider' => string, 'message_id' => ?string, 'code' => ?string, 'message' => string]
     */
    public function sendOtp(string $to, string $code, string $channel = 'sms'): array;
}