<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SmsTemplate::class);
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'content' => 'required|string|max:1600',
        ];
    }
}
