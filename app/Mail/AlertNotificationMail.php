<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public function __construct(public readonly Alert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[NetGuard] %s: %s', $this->alert->severity->label(), $this->alert->title),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.alert-notification');
    }
}
