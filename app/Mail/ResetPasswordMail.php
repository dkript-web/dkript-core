<?php

namespace App\Mail;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl,
        public string $token,
        public ?Parameter $settings = null
    ) {
        $this->settings = $settings ?: Parameter::getSystemSettings();
    }

    public function envelope(): Envelope
    {
        $systemName = !empty($this->settings->system_name) ? $this->settings->system_name : \App\Services\BrandingService::name();
        return new Envelope(
            subject: "Recuperación de Contraseña — {$systemName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }
}