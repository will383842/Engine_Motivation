<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'slug' => 'required|unique:badges,slug',
            'criteria' => 'required|json',
            'category' => 'nullable|string|max:50',
        ];
    }
}
