<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'rules' => 'required|array|min:1',
            'rules.*.field' => 'required|string',
            'rules.*.operator' => 'required|in:eq,neq,gt,lt,gte,lte,in,not_in,contains',
        ];
    }
}
