<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    protected static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera una clave secreta aleatoria en Base32 compatible con Google Authenticator
     */
    public static function generateSecretKey(int $length = 32): string
    {
        $secret = '';
        $charsCount = strlen(static::$base32Chars);

        for ($i = 0; $i < $length; $i++) {
            $secret .= static::$base32Chars[random_int(0, $charsCount - 1)];
        }

        return $secret;
    }

    /**
     * Calcula el código TOTP actual para un secreto dado
     */
    public static function getCurrentOtp(string $secret, int $timeSliceOffset = 0): string
    {
        $timeSlice = (int)floor(time() / 30) + $timeSliceOffset;
        $secretBinary = static::base32Decode($secret);

        // Pack 64-bit integer big-endian
        $timeData = pack('N*', 0) . pack('N*', $timeSlice);

        $hash = hash_hmac('sha1', $timeData, $secretBinary, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);

        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica si el código de 6 dígitos ingresado por el usuario es válido
     */
    public static function verifyKey(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        // Verifica la ventana actual y las adyacentes (+/- 30s) para tolerar desvíos de reloj
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedOtp = static::getCurrentOtp($secret, $i);
            if (hash_equals($calculatedOtp, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera la URI otpauth estándar para apps de autenticación
     */
    public static function getOtpAuthUri(string $companyName, string $userEmail, string $secret): string
    {
        $encodedIssuer = rawurlencode($companyName);
        $encodedEmail = rawurlencode($userEmail);

        return "otpauth://totp/{$encodedIssuer}:{$encodedEmail}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Retorna una URL para renderizar el código QR
     */
    public static function getQrCodeImageUrl(string $otpAuthUri, int $size = 200): string
    {
        $encodedData = urlencode($otpAuthUri);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&margin=1&data={$encodedData}";
    }

    /**
     * Genera 8 códigos de recuperación de emergencia únicos
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(Str::random(5));
            $part2 = strtoupper(Str::random(5));
            $codes[] = "{$part1}-{$part2}";
        }

        return $codes;
    }

    /**
     * Valida y consume un código de recuperación (de un solo uso)
     */
    public static function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $rawCodes = $user->two_factor_recovery_codes;
        if (empty($rawCodes)) {
            return false;
        }

        $codes = is_array($rawCodes) ? $rawCodes : json_decode($rawCodes, true);
        if (!is_array($codes)) {
            return false;
        }

        $cleanInputCode = strtoupper(trim(str_replace(' ', '', $code)));

        foreach ($codes as $index => $storedCode) {
            $cleanStoredCode = strtoupper(trim(str_replace(' ', '', $storedCode)));
            if (hash_equals($cleanStoredCode, $cleanInputCode)) {
                // Consumir el código (eliminarlo de la lista)
                unset($codes[$index]);
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();
                return true;
            }
        }

        return false;
    }

    /**
     * Decodifica una cadena Base32 a binario
     */
    protected static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(trim($b32));
        $buffer = 0;
        $bufferBits = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            $pos = strpos(static::$base32Chars, $char);
            if ($pos === false) continue;

            $buffer = ($buffer << 5) | $pos;
            $bufferBits += 5;

            if ($bufferBits >= 8) {
                $bufferBits -= 8;
                $binary .= chr(($buffer >> $bufferBits) & 0xFF);
            }
        }

        return $binary;
    }
}
