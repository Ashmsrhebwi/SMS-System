<?php

namespace App\Exports;

use App\Models\Campaign;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CampaignReportExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(private Campaign $campaign) {}

    public function collection(): Collection
    {
        return $this->campaign->messages()
            ->with(['contact' => fn($q) => $q->withTrashed(), 'click'])
            ->get()
            ->map(fn($message) => [
                'name' => $message->contact?->name ?? '[Deleted]',
                'phone' => $message->contact?->phone ?? '—',
                'status' => $message->status,
                'error_code' => $message->error_code ?? '-',
                'error_message' => $message->error_message ?? '-',
                'sent_at' => $message->sent_at?->format('Y-m-d H:i:s') ?? '-',
                'delivered_at' => $message->delivered_at?->format('Y-m-d H:i:s') ?? '-',
                'clicked' => $message->click?->click_count > 0 ? 'Yes' : 'No',
                'click_count' => $message->click?->click_count ?? 0,
                'first_clicked_at' => $message->click?->first_clicked_at?->format('Y-m-d H:i:s') ?? '-',
            ]);
    }

    public function headings(): array
    {
        return [
            'Name', 'Phone', 'Status', 'Error Code', 'Error Message',
            'Sent At', 'Delivered At', 'Clicked', 'Click Count', 'First Clicked At',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
