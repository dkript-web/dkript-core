<?php

namespace App\Services;

class DeviceDetectorService
{
    /**
     * Parsea un string User-Agent y retorna metadatos de plataforma, navegador y tipo de dispositivo.
     */
    public static function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'device_type' => 'desktop',
                'device_name' => 'Dispositivo Desconocido',
                'platform' => 'Desconocido',
                'browser' => 'Desconocido',
                'icon' => 'bi-display',
            ];
        }

        $platform = static::detectPlatform($userAgent);
        $browser = static::detectBrowser($userAgent);
        $deviceType = static::detectDeviceType($userAgent);

        $icon = match ($deviceType) {
            'phone' => 'bi-phone',
            'tablet' => 'bi-tablet',
            'robot' => 'bi-robot',
            default => 'bi-laptop',
        };

        return [
            'device_type' => $deviceType,
            'device_name' => "{$platform} • {$browser}",
            'platform' => $platform,
            'browser' => $browser,
            'icon' => $icon,
        ];
    }

    /**
     * Detecta el Sistema Operativo
     */
    protected static function detectPlatform(string $ua): string
    {
        if (stripos($ua, 'Windows NT 10.0') !== false || stripos($ua, 'Windows NT 11.0') !== false) {
            return 'Windows';
        }
        if (stripos($ua, 'Windows') !== false) {
            return 'Windows';
        }
        if (stripos($ua, 'iPhone') !== false) {
            return 'iOS (iPhone)';
        }
        if (stripos($ua, 'iPad') !== false) {
            return 'iPadOS (iPad)';
        }
        if (stripos($ua, 'Android') !== false) {
            return 'Android';
        }
        if (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS X') !== false) {
            return 'macOS';
        }
        if (stripos($ua, 'CrOS') !== false) {
            return 'ChromeOS';
        }
        if (stripos($ua, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Sistema Desconocido';
    }

    /**
     * Detecta el Navegador Web
     */
    protected static function detectBrowser(string $ua): string
    {
        if (stripos($ua, 'Edg') !== false) {
            return 'Microsoft Edge';
        }
        if (stripos($ua, 'OPR') !== false || stripos($ua, 'OPT') !== false || stripos($ua, 'Opera') !== false) {
            return 'Opera';
        }
        if (stripos($ua, 'Brave') !== false) {
            return 'Brave';
        }
        if (stripos($ua, 'Chrome/') !== false) {
            return 'Google Chrome';
        }
        if (stripos($ua, 'Firefox/') !== false) {
            return 'Mozilla Firefox';
        }
        if (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome') === false) {
            return 'Apple Safari';
        }

        return 'Navegador Web';
    }

    /**
     * Detecta el Tipo de Dispositivo (desktop, phone, tablet)
     */
    protected static function detectDeviceType(string $ua): string
    {
        if (stripos($ua, 'iPad') !== false || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
            return 'tablet';
        }
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'iPhone') !== false || stripos($ua, 'Android') !== false) {
            return 'phone';
        }
        if (stripos($ua, 'bot') !== false || stripos($ua, 'crawler') !== false) {
            return 'robot';
        }

        return 'desktop';
    }
}
