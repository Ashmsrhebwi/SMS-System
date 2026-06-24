<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Tag::class);
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:100|unique:tags,name',
            'color' => 'required|string|max:20',
        ];
    }
}
