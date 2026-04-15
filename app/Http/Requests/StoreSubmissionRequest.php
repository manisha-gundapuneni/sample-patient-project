<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'exists:instruments,id'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.answer_scale' => ['nullable', 'integer', 'min:1', 'max:5'],
            'answers.*.answer_yes_no' => ['nullable', 'boolean'],
            'answers.*.answer_free_text' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'instrument_id.required' => 'Instrument ID is required.',
            'instrument_id.exists' => 'The specified instrument does not exist.',
            'answers.required' => 'Answers are required.',
            'answers.*.question_id.required' => 'Question ID is required for each answer.',
            'answers.*.answer_scale.min' => 'Scale answer must be between 1 and 5.',
            'answers.*.answer_scale.max' => 'Scale answer must be between 1 and 5.',
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $instrumentId = $this->input('instrument_id');
            $answers = $this->input('answers', []);

            // Get instrument with questions
            $instrument = \App\Models\Instrument::with('questions')->find($instrumentId);
            if (!$instrument) {
                return;
            }

            $questionIds = $instrument->questions->pluck('id')->toArray();
            $answeredQuestionIds = collect($answers)->pluck('question_id')->toArray();

            // Check all questions are answered
            $missingQuestions = array_diff($questionIds, $answeredQuestionIds);
            if (!empty($missingQuestions)) {
                $validator->errors()->add('answers', 'All questions must be answered.');
            }

            // Validate answer types match question types
            foreach ($answers as $index => $answer) {
                $question = \App\Models\Question::find($answer['question_id'] ?? null);
                if (!$question) {
                    continue;
                }

                $this->validateAnswerType($validator, $question, $answer, $index);
            }
        });
    }

    private function validateAnswerType($validator, $question, $answer, $index): void
    {
        match ($question->response_type) {
            'scale_1_5' => $this->validateScaleAnswer($validator, $answer, $index),
            'yes_no' => $this->validateYesNoAnswer($validator, $answer, $index),
            'free_text' => true, // free_text accepts anything
            default => false,
        };
    }

    private function validateScaleAnswer($validator, $answer, $index): void
    {
        if (!isset($answer['answer_scale']) || $answer['answer_scale'] === null) {
            $validator->errors()->add("answers.{$index}.answer_scale", 'Scale answer is required.');
        }
    }

    private function validateYesNoAnswer($validator, $answer, $index): void
    {
        if (!isset($answer['answer_yes_no']) || $answer['answer_yes_no'] === null) {
            $validator->errors()->add("answers.{$index}.answer_yes_no", 'Yes/No answer is required.');
        }
    }
}