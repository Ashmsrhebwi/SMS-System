<?php

namespace App\Http\Requests;

use App\Services\PhoneNormalizerService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contact'));
    }

    public function rules(): array
    {
        $contactId = $this->route('contact')?->id;

        return [
            'name'     => 'required|string|max:255',
            'phone'    => "required|string|max:30|unique:contacts,phone,{$contactId}",
            'email'    => 'nullable|email|max:255',
            'notes'    => 'nullable|string|max:1000',
            'opted_in' => 'boolean',
            'language' => 'nullable|string|max:50',
            'status'   => 'nullable|in:active,inactive,interested,follow_up,not_interested',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $normalized = app(PhoneNormalizerService::class)->normalize($this->phone);
            if ($normalized) {
                $this->merge(['phone' => $normalized]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already used by another contact.',
        ];
    }
}
