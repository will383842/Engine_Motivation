<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|in:one_time,daily,weekly,monthly,recurring,streak_based,event_triggered',
            'target_count' => 'required|integer|min:1',
            'xp_reward' => 'required|integer|min:0',
        ];
    }
}
