<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSegmentRequest;
use App\Http\Requests\UpdateSegmentRequest;
use App\Models\Segment;
use App\Models\Tag;
use App\Services\SegmentService;

class SegmentController extends Controller
{
    public function __construct(private SegmentService $segmentService) {}

    public function index()
    {
        $this->authorize('viewAny', Segment::class);
        $segments = Segment::withCount('campaigns')->orderBy('name')->get();

        foreach ($segments as $segment) {
            $segment->eligible_count = $this->segmentService->countEligible($segment);
        }

        return view('segments.index', compact('segments'));
    }

    public function create()
    {
        $this->authorize('create', Segment::class);
        $tags = Tag::orderBy('name')->get();
        return view('segments.create', compact('tags'));
    }

    public function store(StoreSegmentRequest $request)
    {
        $data = $request->validated();
        $data['conditions'] = $data['conditions'] ?? [];
        Segment::create($data);
        return redirect()->route('segments.index')->with('success', 'Segment created successfully.');
    }

    public function edit(Segment $segment)
    {
        $this->authorize('update', $segment);
        $tags = Tag::orderBy('name')->get();
        $eligibleCount = $this->segmentService->countEligible($segment);
        return view('segments.edit', compact('segment', 'tags', 'eligibleCount'));
    }

    public function update(UpdateSegmentRequest $request, Segment $segment)
    {
        $data = $request->validated();
        $data['conditions'] = $data['conditions'] ?? [];
        $segment->update($data);
        return redirect()->route('segments.index')->with('success', 'Segment updated successfully.');
    }

    public function destroy(Segment $segment)
    {
        $this->authorize('delete', $segment);

        if ($segment->campaigns()->exists()) {
            return back()->with('error', 'Cannot delete a segment that is used by campaigns.');
        }

        $segment->delete();
        return redirect()->route('segments.index')->with('success', 'Segment deleted.');
    }
}
