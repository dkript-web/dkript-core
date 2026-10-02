<?php

namespace App\Mail;

use App\Models\Parameter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?Parameter $settings = null
    ) {
        $this->settings = $settings ?: Parameter::getSystemSettings();
    }

    public function envelope(): Envelope
    {
        $systemName = !empty($this->settings->system_name) ? $this->settings->system_name : \App\Services\BrandingService::name();
        return new Envelope(
            subject: "Prueba de Conexión SMTP Exitosa — {$systemName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-smtp',
        );
    }
}