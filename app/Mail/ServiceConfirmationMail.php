<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailSubject;

    public function __construct(
        public Guest $guest,
        public string $eventName,
        public string $serviceType,
        string $subject,
        public array $details,
    ) {
        $this->mailSubject = $subject;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-confirmation',
            with: [
                'guestName'   => $this->guest->name,
                'eventName'   => $this->eventName,
                'serviceType' => $this->serviceType,
                'details'     => $this->details,
            ],
        );
    }
}
