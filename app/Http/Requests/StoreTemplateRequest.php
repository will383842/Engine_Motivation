<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'channel' => 'required|in:telegram,whatsapp',
            'body_template' => 'required|string',
            'language' => 'required|string|size:2',
        ];
    }
}
