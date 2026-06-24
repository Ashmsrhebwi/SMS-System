<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::withCount('contacts')->orderBy('name')->get();
        return response()->json(['data' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Tag::class);
        $data = $request->validate([
            'name'  => 'required|string|max:255|unique:tags,name',
            'color' => 'nullable|string|max:20',
        ]);

        $tag = Tag::create($data);
        AuditLogger::log('create_tag', $tag, null, $tag->only(['name']));

        return response()->json(['data' => $tag], 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);
        $data = $request->validate([
            'name'  => 'required|string|max:255|unique:tags,name,' . $tag->id,
            'color' => 'nullable|string|max:20',
        ]);

        $old = $tag->only(['name', 'color']);
        $tag->update($data);
        AuditLogger::log('update_tag', $tag, $old, $tag->only(['name', 'color']));

        return response()->json(['data' => $tag]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);
        AuditLogger::log('delete_tag', $tag, $tag->only(['name']));
        $tag->delete();

        return response()->json(['message' => 'Tag deleted.']);
    }
}
