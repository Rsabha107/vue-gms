<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PortalAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public Guest $guest;
    public string $portalUrl;
    public array $event;
    public int $hoursValid;
    public string $expiresAt;

    /**
     * Create a new message instance.
     */
    public function __construct(Guest $guest, string $portalUrl, array $event, int $hoursValid = 72)
    {
        $this->guest = $guest;
        $this->portalUrl = $portalUrl;
        $this->event = $event;
        $this->hoursValid = $hoursValid;
        $this->expiresAt = Carbon::now()->addHours($hoursValid)->format('l, F j, Y \a\t g:i A');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ' . ($this->event['name'] ?? 'Event') . ' Guest Portal Access',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-access',
            with: [
                'guestName' => $this->guest->name,
                'guestTitle' => $this->guest->title,
                'portalUrl' => $this->portalUrl,
                'eventName' => $this->event['name'] ?? 'Event',
                'eventSubtitle' => $this->event['subtitle'] ?? '',
                'eventLocation' => $this->event['location'] ?? '',
                'eventDates' => $this->event['formatted_dates'] ?? '',
                'hoursValid' => $this->hoursValid,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
