<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Schema;

class BrandingService
{
    /**
     * Resuelve los parámetros del sistema de forma segura.
     */
    public static function getSettings(): Parameter
    {
        return Parameter::getSystemSettings();
    }

    /**
     * Resuelve el nombre del sistema respetando la precedencia de 3 niveles:
     * 1. Parameter configurable de la base de datos.
     * 2. config('app.name') / config('dkript.name').
     * 3. Fallback estático "Dkript Core".
     */
    public static function name(): string
    {
        $settings = static::getSettings();
        if (!empty($settings->system_name)) {
            return $settings->system_name;
        }

        $appName = config('app.name');
        if (!empty($appName) && $appName !== 'Laravel') {
            return $appName;
        }

        return config('dkript.branding.default_system_name', 'Dkript Core');
    }

    /**
     * Resuelve la ruta relativa del logotipo del sistema.
     * Retorna null si no se ha configurado ningún logo en BD ni en config.
     */
    public static function logo(): ?string
    {
        $settings = static::getSettings();
        if (!empty($settings->system_logo)) {
            return $settings->system_logo;
        }

        return config('dkript.branding.default_logo', null);
    }

    /**
     * Retorna la URL pública completa del logotipo, o null si no existe.
     * Previene generar asset('') o URLs inválidas ante ausencia de logo.
     */
    public static function logoUrl(): ?string
    {
        $logo = static::logo();
        return !empty($logo) ? asset($logo) : null;
    }

    /**
     * Determina si el sistema posee un logotipo gráfico configurado.
     */
    public static function hasLogo(): bool
    {
        return !empty(static::logo());
    }

    /**
     * Determina si la aplicación está operando bajo la identidad comercial o preset de Dkript.
     */
    public static function isDkriptBranded(): bool
    {
        $settings = static::getSettings();
        $presetLogo = config('dkript.preset.system_logo', 'assets/images/branding/logo-dkript.png');
        $presetName = config('dkript.preset.system_name', 'Dkript Enterprise');

        if (!empty($settings->system_logo) && $settings->system_logo === $presetLogo) {
            return true;
        }

        if (!empty($settings->system_name) && $settings->system_name === $presetName) {
            return true;
        }

        return false;
    }

    /**
     * Resuelve el icono corporativo del sistema (isotipo).
     * Retorna null si no se ha configurado ningún icono.
     * 
     * Nota de Arquitectura: La tabla 'parameters' no posee una columna 'icon' persistente.
     * Si la aplicación opera bajo el preset Dkript, se resuelve desde config('dkript.preset.icon').
     * En un Core neutro, se resuelve config('dkript.branding.default_icon', null).
     */
    public static function icon(): ?string
    {
        $configured = config('dkript.branding.default_icon', null);
        if (!empty($configured)) {
            return $configured;
        }

        if (static::isDkriptBranded()) {
            return config('dkript.preset.icon', 'assets/images/branding/icon-dkript.png');
        }

        $settings = static::getSettings();
        if (!empty($settings->system_logo)) {
            return $settings->system_logo;
        }

        return null;
    }

    /**
     * Retorna la URL pública completa del icono, o null si no existe.
     */
    public static function iconUrl(): ?string
    {
        $icon = static::icon();
        return !empty($icon) ? asset($icon) : null;
    }

    /**
     * Determina si el sistema posee un icono gráfico configurado.
     */
    public static function hasIcon(): bool
    {
        return !empty(static::icon());
    }

    /**
     * Genera las iniciales textuales neutrales del nombre del sistema para fallbacks visuales.
     */
    public static function initials(): string
    {
        $name = static::name();
        $words = preg_split('/\s+/', trim($name));
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }
        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    /**
     * Resuelve el correo de contacto institucional.
     * Retorna null si no se encuentra definido.
     */
    public static function contactEmail(): ?string
    {
        $settings = static::getSettings();
        if (!empty($settings->contact_email)) {
            return $settings->contact_email;
        }

        return config('dkript.branding.default_contact_email', null);
    }

    /**
     * Resuelve el nombre de la empresa titular o propietaria.
     * 
     * Nota de Arquitectura: La tabla 'parameters' no posee una columna 'company_name' persistente.
     * Si la aplicación opera bajo el preset Dkript, se resuelve desde config('dkript.preset.company_name').
     * En un Core neutro, se resuelve config('dkript.branding.default_company_name', null).
     */
    public static function companyName(): ?string
    {
        $configured = config('dkript.branding.default_company_name', null);
        if (!empty($configured)) {
            return $configured;
        }

        if (static::isDkriptBranded()) {
            return config('dkript.preset.company_name', 'Dkript Inc.');
        }

        return null;
    }

