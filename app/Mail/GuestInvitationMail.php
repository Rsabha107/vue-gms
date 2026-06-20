<?php

namespace App\Mail;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Guest $guest;
    public Invitation $invitation;
    public array $matches;
    public string $eventName;
    public string $venueName;

    /**
     * Create a new message instance.
     */
    public function __construct(Guest $guest, Invitation $invitation, array $matches, string $eventName, string $venueName)
    {
        $this->guest = $guest;
        $this->invitation = $invitation;
        $this->matches = $matches;
        $this->eventName = $eventName;
        $this->venueName = $venueName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invitation->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-invitation',
            with: [
                'guestName' => $this->guest->name,
                'emailBody' => $this->invitation->body,
                'matches' => $this->matches,
                'eventName' => $this->eventName,
                'venueName' => $this->venueName,
                'rsvpUrl' => route('rsvp.show', ['token' => $this->invitation->rsvp_token]),
            ],
        );
    }
}
