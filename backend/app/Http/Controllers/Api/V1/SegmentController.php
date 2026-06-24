<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSegmentRequest;
use App\Http\Requests\UpdateSegmentRequest;
use App\Models\Segment;
use App\Models\Tag;
use App\Services\SegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function __construct(private SegmentService $segmentService) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Segment::class);

        $segments = Segment::withCount('campaigns')->orderBy('name')->get();
        foreach ($segments as $segment) {
            $segment->eligible_count = $this->segmentService->countEligible($segment);
        }

        return response()->json(['data' => $segments]);
    }

    public function store(StoreSegmentRequest $request): JsonResponse
    {
        $data               = $request->validated();
        $data['conditions'] = $data['conditions'] ?? [];
        $segment            = Segment::create($data);

        return response()->json(['data' => $segment], 201);
    }

    public function show(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);
        $segment->loadCount('campaigns');
        $segment->eligible_count = $this->segmentService->countEligible($segment);

        return response()->json(['data' => $segment]);
    }

    public function update(UpdateSegmentRequest $request, Segment $segment): JsonResponse
    {
        $this->authorize('update', $segment);

        $data               = $request->validated();
        $data['conditions'] = $data['conditions'] ?? [];
        $segment->update($data);

        return response()->json(['data' => $segment]);
    }

    public function destroy(Segment $segment): JsonResponse
    {
        $this->authorize('delete', $segment);

        if ($segment->campaigns()->exists()) {
            return response()->json(['message' => 'Cannot delete a segment that is used by campaigns.'], 422);
        }

        $segment->delete();

        return response()->json(['message' => 'Segment deleted.']);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Segment::class);

        $temp             = new Segment();
        $temp->conditions = $request->input('conditions', []);
        $temp->logic      = $request->input('logic', 'and');

        $count = $this->segmentService->countEligible($temp);

        return response()->json(['count' => $count]);
    }

    public function contacts(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);

        $contacts = $this->segmentService->getEligibleContactsQuery($segment)
            ->paginate(50);

        return response()->json($contacts);
    }

    public function count(Segment $segment): JsonResponse
    {
        $this->authorize('view', $segment);

        return response()->json(['count' => $this->segmentService->countEligible($segment)]);
    }
}
