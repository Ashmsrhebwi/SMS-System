<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSmsTemplateRequest;
use App\Http\Requests\UpdateSmsTemplateRequest;
use App\Models\SmsTemplate;
use App\Models\TemplateCategory;
use App\Services\AuditLogger;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SmsTemplate::class);
        $templates  = SmsTemplate::with(['creator', 'category'])->latest()->get();
        $categories = TemplateCategory::orderBy('name')->get();
        return view('templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', SmsTemplate::class);
        $categories = TemplateCategory::orderBy('name')->get();
        return view('templates.create', compact('categories'));
    }

    public function store(StoreSmsTemplateRequest $request)
    {
        $template = SmsTemplate::create([
            ...$request->validated(),
            'created_by'  => auth()->id(),
            'category_id' => $request->category_id ?: null,
        ]);

        AuditLogger::log('create_template', $template, null, $template->only(['name']));

        return redirect()->route('templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(SmsTemplate $template)
    {
        $this->authorize('update', $template);
        $categories = TemplateCategory::orderBy('name')->get();
        return view('templates.edit', compact('template', 'categories'));
    }

    public function update(UpdateSmsTemplateRequest $request, SmsTemplate $template)
    {
        $old = $template->only(['name', 'content', 'category_id']);

        $template->update([
            ...$request->validated(),
            'category_id' => $request->category_id ?: null,
        ]);

        AuditLogger::log('update_template', $template, $old, $template->fresh()->only(['name', 'category_id']));

        return redirect()->route('templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(SmsTemplate $template)
    {
        $this->authorize('delete', $template);
        AuditLogger::log('delete_template', $template, $template->only(['name']));
        $template->delete();
        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }
}
