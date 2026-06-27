<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'type',
        'name',
        'subject',
        'body',
        'cc',
        'bcc',
        'is_default',
        'enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'enabled'    => 'boolean',
    ];

    const TYPES = [
        'invitation'             => 'Invitation',
        'confirmation'           => 'Invitation Confirmed',
        'portal_access'          => 'Portal Access',
        'service_review'         => 'Service Review (Pending)',
        'flight_confirmed'       => 'Flight Confirmed',
        'accommodation_confirmed'=> 'Accommodation Confirmed',
        'transport_confirmed'    => 'Transport Confirmed',
    ];

    const MERGE_TAGS = [
        'guest_name', 'guest_title', 'guest_email',
        'event_name', 'event_date', 'event_location',
        'tier_name', 'match_list', 'portal_url',
        'service_type', 'service_details', 'rsvp_url',
    ];

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type)->where('enabled', true);
    }

    public static function resolve(string $type): ?self
    {
        return static::ofType($type)->orderByDesc('is_default')->first();
    }

    public function renderSubject(array $data): string
    {
        return $this->replaceTags($this->subject, $data);
    }

    public function renderBody(array $data): string
    {
        return $this->replaceTags($this->body, $data);
    }

    public function ccArray(): array
    {
        return $this->parseEmails($this->cc);
    }

    public function bccArray(): array
    {
        return $this->parseEmails($this->bcc);
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

    private function parseEmails(?string $value): array
    {
        if (!$value) return [];
        return array_filter(array_map('trim', explode(',', $value)));
    }
}
