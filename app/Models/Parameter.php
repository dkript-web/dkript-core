<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_name',
        'system_logo',
        'show_brand_text',
        'contact_email',
        'records_per_page',
        'session_timeout_minutes',
        'maintenance_mode',
        'modal_style',
        'error_display_mode',
        'sms_provider',
        'twilio_account_sid',
        'twilio_auth_token',
        'twilio_phone_number',
        'twilio_whatsapp_number',
        'whatsapp_phone_number_id',
        'whatsapp_access_token',
        'whatsapp_business_account_id',
        'whatsapp_api_version',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'auto_backup_enabled',
        'auto_backup_frequency',
        'auto_backup_time',
        'auto_backup_type',
        'auto_backup_max_retention',
        'auto_backup_last_run_at',
        'installed_at',
        'error_pages',
    ];

    protected $hidden = [
        'mail_password',
        'twilio_auth_token',
        'whatsapp_access_token',
    ];

    protected function casts(): array
    {
        return [
            'show_brand_text' => 'boolean',
            'records_per_page' => 'integer',
            'session_timeout_minutes' => 'integer',
            'maintenance_mode' => 'integer',
            'mail_port' => 'integer',
            'auto_backup_enabled' => 'boolean',
            'auto_backup_max_retention' => 'integer',
            'auto_backup_last_run_at' => 'datetime',
            'installed_at' => 'datetime',
            'error_pages' => 'array',
            'mail_password' => 'encrypted',
            'twilio_auth_token' => 'encrypted',
            'whatsapp_access_token' => 'encrypted',
        ];
    }

    /**
     * Obtiene la configuración de una página de error específica.
     */
    public function getErrorPageConfig(string $code): ?array
    {
        $pages = $this->error_pages ?? [];
        return $pages[$code] ?? null;
    }

    /**
     * Actualiza o define la configuración de una página de error específica.
     */
    public function setErrorPageConfig(string $code, array $config): void
    {
        $pages = $this->error_pages ?? [];
        $pages[$code] = array_merge($pages[$code] ?? [], $config);
        $this->error_pages = $pages;
    }

    public static function getSystemSettings()
    {
        $defaultName = (config('app.name') && config('app.name') !== 'Laravel')
            ? config('app.name')
            : config('dkript.branding.default_system_name', 'Dkript Core');

        return static::first() ?: new static([
            'system_name' => $defaultName,
            'system_logo' => config('dkript.branding.default_logo', null),
            'show_brand_text' => config('dkript.branding.show_brand_text', true),
            'contact_email' => config('dkript.branding.default_contact_email', null),
            'records_per_page' => 10,
            'session_timeout_minutes' => 15,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'error_display_mode' => 'scene',
            'sms_provider' => 'log',
            'twilio_account_sid' => null,
            'twilio_auth_token' => null,
            'twilio_phone_number' => null,
            'twilio_whatsapp_number' => null,
            'whatsapp_phone_number_id' => null,
            'whatsapp_access_token' => null,
            'whatsapp_business_account_id' => null,
            'whatsapp_api_version' => 'v20.0',
            'mail_mailer' => 'log',
            'mail_host' => '127.0.0.1',
            'mail_port' => 587,
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
            'mail_from_address' => config('mail.from.address', 'hello@example.com'),
            'mail_from_name' => config('app.name', 'Dkript Core'),
            'auto_backup_enabled' => false,
            'auto_backup_frequency' => 'daily',
            'auto_backup_time' => '02:00',
            'auto_backup_type' => 'database',
            'auto_backup_max_retention' => 7,
            'auto_backup_last_run_at' => null,
        ]);
    }
}
