<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tag'));
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id;

        return [
            'name'  => "required|string|max:100|unique:tags,name,{$tagId}",
            'color' => 'required|string|max:20',
        ];
    }
}
