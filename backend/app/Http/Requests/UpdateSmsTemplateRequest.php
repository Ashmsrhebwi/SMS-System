<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('template'));
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'content' => 'required|string|max:1600',
        ];
    }
}
