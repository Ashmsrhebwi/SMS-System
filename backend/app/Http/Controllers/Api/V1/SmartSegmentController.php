<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Segment;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartSegmentController extends Controller
{
    /**
     * Preview how many groups would be created.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_size' => 'required|integer|min:1|max:10000',
            'filters'    => 'nullable|array',
            'country'    => 'nullable|string',
            'language'   => 'nullable|string',
            'status'     => 'nullable|string',
            'tag_id'     => 'nullable|integer',
        ]);

        $query  = $this->buildBaseQuery($data);
        $total  = $query->count();
        $groups = $data['group_size'] > 0 ? (int) ceil($total / $data['group_size']) : 0;

        return response()->json([
            'total_contacts' => $total,
            'group_size'     => $data['group_size'],
            'groups_count'   => $groups,
        ]);
    }

    /**
     * Automatically create multiple segments by distributing contacts evenly.
     */
    public function distribute(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'name_prefix' => 'required|string|max:100',
            'group_size'  => 'required|integer|min:1|max:10000',
            'country'     => 'nullable|string',
            'language'    => 'nullable|string',
            'status'      => 'nullable|string',
            'tag_id'      => 'nullable|integer',
            'opted_in'    => 'nullable|boolean',
        ]);

        $query      = $this->buildBaseQuery($data);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            return response()->json(['message' => 'No contacts match the criteria.'], 422);
        }

        $groupSize   = (int) $data['group_size'];
        $groupsCount = (int) ceil($totalCount / $groupSize);

        if ($groupsCount > 500) {
            return response()->json([
                'message' => "This would create {$groupsCount} segments. Please increase group size.",
            ], 422);
        }

        $created    = [];
        $groupIndex = 1;

        $query->select('id')->chunk($groupSize, function ($contacts) use (
            $data, &$groupIndex, &$created, $groupsCount
        ) {
            $label   = str_pad($groupIndex, strlen((string) $groupsCount), '0', STR_PAD_LEFT);
            $segment = Segment::create([
                'name'        => "{$data['name_prefix']} — Group {$label}",
                'description' => "Auto-distributed group {$groupIndex} of {$groupsCount}",
                'conditions'  => [],
                'logic'       => 'and',
            ]);

            $segment->contacts()->attach($contacts->pluck('id')->toArray());

            $created[] = [
                'id'    => $segment->id,
                'name'  => $segment->name,
                'count' => $contacts->count(),
            ];

            $groupIndex++;
        });

        AuditLogger::log('smart_segment_distribute', null, null, [
            'prefix'       => $data['name_prefix'],
            'group_size'   => $groupSize,
            'groups_count' => count($created),
            'total'        => $totalCount,
        ]);

        return response()->json([
            'message'       => count($created) . ' segments created successfully.',
            'total_contacts'=> $totalCount,
            'groups_created'=> count($created),
            'segments'      => $created,
        ], 201);
    }

    private function buildBaseQuery(array $data)
    {
        $query = Contact::where('opted_in', $data['opted_in'] ?? true);

        if (!empty($data['country'])) {
            $query->where('country', $data['country']);
        }
        if (!empty($data['language'])) {
            $query->where('language', $data['language']);
        }
        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (!empty($data['tag_id'])) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', (int) $data['tag_id']));
        }

        return $query;
    }
}
