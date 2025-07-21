<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // No auth required per spec
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:2|max:2',
            'questions.*.type' => 'required|in:nps,text',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.options' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'questions.min' => 'A survey must have exactly 2 questions.',
            'questions.max' => 'A survey must have exactly 2 questions.',
            'questions.*.type.in' => 'Question type must be either NPS or text.',
        ];
    }
}
