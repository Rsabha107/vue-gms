<?php

namespace App\Imports;

use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Models\Group;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Validation\Rule;

class GuestsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected $eventId;
    protected $imported = 0;
    protected $skipped = 0;
    protected $nextReferenceNumber;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
        
        // Get the last reference number once at the start
        $lastGuest = Guest::where('event_id', $this->eventId)
            ->orderBy('reference_number', 'desc')
            ->first();
        
        if ($lastGuest && $lastGuest->reference_number) {
            $lastNumber = (int) substr($lastGuest->reference_number, 1);
            $this->nextReferenceNumber = $lastNumber + 1;
        } else {
            $this->nextReferenceNumber = 1;
        }
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Generate reference number and increment counter
        $referenceNumber = 'G' . str_pad($this->nextReferenceNumber, 3, '0', STR_PAD_LEFT);
        $this->nextReferenceNumber++;

        // Parse facilities if provided as comma-separated
        $facilities = [];
        if (!empty($row['facilities'])) {
            $facilities = array_map('trim', explode(',', $row['facilities']));
        }

        // Parse companion list if provided as comma-separated
        $companionList = [];
        if (!empty($row['companion_list'])) {
            $companionList = array_map('trim', explode(',', $row['companion_list']));
        }

        $this->imported++;

        return new Guest([
            'event_id'        => $this->eventId,
            'reference_number' => $referenceNumber,
            'name'            => $row['name'] ?? $row['first_name'] . ' ' . $row['last_name'],
            'firstName'       => $row['first_name'] ?? null,
            'lastName'        => $row['last_name'] ?? null,
            'title'           => $row['title'] ?? null,
            'guestType'       => $row['guest_type'] ?? 'local',
            'qid'             => $row['qid'] ?? null,
            'tier'            => $row['tier'] ?? 'T3',
            'group_id'        => $row['group_id'] ?? null,
            'nationality'     => $row['nationality'] ?? null,
            'status_id'       => $row['status_id'] ?? 'invited',
            'email'           => $row['email'] ?? null,
            'phone'           => $row['phone'] ?? null,
            'host'            => $row['host'] ?? null,
            'hotel'           => $row['hotel'] ?? null,
            'dietaryNotes'    => $row['dietary_notes'] ?? null,
            'notes'           => $row['notes'] ?? null,
            'facilities'      => $facilities,
            'companionList'   => $companionList,
            'companions'      => $row['companions'] ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'first_name' => 'required_without:name|string|max:120',
            'last_name' => 'required_without:name|string|max:120',
            'title' => 'nullable|string|max:255',
            'guest_type' => 'nullable|in:local,international',
            'qid' => 'nullable|string|max:20',
            'tier' => 'nullable|string',
            'group_id' => 'nullable|string',
            'nationality' => 'nullable|string|max:2',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:40',
            'host' => 'nullable|string',
            'hotel' => 'nullable|string',
            'dietary_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'nullable|in:invited,confirmed,pending,declined',
            'companions' => 'nullable|integer|min:0',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'first_name.required_without' => 'Either name or first_name is required',
            'last_name.required_without' => 'Either name or last_name is required',
            'guest_type.in' => 'Guest type must be either local or international',
            'status_id.in' => 'Status must be one of: invited, confirmed, pending, declined',
        ];
    }

    public function getImported()
    {
        return $this->imported;
    }

    public function getSkipped()
    {
        return count($this->failures()) + count($this->errors());
    }
}
