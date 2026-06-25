<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $builder) {}

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'Name', 'Phone', 'Email', 'Country', 'Language', 'Status', 'Source',
            'Opted In', 'Tags', 'Notes', 'Last Visit', 'Created At',
        ];
    }

    public function map($contact): array
    {
        return [
            $this->sanitizeCsvCell($contact->name),
            $contact->phone,  // phone numbers are normalized — always start with +
            $this->sanitizeCsvCell($contact->email ?? ''),
            $this->sanitizeCsvCell($contact->country ?? ''),
            $this->sanitizeCsvCell($contact->language ?? ''),
            $this->sanitizeCsvCell($contact->status ?? ''),
            $this->sanitizeCsvCell($contact->source ?? ''),
            $contact->opted_in ? 'Yes' : 'No',
            $this->sanitizeCsvCell($contact->tags->pluck('name')->implode(', ')),
            $this->sanitizeCsvCell($contact->notes ?? ''),
            $contact->last_visit?->format('Y-m-d') ?? '',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Prevent CSV/formula injection: values beginning with =, +, -, @, tab, or CR
     * are prefixed with an apostrophe so spreadsheet apps treat them as text.
     */
    private function sanitizeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'" . $value;
        }
        return $value;
    }
}