    /**
     * Indica si el nombre textual debe mostrarse junto al logotipo.
     */
    public static function showBrandText(): bool
    {
        $settings = static::getSettings();
        return (bool) ($settings->show_brand_text ?? true);
    }

    /**
     * Resuelve la edición del sistema ("Core").
     */
    public static function edition(): string
    {
        return config('dkript.edition', 'Core');
    }

    /**
     * Resuelve la versión de la plataforma.
     */
    public static function version(): string
    {
        return config('dkript.version', '1.0.0-dev');
    }

    /**
     * Aplica el Preset Oficial Dkript de forma 100% idempotente sobre los parámetros del sistema.
     * Inyecta explícitamente el nombre, logotipo, correo y remitente comercial de Dkript
     * en los campos persistentes de la tabla 'parameters' (system_name, system_logo,
     * contact_email, mail_from_name, mail_from_address, show_brand_text).
     * 
     * Nota de Arquitectura: 'icon' y 'company_name' no poseen columnas en la tabla
     * 'parameters'; son valores del preset gestionados a nivel de configuración.
     */
    public static function applyDkriptPreset(): Parameter
    {
        $parameter = Parameter::first();
        if (!$parameter) {
            $parameter = new Parameter();
            $parameter->id = 1;
        }

        $preset = config('dkript.preset', [
            'system_name' => 'Dkript Enterprise',
            'system_logo' => 'assets/images/branding/logo-dkript.png',
            'contact_email' => 'soporte@dkript.com',
            'company_name' => 'Dkript Inc.',
            'mail_from_name' => 'Dkript Enterprise',
            'mail_from_address' => 'soporte@dkript.com',
            'show_brand_text' => true,
        ]);

        $parameter->system_name = $preset['system_name'] ?? 'Dkript Enterprise';
        $parameter->system_logo = $preset['system_logo'] ?? 'assets/images/branding/logo-dkript.png';
        $parameter->show_brand_text = $preset['show_brand_text'] ?? true;
        $parameter->contact_email = $preset['contact_email'] ?? 'soporte@dkript.com';
        $parameter->mail_from_name = $preset['mail_from_name'] ?? 'Dkript Enterprise';
        $parameter->mail_from_address = $preset['mail_from_address'] ?? 'soporte@dkript.com';
        $parameter->save();

        return $parameter;
    }

    /**
     * Retorna los fallbacks neutrales del Core para cada código de error.
     * Estos defaults no contienen video ni imagen dependiente de marca,
     * garantizando un despliegue ligero, robusto y 100% independiente.
     */
    public static function defaultErrorPages(): array
    {
        return [
            '403' => [
                'code' => '403',
                'title' => 'Acceso No Autorizado',
                'message' => 'No dispones de las credenciales o permisos requeridos para acceder a este recurso del sistema.',
                'badge' => 'ERROR 403 · ACCESO DENEGADO',
                'video' => null,
                'image' => null,
            ],
            '404' => [
                'code' => '404',
                'title' => 'Página No Encontrada',
                'message' => 'El recurso o enlace solicitado no existe en la infraestructura o fue reubicado.',
                'badge' => 'ERROR 404 · RUTA INEXISTENTE',
                'video' => null,
                'image' => null,
            ],
            '419' => [
                'code' => '419',
                'title' => 'Sesión Expirada',
                'message' => 'Tu clave de autenticación o sesión temporal ha caducado por inactividad. Por favor, recarga la página o inicia sesión nuevamente.',
                'badge' => 'ERROR 419 · TOKEN EXPIRADO',
                'video' => null,
                'image' => null,
            ],
            '429' => [
                'code' => '429',
                'title' => 'Demasiadas Solicitudes',
                'message' => 'Has superado el límite de peticiones permitidas en este lapso temporal. Por favor, espera unos instantes antes de reintentar.',
                'badge' => 'ERROR 429 · LÍMITE DE VELOCIDAD',
                'video' => null,
                'image' => null,
            ],
            '500' => [
                'code' => '500',
                'title' => 'Error del Servidor',
                'message' => 'Se ha presentado un fallo inesperado al procesar la solicitud en el servidor. Los administradores han sido notificados.',
                'badge' => 'ERROR 500 · ANOMALÍA INTERNA',
                'video' => null,
                'image' => null,
            ],
            '503' => [
                'code' => '503',
                'title' => 'Servicio No Disponible',
                'message' => 'La plataforma se encuentra temporalmente fuera de servicio por labores de mantenimiento o calibración.',
                'badge' => 'ESTADO 503 · MANTENIMIENTO',
                'video' => null,
                'image' => null,
            ],
        ];
    }

