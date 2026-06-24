<?php

namespace App\Http\Controllers;

use App\Models\TemplateCategory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class TemplateCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TemplateCategory::class);
        $categories = TemplateCategory::withCount('templates')->orderBy('name')->get();
        return view('template-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', TemplateCategory::class);

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:template_categories,name',
        ]);

        $category = TemplateCategory::create($data);
        AuditLogger::log('create_template_category', $category, null, ['name' => $category->name]);

        return redirect()->route('template-categories.index')
            ->with('success', 'Category created.');
    }

    public function update(Request $request, TemplateCategory $templateCategory)
    {
        $this->authorize('update', $templateCategory);

        $data = $request->validate([
            'name' => "required|string|max:100|unique:template_categories,name,{$templateCategory->id}",
        ]);

        $old = $templateCategory->only(['name']);
        $templateCategory->update($data);
        AuditLogger::log('update_template_category', $templateCategory, $old, ['name' => $templateCategory->name]);

        return redirect()->route('template-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(TemplateCategory $templateCategory)
    {
        $this->authorize('delete', $templateCategory);

        AuditLogger::log('delete_template_category', $templateCategory, $templateCategory->only(['name']));
        $templateCategory->delete();

        return redirect()->route('template-categories.index')
            ->with('success', 'Category deleted.');
    }
}
