<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GlobalBlacklist;
use App\Services\AuditLogger;
use App\Services\PhoneNormalizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GlobalBlacklist::class);

        $query = GlobalBlacklist::latest();

        if ($search = $request->get('search')) {
            $query->where('phone', 'like', "%{$search}%");
        }

        $entries = $query->paginate($request->integer('per_page', 50));

        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GlobalBlacklist::class);

        $data = $request->validate([
            'phone'  => 'required|string|max:20',
            'reason' => 'nullable|string|max:500',
        ]);

        $normalized = app(PhoneNormalizerService::class)->normalize($data['phone']);
        $phone      = $normalized ?? $data['phone'];

        if (GlobalBlacklist::where('phone', $phone)->exists()) {
            return response()->json(['message' => 'This number is already blacklisted.'], 422);
        }

        $entry = GlobalBlacklist::create([
            'phone'  => $phone,
            'reason' => $data['reason'] ?? null,
        ]);

        AuditLogger::log('blacklist_add', null, null, ['phone' => $phone]);

        return response()->json(['data' => $entry], 201);
    }

    public function destroy(GlobalBlacklist $globalBlacklist): JsonResponse
    {
        $this->authorize('delete', GlobalBlacklist::class);

        AuditLogger::log('blacklist_remove', null, null, ['phone' => $globalBlacklist->phone]);
        $globalBlacklist->delete();

        return response()->json(['message' => 'Number removed from blacklist.']);
    }
}