    /**
     * Resuelve de forma segura y defensiva la configuración visual y textual de una página de error.
     * Precedencia estricta de 3 niveles:
     * 1. Parameter persistido en BD (columna JSON 'error_pages' -> [$code]).
     * 2. Preset / Demo Dkript (si opera bajo marca Dkript o en config 'dkript.preset.error_pages').
     * 3. Fallback estático y neutro del Core (sin dependencias multimedia obligatorias).
     * 
     * Verificación física de archivos:
     * Comprueba si el archivo físico referenciado existe en public_path().
     * Si no existe, lo desactiva para permitir la cascada: Video -> Imagen -> Vector/CSS neutro.
     */
    public static function errorPage(string $code): array
    {
        $code = (string) $code;
        $dbConfig = null;

        // Manejo defensivo absoluto contra caídas de base de datos o migraciones
        try {
            $settings = static::getSettings();
            if ($settings && is_array($settings->error_pages) && isset($settings->error_pages[$code])) {
                $dbConfig = $settings->error_pages[$code];
            }
        } catch (\Throwable $e) {
            $dbConfig = null;
        }

        $defaults = static::defaultErrorPages()[$code] ?? [
            'code' => $code,
            'title' => "Error {$code}",
            'message' => 'Ha ocurrido un error en la solicitud.',
            'badge' => "ERROR {$code}",
            'video' => null,
            'image' => null,
        ];

        $preset = config("dkript.preset.error_pages.{$code}", []);

        $isBranded = false;
        try {
            $isBranded = static::isDkriptBranded();
        } catch (\Throwable $e) {
            $isBranded = false;
        }

        // Resolución de textos (DB -> Preset si Branded -> Core Defaults)
        $title = $dbConfig['title'] ?? ($isBranded ? ($preset['title'] ?? null) : null) ?? $defaults['title'];
        $message = $dbConfig['message'] ?? ($isBranded ? ($preset['message'] ?? null) : null) ?? $defaults['message'];
        $badge = $dbConfig['badge'] ?? ($isBranded ? ($preset['badge'] ?? null) : null) ?? $defaults['badge'];

        // Resolución de candidatos multimedia
        $candidateVideo = null;
        if (is_array($dbConfig) && array_key_exists('video', $dbConfig)) {
            $candidateVideo = $dbConfig['video'];
        } elseif ($isBranded && !empty($preset['video'])) {
            $candidateVideo = $preset['video'];
        }

        $candidateImage = null;
        if (is_array($dbConfig) && array_key_exists('image', $dbConfig)) {
            $candidateImage = $dbConfig['image'];
        } elseif ($isBranded && !empty($preset['image'])) {
            $candidateImage = $preset['image'];
        }

        // Verificación física de existencia en disco
        $video = null;
        $hasVideo = false;
        if (!empty($candidateVideo)) {
            $vPath = public_path($candidateVideo);
            if (@file_exists($vPath) && !is_dir($vPath)) {
                $video = $candidateVideo;
                $hasVideo = true;
            }
        }

        $image = null;
        $hasImage = false;
        if (!empty($candidateImage)) {
            $iPath = public_path($candidateImage);
            if (@file_exists($iPath) && !is_dir($iPath)) {
                $image = $candidateImage;
                $hasImage = true;
            }
        }

        return [
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'badge' => $badge,
            'video' => $video,
            'video_url' => $hasVideo ? asset($video) : null,
            'has_video' => $hasVideo,
            'image' => $image,
            'image_url' => $hasImage ? asset($image) : null,
            'has_image' => $hasImage,
            'has_media' => ($hasVideo || $hasImage),
        ];
    }

    /**
     * Retorna la configuración completa de todas las páginas de error soportadas.
     */
    public static function errorPages(): array
    {
        $codes = ['403', '404', '419', '429', '500', '503'];
        $pages = [];
        foreach ($codes as $code) {
            $pages[$code] = static::errorPage($code);
        }
        return $pages;
    }
}

