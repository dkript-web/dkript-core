<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',
        public ?string $actionUrl = null,
        public array $extra = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'icon' => $this->resolveIcon($this->type),
            'action_url' => $this->actionUrl,
            'extra' => $this->extra,
        ];
    }

    protected function resolveIcon(string $type): string
    {
        return match (strtolower($type)) {
            'security', 'warning' => 'bi-shield-fill-exclamation',
            'system' => 'bi-gear-wide-connected',
            'backup' => 'bi-cloud-arrow-down-fill',
            'user' => 'bi-person-badge-fill',
            'success' => 'bi-check-circle-fill',
            'error' => 'bi-exclamation-triangle-fill',
            default => 'bi-bell-fill',
        };
    }
}