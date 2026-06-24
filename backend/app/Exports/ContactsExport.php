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
            $contact->name,
            $contact->phone,
            $contact->email ?? '',
            $contact->country ?? '',
            $contact->language ?? '',
            $contact->status ?? '',
            $contact->source ?? '',
            $contact->opted_in ? 'Yes' : 'No',
            $contact->tags->pluck('name')->implode(', '),
            $contact->notes ?? '',
            $contact->last_visit?->format('Y-m-d') ?? '',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
