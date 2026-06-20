<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $emailSubject,
        public readonly string $bodyHtml,
        public readonly ?int $agencyId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        $primaryColor = '#10B981';
        if ($this->agencyId) {
            $agency = \App\Infrastructure\Persistence\Models\Agency::find($this->agencyId);
            if ($agency && $agency->primary_color) {
                $primaryColor = $agency->primary_color;
            }
        }

        return new Content(view: 'emails.generic-template', with: [
            'bodyHtml' => $this->bodyHtml,
            'primaryColor' => $primaryColor,
        ]);
    }
}
