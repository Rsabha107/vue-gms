<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Guest $guest,
        public string $eventName,
        public array $matches,
        public ?string $portalUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->eventName} Attendance is Confirmed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-confirmed',
            with: [
                'guestName' => $this->guest->name,
                'eventName' => $this->eventName,
                'matches'   => $this->matches,
                'portalUrl' => $this->portalUrl,
            ],
        );
    }
}
