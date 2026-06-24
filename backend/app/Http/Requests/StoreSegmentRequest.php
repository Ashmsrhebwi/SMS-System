<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Segment::class);
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'description'            => 'nullable|string|max:500',
            'logic'                  => 'nullable|string|in:and,or',
            'conditions'             => 'nullable|array',
            'conditions.*.field'     => 'required|string|in:name,phone,email,tag,opted_in,country,phone_country,created_at,created_before,created_after,message_count,last_messaged',
            'conditions.*.operator'  => 'nullable|string|in:is,is_not,has,not_has,has_any,has_none,contains,not_contains,starts_with,ends_with,before,after,within_days,never,ever,eq,gt,gte,lt,lte,not_starts_with',
            'conditions.*.value'     => 'nullable|string|max:255',
        ];
    }
}
