<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => 'required|string|regex:/^\+[0-9]+$/',
            'twilio_sid' => 'required|string',
            'current_daily_limit' => 'required|integer|min:1',
        ];
    }
}
