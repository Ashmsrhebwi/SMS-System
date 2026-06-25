<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $settings = SystemSetting::all()->map(fn($s) => [
            'key'         => $s->key,
            'value'       => $s->value,
            'type'        => $s->type,
            'group'       => $s->group,
            'label'       => $s->label,
            'description' => $s->description,
            'updated_at'  => $s->updated_at?->toISOString(),
        ])->groupBy('group');

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $setting = SystemSetting::find($key);

        if (!$setting) {
            return response()->json(['message' => 'Setting not found.'], 404);
        }

        $value = $request->input('value');

        // Type validation
        $validated = match ($setting->type) {
            'integer' => is_numeric($value) ? (string)(int)$value : null,
            'float'   => is_numeric($value) ? (string)(float)$value : null,
            'boolean' => in_array(strtolower((string)$value), ['true', 'false', '1', '0']) ? $value : null,
            default   => (string)$value,
        };

        if ($validated === null) {
            return response()->json(['message' => "Invalid value for type '{$setting->type}'."], 422);
        }

        $old = $setting->value;
        SystemSetting::set($key, $validated);

        AuditLogger::log('update_setting', null, ['key' => $key, 'value' => $old], ['key' => $key, 'value' => $validated]);

        return response()->json(['message' => 'Setting updated.', 'key' => $key, 'value' => $validated]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $updates = $request->validate([
            'settings'             => 'required|array',
            'settings.*.key'       => 'required|string',
            'settings.*.value'     => 'present',
        ]);

        $changed = [];
        foreach ($updates['settings'] as $item) {
            $setting = SystemSetting::find($item['key']);
            if ($setting) {
                $old = $setting->value;
                SystemSetting::set($item['key'], (string)$item['value']);
                $changed[] = ['key' => $item['key'], 'old' => $old, 'new' => (string)$item['value']];
            }
        }

        if (!empty($changed)) {
            AuditLogger::log('bulk_update_settings', null, null, $changed);
        }

        return response()->json(['message' => count($changed) . ' setting(s) updated.']);
    }
}
