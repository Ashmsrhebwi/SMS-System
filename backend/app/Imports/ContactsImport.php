<?php

namespace App\Imports;

use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\OptOut;
use App\Services\ActivityLogger;
use App\Services\CountryDetectorService;
use App\Services\PhoneNormalizerService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToCollection, WithHeadingRow
{
    public int   $imported   = 0;
    public int   $updated    = 0;
    public array $duplicates = [];
    public array $errors     = [];

    private PhoneNormalizerService $normalizer;
    private array $processedPhones = []; // track phones added in this import batch

    public function __construct(private string $duplicateAction = 'skip')
    {
        $this->normalizer = new PhoneNormalizerService();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum   = $index + 2;
            $name     = trim($row['full_name'] ?? $row['name'] ?? '');
            $rawPhone = trim($row['phone_number'] ?? $row['phone'] ?? $row['mobile'] ?? '');
            // Auto-prepend + if the number doesn't already start with it
            if ($rawPhone !== '' && !str_starts_with($rawPhone, '+')) {
                $rawPhone = '+' . $rawPhone;
            }
            $email    = trim($row['email'] ?? '');
            $notes    = trim($row['notes'] ?? '');
            $language = trim($row['language'] ?? '');
            $status   = trim($row['status'] ?? 'active');
            $source   = trim($row['source'] ?? 'import');

            // Validation: missing required fields
            if (empty($name) || empty($rawPhone)) {
                $reason = match(true) {
                    empty($name) && empty($rawPhone) => 'Missing Full Name and Phone Number',
                    empty($name)                     => 'Missing Full Name',
                    default                          => 'Missing Phone Number',
                };
                $this->errors[] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'phone'  => $rawPhone,
                    'email'  => $email ?: null,
                    'reason' => $reason,
                ];
                continue;
            }

            // Validation: phone format
            $normalizedPhone = $this->normalizer->normalize($rawPhone);
            if (!$normalizedPhone) {
                $this->errors[] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'phone'  => $rawPhone,
                    'email'  => $email ?: null,
                    'reason' => "Invalid phone number format: \"{$rawPhone}\"",
                ];
                continue;
            }

            // Blacklist / opt-out check
            $isBlacklisted = GlobalBlacklist::where('phone', $normalizedPhone)->exists()
                          || OptOut::where('phone', $normalizedPhone)->exists();
            if ($isBlacklisted) {
                $this->errors[] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'phone'  => $normalizedPhone,
                    'email'  => $email ?: null,
                    'reason' => 'Phone is on the opt-out / blacklist',
                ];
                continue;
            }

            // Duplicate detection — check in-memory first (within-file duplicates), then DB
            if (isset($this->processedPhones[$normalizedPhone])) {
                $this->duplicates[] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'phone'  => $normalizedPhone,
                    'email'  => $email ?: null,
                    'reason' => 'Duplicate phone number within the import file',
                ];
                continue;
            }

            $existing = Contact::where('phone', $normalizedPhone)->first();
            if (!$existing && $email) {
                $existing = Contact::where('email', $email)->first();
            }

            if ($existing) {
                if ($this->duplicateAction === 'update') {
                    $lastVisit = null;
                    if (!empty($row['last_visit'])) {
                        try { $lastVisit = \Carbon\Carbon::parse($row['last_visit'])->format('Y-m-d'); } catch (\Exception) {}
                    }
                    $validStatuses = ['active', 'inactive', 'interested', 'follow_up', 'not_interested'];
                    $existing->update([
                        'name'       => $name,
                        'email'      => $email ?: $existing->email,
                        'notes'      => $notes ?: $existing->notes,
                        'last_visit' => $lastVisit ?? $existing->getRawOriginal('last_visit'),
                        'language'   => $language ?: $existing->language,
                        'status'     => in_array($status, $validStatuses) ? $status : $existing->status,
                        'source'     => $source ?: $existing->source,
                    ]);
                    ActivityLogger::contactUpdated($existing);
                    $this->processedPhones[$normalizedPhone] = true;
                    $this->updated++;
                } else {
                    $this->duplicates[] = [
                        'row'    => $rowNum,
                        'name'   => $name,
                        'phone'  => $normalizedPhone,
                        'email'  => $email ?: null,
                        'reason' => 'Phone number already exists in the system',
                    ];
                }
                continue;
            }

            // Opted-in status
            $rawOptIn = $row['opted_in'] ?? $row['opt_in'] ?? null;
            $optedIn  = ($rawOptIn !== null && $rawOptIn !== '')
                ? in_array(strtolower((string) $rawOptIn), ['1', 'true', 'yes'], true)
                : true;

            $validStatuses = ['active', 'inactive', 'interested', 'follow_up', 'not_interested'];
            $contactStatus = in_array($status, $validStatuses) ? $status : 'active';

            try {
                $contact = Contact::create([
                    'name'     => $name,
                    'phone'    => $normalizedPhone,
                    'email'    => $email ?: null,
                    'opted_in' => $optedIn,
                    'notes'    => $notes ?: null,
                    'country'  => CountryDetectorService::detect($normalizedPhone),
                    'language' => $language ?: null,
                    'status'   => $contactStatus,
                    'source'   => 'import',
                ]);
                $this->processedPhones[$normalizedPhone] = true;
                ActivityLogger::contactImported($contact);
                $this->imported++;
            } catch (\Illuminate\Database\QueryException $e) {
                // Unique constraint violation — treat as duplicate
                $this->duplicates[] = [
                    'row'    => $rowNum,
                    'name'   => $name,
                    'phone'  => $normalizedPhone,
                    'email'  => $email ?: null,
                    'reason' => 'Phone number already exists in the system',
                ];
            }
        }
    }
}
