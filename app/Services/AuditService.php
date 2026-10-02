<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Claves sensibles que deben redactarse antes de persistir en auditoría o logs.
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'mail_password',
        'twilio_token',
        'twilio_auth_token',
        'whatsapp_access_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'access_token',
        'refresh_token',
        'secret',
        'token',
        'api_key',
        'apiKey',
        'auth_token',
        'recovery_code',
        'recovery_codes',
        'otp',
    ];

    /**
     * Registra un evento de auditoría en la base de datos y en el log de Laravel.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): ?AuditLog {
        try {
            $actor = $user ?? auth()->user();
            $request = request();

            $sanitizedOldValues = self::sanitizePayload($oldValues);
            $sanitizedNewValues = self::sanitizePayload($newValues);

            $log = AuditLog::create([
                'user_id' => $actor?->id,
                'user_name' => $actor?->name ?? 'Invitado / Sistema',
                'user_email' => $actor?->email,
                'action' => strtoupper($action),
                'module' => strtoupper($module),
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'old_values' => $sanitizedOldValues,
                'new_values' => $sanitizedNewValues,
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
            ]);

            Log::info("AUDIT_{$module}_{$action}", [
                'user_id' => $actor?->id,
                'description' => $description,
                'ip' => $request?->ip(),
            ]);

            return $log;
        } catch (\Throwable $e) {
            Log::error("AUDIT_LOG_FAILURE: No se pudo registrar auditoría: " . $e->getMessage(), [
                'action' => $action,
                'module' => $module,
                'description' => $description,
            ]);

            return null;
        }
    }

    /**
     * Sanitiza de manera recursiva un payload de valores,
     * reemplazando cualquier clave sensible por '[REDACTED]'.
     */
    public static function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (self::isSensitiveKey((string)$key)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Determina si una clave coincide o contiene un patrón sensible.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(trim($key));

        foreach (self::$sensitiveKeys as $sensitive) {
            if ($normalized === strtolower($sensitive)) {
                return true;
            }
        }

        if (str_contains($normalized, 'password') ||
            str_contains($normalized, 'secret') ||
            str_contains($normalized, 'token') ||
            str_contains($normalized, 'recovery_code')) {
            return true;
        }

        return false;
    }
}
