<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ContactsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Imports\ContactsImport;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\Message;
use App\Models\SuppressionList;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\CountryDetectorService;
use App\Services\PhoneNormalizerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::with('tags')->latest();

        if ($tagId = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }
        if ($optIn = $request->get('opt_in')) {
            $query->where('opted_in', $optIn === '1');
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Advanced filter conditions from the filter panel
        $filtersRaw = $request->input('filters', []);
        if (is_string($filtersRaw) && $filtersRaw !== '') {
            $filtersRaw = json_decode($filtersRaw, true) ?? [];
        }
        if (is_array($filtersRaw) && !empty($filtersRaw)) {
            $filterLogic = $request->input('filter_logic', 'and');
            $this->applyContactFilters($query, $filtersRaw, $filterLogic);
        }

        $contacts = $query->paginate($request->integer('per_page', 50));

        return ContactResource::collection($contacts)->response();
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        if ($request->filled('phone') && !$request->boolean('force_create')) {
            $normalized = app(PhoneNormalizerService::class)->normalize($request->phone);
            if ($normalized && Contact::where('phone', $normalized)->exists()) {
                return response()->json([
                    'message'    => "Phone number {$normalized} already exists.",
                    'duplicate'  => true,
                    'field'      => 'phone',
                ], 422);
            }
            if ($request->filled('email') && Contact::where('email', $request->email)->exists()) {
                return response()->json([
                    'message'   => "Email {$request->email} is already used by another contact.",
                    'duplicate' => true,
                    'field'     => 'email',
                ], 422);
            }
        }

        $contact = Contact::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in', true),
            'country'  => CountryDetectorService::detect($request->phone ?? ''),
            'language' => $request->language,
            'status'   => $request->input('status', 'active'),
            'source'   => 'manual',
        ]);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        ActivityLogger::contactCreated($contact);
        AuditLogger::log('create_contact', $contact, null, $contact->only(['name', 'phone', 'email']));

        return (new ContactResource($contact->load('tags')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);
        $contact->loadCount('messages')->load(['tags', 'notes.author', 'activities.user']);

        return (new ContactResource($contact))->response();
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $old       = $contact->only(['name', 'phone', 'email', 'opted_in']);
        $oldTagIds = $contact->tags->pluck('id')->toArray();
        $newTagIds = $request->tags ?? [];

        $contact->update([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in'),
            'country'  => $request->filled('phone')
                          ? CountryDetectorService::detect($request->phone)
                          : $contact->country,
            'language' => $request->input('language', $contact->language),
            'status'   => $request->input('status', $contact->status),
        ]);

        $contact->tags()->sync($newTagIds);

        $allTags = Tag::whereIn('id', array_unique(array_merge($oldTagIds, $newTagIds)))->get()->keyBy('id');
        foreach (array_diff($newTagIds, $oldTagIds) as $addedId) {
            if ($allTags->has($addedId)) {
                ActivityLogger::tagAdded($contact, $allTags[$addedId]);
            }
        }
        foreach (array_diff($oldTagIds, $newTagIds) as $removedId) {
            if ($allTags->has($removedId)) {
                ActivityLogger::tagRemoved($contact, $allTags[$removedId]);
            }
        }

        ActivityLogger::contactUpdated($contact);
        AuditLogger::log('update_contact', $contact, $old, $contact->fresh()->only(['name', 'phone', 'email', 'opted_in']));

        return (new ContactResource($contact->load('tags')))->response();
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);
        AuditLogger::log('delete_contact', $contact, $contact->only(['name', 'phone']));
        $contact->delete();

        return response()->json(['message' => 'Contact deleted.']);
    }

    public function toggleOptIn(Contact $contact): JsonResponse
    {
        $this->authorize('toggleOptIn', $contact);
        $contact->update(['opted_in' => !$contact->opted_in]);
        ActivityLogger::contactUpdated($contact);

        return response()->json(['opted_in' => $contact->opted_in]);
    }

    public function notes(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);
        $notes = $contact->notes()->with('author')->paginate(20);

        return response()->json($notes);
    }

    public function storeNote(Request $request, Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $data = $request->validate(['note' => 'required|string|max:2000']);

        $note = $contact->notes()->create([
            'user_id' => auth()->id(),
            'note'    => $data['note'],
        ]);

        return response()->json($note->load('author'), 201);
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $phone     = $request->get('phone');
        $email     = $request->get('email');
        $excludeId = $request->get('exclude_id');

        $result = ['phone_exists' => false, 'email_exists' => false, 'contact' => null];

        if ($phone) {
            $normalized = app(PhoneNormalizerService::class)->normalize($phone);
            if ($normalized) {
                $q = Contact::where('phone', $normalized);
                if ($excludeId) {
                    $q->where('id', '!=', $excludeId);
                }
                $existing = $q->first(['id', 'name', 'phone']);
                if ($existing) {
                    $result['phone_exists'] = true;
                    $result['contact']      = ['id' => $existing->id, 'name' => $existing->name];
                }
            }
        }

        if ($email && !$result['phone_exists']) {
            $q = Contact::where('email', $email);
            if ($excludeId) {
                $q->where('id', '!=', $excludeId);
            }
            $existing = $q->first(['id', 'name', 'email']);
            if ($existing) {
                $result['email_exists'] = true;
                $result['contact']      = ['id' => $existing->id, 'name' => $existing->name];
            }
        }

        return response()->json($result);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('import', Contact::class);

        $request->validate([
            'file'             => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'duplicate_action' => 'in:skip,update',
        ]);

        $import = new ContactsImport($request->get('duplicate_action', 'skip'));
        Excel::import($import, $request->file('file'));

        AuditLogger::log('import_contacts', null, null, [
            'imported'   => $import->imported,
            'updated'    => $import->updated,
            'duplicates' => count($import->duplicates),
            'errors'     => count($import->errors),
        ]);

        return response()->json([
            'message'    => 'Import complete.',
            'imported'   => $import->imported,
            'updated'    => $import->updated,
            'duplicates' => $import->duplicates,
            'errors'     => $import->errors,
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $contacts = Contact::whereIn('id', $request->ids)->get();
        $count    = 0;
        foreach ($contacts as $contact) {
            if (auth()->user()->can('delete', $contact)) {
                AuditLogger::log('delete_contact', $contact, $contact->only(['name', 'phone']));
                $contact->delete();
                $count++;
            }
        }

        return response()->json(['message' => "{$count} contact(s) deleted.", 'deleted' => $count]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $data = $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer',
            'action'   => 'required|in:add_note,add_tags,opted_out,opted_in,set_status,set_language',
            'note'     => 'required_if:action,add_note|string|max:2000|nullable',
            'tag_ids'  => 'required_if:action,add_tags|array|nullable',
            'tag_ids.*'=> 'integer',
            'status'   => 'required_if:action,set_status|in:active,inactive,interested,follow_up,not_interested|nullable',
            'language' => 'required_if:action,set_language|string|max:50|nullable',
        ]);

        $contacts = Contact::whereIn('id', $data['ids'])->get();
        $count    = 0;

        foreach ($contacts as $contact) {
            if (!auth()->user()->can('update', $contact)) {
                continue;
            }

            match ($data['action']) {
                'add_note' => (function () use ($contact, $data) {
                    $contact->notes()->create([
                        'user_id' => auth()->id(),
                        'note'    => $data['note'],
                    ]);
                    ActivityLogger::contactUpdated($contact);
                })(),

                'add_tags' => (function () use ($contact, $data) {
                    $contact->tags()->syncWithoutDetaching($data['tag_ids'] ?? []);
                    $tags = Tag::whereIn('id', $data['tag_ids'] ?? [])->get();
                    foreach ($tags as $tag) {
                        ActivityLogger::tagAdded($contact, $tag);
                    }
                })(),

                'opted_out' => (function () use ($contact) {
                    $contact->update(['opted_in' => false]);
                    ActivityLogger::contactUpdated($contact);
                })(),

                'opted_in' => (function () use ($contact) {
                    $contact->update(['opted_in' => true]);
                    ActivityLogger::contactUpdated($contact);
                })(),

                'set_status' => (function () use ($contact, $data) {
                    $contact->update(['status' => $data['status']]);
                    ActivityLogger::contactUpdated($contact);
                })(),

                'set_language' => (function () use ($contact, $data) {
                    $contact->update(['language' => $data['language']]);
                    ActivityLogger::contactUpdated($contact);
                })(),
            };

            $count++;
        }

        AuditLogger::log('bulk_update_contacts', null, null, [
            'action' => $data['action'],
            'count'  => $count,
        ]);

        return response()->json(['message' => "{$count} contact(s) updated.", 'updated' => $count]);
    }

    public function contactMessages(Contact $contact, Request $request): JsonResponse
    {
        $this->authorize('view', $contact);

        $messages = Message::with('campaign:id,name')
            ->where('contact_id', $contact->id)
            ->latest('updated_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($messages);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Contact::class);

        $filter    = $request->get('filter', 'all');
        $segmentId = $request->get('segment_id');
        $tagId     = $request->get('tag_id');
        $format    = $request->get('format', 'xlsx');

        $export   = new ContactsExport($filter, $segmentId ? (int) $segmentId : null, $tagId ? (int) $tagId : null);
        $filename = 'contacts-export-' . now()->format('Y-m-d') . '.' . $format;

        AuditLogger::log('export_contacts', null, null, ['filter' => $filter, 'format' => $format]);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename);
    }

    public function filterCount(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query();

        if ($tagId = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }
        if ($optIn = $request->get('opt_in')) {
            $query->where('opted_in', $optIn === '1');
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $filtersRaw = $request->input('filters', []);
        if (is_string($filtersRaw) && $filtersRaw !== '') {
            $filtersRaw = json_decode($filtersRaw, true) ?? [];
        }
        if (is_array($filtersRaw) && !empty($filtersRaw)) {
            $this->applyContactFilters($query, $filtersRaw, $request->input('filter_logic', 'and'));
        }

        return response()->json(['count' => $query->count()]);
    }

    public function bulkSuppress(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $contacts = Contact::whereIn('id', $data['ids'])->get();
        $count    = 0;

        foreach ($contacts as $contact) {
            SuppressionList::updateOrCreate(
                ['phone' => $contact->phone],
                [
                    'name'           => $contact->name,
                    'reason'         => $data['reason'] ?? 'Manual suppression',
                    'contact_id'     => $contact->id,
                    'added_by'       => auth()->id(),
                    'failure_count'  => 1,
                    'last_failed_at' => now(),
                ]
            );
            $contact->update(['opted_in' => false]);
            $count++;
        }

        AuditLogger::log('bulk_suppress_contacts', null, null, [
            'count'  => $count,
            'reason' => $data['reason'] ?? 'Manual suppression',
        ]);

        return response()->json(['message' => "{$count} contact(s) suppressed.", 'suppressed' => $count]);
    }

    public function analyticsCountry(): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $rows = Contact::selectRaw('country, COUNT(*) as total, SUM(opted_in) as opted_in_count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        $data = $rows->map(fn($r) => [
            'country'       => $r->country,
            'total'         => (int) $r->total,
            'opted_in'      => (int) $r->opted_in_count,
            'percentage'    => $grandTotal > 0 ? round(($r->total / $grandTotal) * 100, 1) : 0,
        ]);

        return response()->json(['data' => $data, 'total' => $grandTotal]);
    }

    public function analyticsLanguage(): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $rows = Contact::selectRaw('language, COUNT(*) as total')
            ->whereNotNull('language')
            ->where('language', '!=', '')
            ->groupBy('language')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        $data = $rows->map(fn($r) => [
            'language'   => $r->language,
            'total'      => (int) $r->total,
            'percentage' => $grandTotal > 0 ? round(($r->total / $grandTotal) * 100, 1) : 0,
        ]);

        return response()->json(['data' => $data, 'total' => $grandTotal]);
    }

    // ── Advanced contact filter (self-contained, no SegmentService dependency) ──

    private function applyContactFilters(Builder $query, array $conditions, string $logic): void
    {
        if (empty($conditions)) {
            return;
        }

        $applyOne = function (Builder $q, array $c): void {
            $field    = $c['field']    ?? null;
            $operator = $c['operator'] ?? 'contains';
            $value    = $c['value']    ?? null;

            if (!$field) {
                return;
            }

            match ($field) {
                'name'  => $this->applyStrFilter($q, 'name', $operator, $value),
                'phone' => $this->applyStrFilter($q, 'phone', $operator, $value),

                'country', 'phone_country' => ($value !== null && $value !== '') ? match ($operator) {
                    'is_not', 'not_contains' => $q->where('country', '!=', $value),
                    'contains'               => $q->where('country', 'like', "%{$value}%"),
                    default                  => $q->where('country', $value),
                } : null,

                'language' => ($value !== null && $value !== '') ? match ($operator) {
                    'is_not'       => $q->where('language', '!=', $value),
                    'contains'     => $q->where('language', 'like', "%{$value}%"),
                    'has'          => $q->whereNotNull('language')->where('language', '!=', ''),
                    'not_has'      => $q->where(fn($x) => $x->whereNull('language')->orWhere('language', '')),
                    default        => $q->where('language', $value),
                } : null,

                'status' => ($value !== null && $value !== '')
                    ? $q->where('status', $value)
                    : null,

                'source' => ($value !== null && $value !== '')
                    ? $q->where('source', $value)
                    : null,

                'email' => match ($operator) {
                    'has'          => $q->whereNotNull('email')->where('email', '!=', ''),
                    'not_has'      => $q->where(fn ($x) => $x->whereNull('email')->orWhere('email', '')),
                    'contains'     => ($value !== null && $value !== '') ? $q->where('email', 'like', "%{$value}%") : null,
                    'not_contains' => ($value !== null && $value !== '') ? $q->where('email', 'not like', "%{$value}%") : null,
                    default        => null,
                },

                'opted_in' => ($value !== null && $value !== '')
                    ? $q->where('opted_in', (bool) (int) $value)
                    : null,

                'tag' => match ($operator) {
                    'has_any'  => $q->whereHas('tags'),
                    'has_none' => $q->whereDoesntHave('tags'),
                    'has'      => ($value !== null && $value !== '')
                        ? $q->whereHas('tags', fn ($x) => $x->where('tags.id', (int) $value))
                        : null,
                    'not_has'  => ($value !== null && $value !== '')
                        ? $q->whereDoesntHave('tags', fn ($x) => $x->where('tags.id', (int) $value))
                        : null,
                    default    => null,
                },

                'created_at' => ($value !== null && $value !== '') ? match ($operator) {
                    'before'      => $q->whereDate('created_at', '<=', $value),
                    'after'       => $q->whereDate('created_at', '>=', $value),
                    'within_days' => $q->where('created_at', '>=', now()->subDays((int) $value)),
                    default       => null,
                } : null,

                default => null,
            };
        };

        if ($logic === 'or') {
            $query->where(function (Builder $q) use ($conditions, $applyOne) {
                foreach ($conditions as $i => $condition) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method(function (Builder $inner) use ($condition, $applyOne) {
                        $applyOne($inner, $condition);
                    });
                }
            });
        } else {
            $query->where(function (Builder $q) use ($conditions, $applyOne) {
                foreach ($conditions as $condition) {
                    $applyOne($q, $condition);
                }
            });
        }
    }

    private function applyStrFilter(Builder $query, string $col, string $op, mixed $val): void
    {
        if ($val === null || $val === '') {
            return;
        }
        match ($op) {
            'contains'     => $query->where($col, 'like', "%{$val}%"),
            'not_contains' => $query->where($col, 'not like', "%{$val}%"),
            'starts_with'  => $query->where($col, 'like', "{$val}%"),
            'ends_with'    => $query->where($col, 'like', "%{$val}"),
            'is'           => $query->where($col, $val),
            'is_not'       => $query->where($col, '!=', $val),
            default        => $query->where($col, 'like', "%{$val}%"),
        };
    }
}
