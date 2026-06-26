<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    private string $renderedSubject;
    private string $renderedBody;
    private string $eventName;
    private array $extraViewData;

    public function __construct(
        private EmailTemplate $template,
        private array $mergeData,
        private string $templateType = 'invitation',
        array $extraViewData = [],
    ) {
        $this->renderedSubject = $template->renderSubject($mergeData);
        $this->renderedBody = $template->renderBody($mergeData);
        $this->eventName = $mergeData['event_name'] ?? 'Event';
        $this->extraViewData = $extraViewData;

        if ($cc = $template->ccArray()) {
            $this->cc($cc);
        }
        if ($bcc = $template->bccArray()) {
            $this->bcc($bcc);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.templated',
            with: array_merge([
                'renderedBody' => $this->renderedBody,
                'eventName'    => $this->eventName,
                'templateType' => $this->templateType,
            ], $this->extraViewData),
        );
    }

    public static function deliver(
        string $type,
        string $toEmail,
        array $mergeData,
        array $extraViewData = [],
    ): bool {
        $template = EmailTemplate::resolve($type);
        if (!$template) return false;

        \Illuminate\Support\Facades\Mail::to($toEmail)->queue(
            new static($template, $mergeData, $type, $extraViewData)
        );

        return true;
    }
}
