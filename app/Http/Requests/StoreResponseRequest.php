<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response_id' => 'nullable|integer|exists:responses,id',
            'nps_score' => 'nullable|integer|min:0|max:10',
            'open_text' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'nps_score.min' => 'NPS score must be between 0 and 10.',
            'nps_score.max' => 'NPS score must be between 0 and 10.',
            'open_text.max' => 'Response must not exceed 1000 characters.',
        ];
    }
}
