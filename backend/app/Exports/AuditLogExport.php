<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }
        if (!empty($this->filters['action'])) {
            $query->where('action', 'like', '%' . $this->filters['action'] . '%');
        }
        if (!empty($this->filters['entity_type'])) {
            $query->where('entity_type', $this->filters['entity_type']);
        }
        if (!empty($this->filters['from'])) {
            $query->whereDate('created_at', '>=', $this->filters['from']);
        }
        if (!empty($this->filters['to'])) {
            $query->whereDate('created_at', '<=', $this->filters['to']);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'Old Values', 'New Values'];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user?->name ?? 'System',
            $log->action,
            $log->entity_type ?? '—',
            $log->entity_id ?? '—',
            $log->ip_address ?? '—',
            $log->old_values ? json_encode($log->old_values) : '—',
            $log->new_values ? json_encode($log->new_values) : '—',
        ];
    }
}
