<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TemplateCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TemplateCategory::withCount('templates')->orderBy('name')->get();
        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:template_categories,name']);
        $category = TemplateCategory::create($data);
        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, TemplateCategory $templateCategory): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:template_categories,name,' . $templateCategory->id]);
        $templateCategory->update($data);
        return response()->json(['data' => $templateCategory]);
    }

    public function destroy(TemplateCategory $templateCategory): JsonResponse
    {
        if ($templateCategory->templates()->exists()) {
            return response()->json(['message' => 'Cannot delete a category that has templates.'], 422);
        }
        $templateCategory->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
