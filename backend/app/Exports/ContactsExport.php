<?php

namespace App\Exports;

use App\Models\Contact;
use App\Models\Segment;
use App\Services\SegmentService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private string $filter = 'all',
        private ?int $segmentId = null,
        private ?int $tagId = null,
    ) {}

    public function query()
    {
        $query = Contact::with('tags');

        if ($this->filter === 'opted_in') {
            $query->where('opted_in', true);
        } elseif ($this->filter === 'segment' && $this->segmentId) {
            $segment = Segment::find($this->segmentId);
            if ($segment) {
                $service = app(SegmentService::class);
                $ids = $service->getEligibleContactsQuery($segment)->pluck('id');
                $query->whereIn('id', $ids);
            }
        } elseif ($this->filter === 'tag' && $this->tagId) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $this->tagId));
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name', 'Phone', 'Email', 'Opted In', 'Notes', 'Last Visit', 'Created At'];
    }

    public function map($contact): array
    {
        return [
            $contact->name,
            $contact->phone,
            $contact->email ?? '',
            $contact->opted_in ? 'Yes' : 'No',
            $contact->notes ?? '',
            $contact->last_visit?->format('Y-m-d') ?? '',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
