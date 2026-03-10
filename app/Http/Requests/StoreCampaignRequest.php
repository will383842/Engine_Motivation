<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'segment_id' => 'required|uuid|exists:segments,id',
            'template_id' => 'required|uuid|exists:message_templates,id',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}
