<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => 'required|string',
            'data' => 'required|array',
            'timestamp' => 'required|integer',
        ];
    }
}
