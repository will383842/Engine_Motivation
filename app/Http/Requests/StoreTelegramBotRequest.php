<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelegramBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bot_token' => 'required|string',
            'bot_username' => 'required|string',
            'daily_limit' => 'nullable|integer|min:1',
        ];
    }
}
