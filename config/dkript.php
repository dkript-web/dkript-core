<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dkript Core — Nombre de la Plataforma
    |--------------------------------------------------------------------------
    |
    | Define el nombre predeterminado de la plataforma cuando no existe una
    | configuración personalizada en la base de datos (tabla parameters).
    |
    */

    'name' => env('APP_NAME', 'Dkript Core'),

    /*
    |--------------------------------------------------------------------------
    | Dkript Core — Edición y Versión del Framework
    |--------------------------------------------------------------------------
    |
    | Identificadores de versión y edición de la infraestructura base.
    |
    */

    'edition' => 'Core',
    'version' => '1.0.0-dev',

    /*
    |--------------------------------------------------------------------------
    | Dkript Core — Matriz de Branding y Personalización Predeterminada (Neutro)
    |--------------------------------------------------------------------------
    |
    | Valores de respaldo neutrales del Core cuando no se encuentran definidos
    | en la base de datos (tabla parameters) ni en variables de entorno.
    | Las instalaciones limpias del Core NO asumen identidad comercial Dkript.
    |
    */

    'branding' => [
        'default_system_name' => 'Dkript Core',
        'default_logo' => null,
        'default_icon' => null,
        'default_contact_email' => null,
        'default_company_name' => null,
        'show_brand_text' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dkript Preset — Identidad Corporativa Oficial Dkript
    |--------------------------------------------------------------------------
    |
    | Valores aplicados al ejecutar BrandingService::applyDkriptPreset() o al
    | inicializar la Demo oficial.
    |
    | Nota de Arquitectura: 'icon' y 'company_name' son valores gestionados a
    | nivel de configuración/preset. El modelo Parameter y la tabla 'parameters'
    | no poseen columnas persistentes para estos dos campos; por tanto, no se
    | persisten en BD, sino que BrandingService los resuelve dinámicamente
    | desde esta configuración cuando el preset se encuentra activo.
    |
    */

    'preset' => [
        'system_name' => 'Dkript Enterprise',
        'system_logo' => 'assets/images/branding/logo-dkript.png',
        'icon' => 'assets/images/branding/icon-dkript.png',
        'contact_email' => 'soporte@dkript.com',
        'company_name' => 'Dkript Inc.',
        'mail_from_name' => 'Dkript Enterprise',
        'mail_from_address' => 'soporte@dkript.com',
        'show_brand_text' => true,
        'error_pages' => [
            '403' => [
                'title' => 'Barrera de Seguridad Activa (403)',
                'message' => 'Tu cuenta o rol de usuario actual no cuenta con las credenciales requeridas para acceder a este módulo.',
                'badge' => 'ERROR 403 · BARRERA DE SEGURIDAD ACTIVA',
                'video' => 'assets/images/animations/mp4/drypt-403-shield.mp4',
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
            '404' => [
                'title' => 'Dimensión No Encontrada (404)',
                'message' => 'Drypt ha desplegado sus radares y barrido los registros de la infraestructura, pero la ruta solicitada no existe, ha colapsado o fue transferida a otra dimensión del sistema.',
                'badge' => 'ERROR 404 · RUTA NO LOCALIZADA',
                'video' => 'assets/images/animations/mp4/drypt-404-scanning.mp4',
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
            '419' => [
                'title' => 'Desfase Temporal de Sesión (419)',
                'message' => 'La clave de autenticación ha caducado por inactividad. Es necesario actualizar o reiniciar la sesión.',
                'badge' => 'ERROR 419 · TOKEN CSRF EXPIRADO',
                'video' => 'assets/images/animations/mp4/drypt-419-conjuring.mp4',
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
            '429' => [
                'title' => 'Saturación de Frecuencia (429)',
                'message' => 'Drypt ha detectado una sobrecarga de solicitudes. El núcleo ha activado una pausa de seguridad temporal.',
                'badge' => 'ERROR 429 · SATURACIÓN DE FRECUENCIA',
                'video' => null,
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
            '500' => [
                'title' => 'Sobrecarga Crítica de Servidor (500)',
                'message' => 'Se ha generado un fallo inesperado en las matrices internas del núcleo. Los ingenieros han sido notificados.',
                'badge' => 'ERROR 500 · SOBRECARGA EN EL NÚCLEO',
                'video' => 'assets/images/animations/mp4/drypt-500-overload.mp4',
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
            '503' => [
                'title' => 'Calibración & Optimización (503)',
                'message' => 'Drypt se encuentra aplicando mejoras y actualizaciones en la infraestructura central. Volveremos enseguida.',
                'badge' => 'ESTADO 503 · MANTENIMIENTO DEL SISTEMA',
                'video' => 'assets/images/animations/mp4/drypt-503-stasis.mp4',
                'image' => 'assets/images/branding/drypt-oficial.png',
            ],
        ],
    ],

];

