<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'mrn' => ['required', 'string', 'max:50', Rule::unique('patients')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Patient name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'mrn.required' => 'Medical Record Number is required.',
            'mrn.unique' => 'This Medical Record Number is already in use.',
        ];
    }
}