<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'trigger_event' => 'nullable|string',
            'priority' => 'integer|min:1|max:100',
            'is_active' => 'boolean',
            'steps' => 'nullable|array',
        ];
    }
}
