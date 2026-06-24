<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSmsTemplateRequest;
use App\Http\Requests\UpdateSmsTemplateRequest;
use App\Models\SmsTemplate;
use App\Models\TemplateCategory;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SmsTemplate::class);

        $query = SmsTemplate::with(['creator', 'category'])->latest();

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%"));
        }

        $templates  = $query->paginate($request->integer('per_page', 50));
        $categories = TemplateCategory::orderBy('name')->get();

        return response()->json([
            'data'       => $templates,
            'categories' => $categories,
        ]);
    }

    public function store(StoreSmsTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', SmsTemplate::class);

        $template = SmsTemplate::create([
            ...$request->validated(),
            'created_by'  => auth()->id(),
            'category_id' => $request->category_id ?: null,
        ]);

        AuditLogger::log('create_template', $template, null, $template->only(['name']));

        return response()->json(['data' => $template->load(['creator', 'category'])], 201);
    }

    public function show(SmsTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json(['data' => $template->load(['creator', 'category'])]);
    }

    public function update(UpdateSmsTemplateRequest $request, SmsTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $old = $template->only(['name', 'content', 'category_id']);

        $template->update([
            ...$request->validated(),
            'category_id' => $request->category_id ?: null,
        ]);

        AuditLogger::log('update_template', $template, $old, $template->fresh()->only(['name', 'category_id']));

        return response()->json(['data' => $template->load(['creator', 'category'])]);
    }

    public function destroy(SmsTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        AuditLogger::log('delete_template', $template, $template->only(['name']));
        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }
}
