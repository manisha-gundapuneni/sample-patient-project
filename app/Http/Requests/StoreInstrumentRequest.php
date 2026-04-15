<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.response_type' => ['required', 'in:scale_1_5,yes_no,free_text'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Instrument title is required.',
            'questions.required' => 'At least one question is required.',
            'questions.*.prompt.required' => 'Question prompt is required.',
            'questions.*.response_type.in' => 'Response type must be one of: scale_1_5, yes_no, free_text.',
        ];
    }
}