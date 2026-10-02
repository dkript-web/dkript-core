<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envía una notificación directa a un usuario específico.
     */
    public static function send(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionUrl = null,
        array $extra = []
    ): void {
        try {
            $user->notify(new SystemNotification($title, $message, $type, $actionUrl, $extra));
        } catch (\Throwable $e) {
            Log::error("NOTIFICATION_ERROR: Fallo al notificar a usuario [{$user->id}]: " . $e->getMessage());
        }
    }

    /**
     * Emite una notificación a todos los usuarios con rol de Super Administrador (role_id = 1).
     */
    public static function broadcastToAdmins(
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionUrl = null,
        array $extra = []
    ): void {
        $admins = User::whereHas('profile', function ($q) {
            $q->where('role_id', 1);
        })->get();

        foreach ($admins as $admin) {
            static::send($admin, $title, $message, $type, $actionUrl, $extra);
        }
    }

    /**
     * Emite una notificación a todos los usuarios de un rol específico.
     */
    public static function sendToRole(
        int $roleId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionUrl = null,
        array $extra = []
    ): void {
        $users = User::whereHas('profile', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        })->get();

        foreach ($users as $user) {
            static::send($user, $title, $message, $type, $actionUrl, $extra);
        }
    }
}