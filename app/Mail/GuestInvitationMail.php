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
    private function buildMergeData(): array
    {
        return [
            'guest_name'     => $this->guest->name,
            'guest_title'    => $this->guest->title ?? '',
            'guest_email'    => $this->guest->email ?? '',
            'event_name'     => $this->eventName,
            'event_location' => $this->venueName,
            'tier_name'      => $this->guest->tierInfo?->name ?? '',
            'rsvp_url'       => route('rsvp.show', ['token' => $this->invitation->rsvp_token]),
        ];
    }

    private function replaceTags(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace(
                ['{{' . $key . '}}', '{{ ' . $key . ' }}', '{' . $key . '}'],
                $value ?? '',
                $text
            );
        }
        return $text;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replaceTags($this->invitation->subject, $this->buildMergeData()),
        );
    }

    public function content(): Content
    {
        $mergeData = $this->buildMergeData();
        $body = $this->replaceTags($this->invitation->body, $mergeData);

        return new Content(
            view: 'emails.guest-invitation',
            with: [
                'guestName' => $this->guest->name,
                'emailBody' => $body,
                'matches' => $this->matches,
                'eventName' => $this->eventName,
                'venueName' => $this->venueName,
                'rsvpUrl' => $mergeData['rsvp_url'],
            ],
        );
    }
}
