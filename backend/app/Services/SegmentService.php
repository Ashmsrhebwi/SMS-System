<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SegmentService
{
    public function getEligibleContacts(?Segment $segment): Collection
    {
        return $this->buildQuery($segment)->get();
    }

    public function getEligibleContactsQuery(?Segment $segment): Builder
    {
        return $this->buildQuery($segment);
    }

    public function eachEligibleContact(?Segment $segment, int $chunkSize, callable $callback): void
    {
        $this->buildQuery($segment)->chunk($chunkSize, $callback);
    }

    public function countEligible(?Segment $segment): int
    {
        return $this->buildQuery($segment)->count();
    }

    private function buildQuery(?Segment $segment): Builder
    {
        $query = Contact::where('opted_in', true)
            ->whereNotExists(function ($q) {
                $q->from('opt_outs')
                  ->whereColumn('opt_outs.phone', 'contacts.phone');
            })
            ->whereNotExists(function ($q) {
                $q->from('global_blacklist')
                  ->whereColumn('global_blacklist.phone', 'contacts.phone');
            })
            ->whereNotExists(function ($q) {
                $q->from('suppression_list')
                  ->whereColumn('suppression_list.phone', 'contacts.phone');
            });

        if ($segment) {
            // Pinned-contact segments (created from clicks or smart distribution)
            // take precedence over filter conditions
            if ($segment->contacts()->exists()) {
                $query->whereIn('contacts.id', $segment->contacts()->pluck('contacts.id'));
            } else {
                $conditions = $segment->conditions ?? [];
                $logic      = $segment->logic ?? 'and';
                $this->applyConditions($query, $conditions, $logic);
            }
        }

        return $query;
    }

    private function applyConditions(Builder $query, array $conditions, string $logic = 'and'): void
    {
        if (empty($conditions)) {
            return;
        }

        if ($logic === 'or') {
            // Wrap each condition in its own group, connected by OR
            $query->where(function (Builder $q) use ($conditions) {
                foreach ($conditions as $i => $condition) {
                    $connectMethod = $i === 0 ? 'where' : 'orWhere';
                    $q->$connectMethod(function (Builder $inner) use ($condition) {
                        $this->applyCondition($inner, $condition);
                    });
                }
            });
        } else {
            // AND: all conditions chained inside a single group
            $query->where(function (Builder $q) use ($conditions) {
                foreach ($conditions as $condition) {
                    $this->applyCondition($q, $condition);
                }
            });
        }
    }

    private function applyCondition(Builder $query, array $condition): void
    {
        $field    = $condition['field']    ?? null;
        $operator = $condition['operator'] ?? 'is';
        $value    = $condition['value']    ?? null;

        if (!$field) {
            return;
        }

        match ($field) {
            'name'         => $this->applyStringCondition($query, 'name', $operator, $value),
            'phone'        => $this->applyStringCondition($query, 'phone', $operator, $value),
            'email'        => $this->applyEmailCondition($query, $operator, $value),
            'tag'          => $this->applyTagCondition($query, $operator, $value),
            'opted_in'     => $this->applyOptInCondition($query, $value),
            'country',
            'phone_country' => $this->applyCountryCondition($query, $operator, $value),
            'created_at'   => $this->applyDateCondition($query, 'created_at', $operator, $value),
            'message_count' => $this->applyMessageCountCondition($query, $operator, $value),
            'last_messaged' => $this->applyLastMessagedCondition($query, $operator, $value),
            // Legacy
            'created_before' => $query->whereDate('created_at', '<=', $value),
            'created_after'  => $query->whereDate('created_at', '>=', $value),
            default          => null,
        };
    }

    private function applyStringCondition(Builder $query, string $column, string $operator, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        match ($operator) {
            'contains'      => $query->where($column, 'like', "%{$value}%"),
            'not_contains'  => $query->where($column, 'not like', "%{$value}%"),
            'starts_with'   => $query->where($column, 'like', "{$value}%"),
            'ends_with'     => $query->where($column, 'like', "%{$value}"),
            'is'            => $query->where($column, $value),
            'is_not'        => $query->where($column, '!=', $value),
            default         => $query->where($column, 'like', "%{$value}%"),
        };
    }

    private function applyEmailCondition(Builder $query, string $operator, mixed $value): void
    {
        match ($operator) {
            'has'          => $query->whereNotNull('email')->where('email', '!=', ''),
            'not_has'      => $query->where(fn($q) => $q->whereNull('email')->orWhere('email', '')),
            'contains'     => $query->where('email', 'like', "%{$value}%"),
            'not_contains' => $query->where('email', 'not like', "%{$value}%"),
            'is'           => $query->where('email', $value),
            'is_not'       => $query->where('email', '!=', $value),
            default        => null,
        };
    }

    private function applyTagCondition(Builder $query, string $operator, mixed $value): void
    {
        match ($operator) {
            'has_any'  => $query->whereHas('tags'),
            'has_none' => $query->whereDoesntHave('tags'),
            'has'      => ($value !== null && $value !== '')
                            ? $query->whereHas('tags', fn($q) => $q->where('tags.id', (int) $value))
                            : null,
            'not_has'  => ($value !== null && $value !== '')
                            ? $query->whereDoesntHave('tags', fn($q) => $q->where('tags.id', (int) $value))
                            : null,
            default    => ($value !== null && $value !== '')
                            ? $query->whereHas('tags', fn($q) => $q->where('tags.id', (int) $value))
                            : null,
        };
    }

    private function applyOptInCondition(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $query->where('opted_in', (bool)(int) $value);
    }

    private function applyCountryCondition(Builder $query, string $operator, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        match ($operator) {
            'is_not', 'not_starts_with' => $query->where('country', '!=', $value),
            'contains'                  => $query->where('country', 'like', "%{$value}%"),
            default                     => $query->where('country', $value),
        };
    }

    private function applyDateCondition(Builder $query, string $column, string $operator, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        match ($operator) {
            'before'      => $query->whereDate($column, '<=', $value),
            'after'       => $query->whereDate($column, '>=', $value),
            'within_days' => $query->where($column, '>=', now()->subDays((int) $value)),
            default       => null,
        };
    }

    private function applyMessageCountCondition(Builder $query, string $operator, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $count = (int) $value;

        match ($operator) {
            'eq'  => $query->has('messages', '=', $count),
            'gt'  => $query->has('messages', '>', $count),
            'gte' => $query->has('messages', '>=', $count),
            'lt'  => $query->has('messages', '<', $count),
            'lte' => $query->has('messages', '<=', $count),
            'is'  => $query->has('messages', '=', $count),
            default => null,
        };
    }

    private function applyLastMessagedCondition(Builder $query, string $operator, mixed $value): void
    {
        match ($operator) {
            'never' => $query->whereDoesntHave('messages'),
            'ever'  => $query->whereHas('messages'),
            'before' => $query->whereHas('messages', function ($q) use ($value) {
                $q->where('created_at', '<=', $value)
                  ->where('status', 'delivered');
            }),
            'after' => $query->whereHas('messages', function ($q) use ($value) {
                $q->where('created_at', '>=', $value)
                  ->where('status', 'delivered');
            }),
            'within_days' => $query->whereHas('messages', function ($q) use ($value) {
                $q->where('created_at', '>=', now()->subDays((int) $value));
            }),
            default => null,
        };
    }
}
