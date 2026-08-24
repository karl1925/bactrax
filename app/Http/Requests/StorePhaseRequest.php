<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'modes.manage');
        })->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phase_sequence' => [
                'required',
                'integer',
                'min:1',
            ],
            'phase_name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'allowable_days' => [
                'required',
                'integer',
                'min:1',
                'max:60', // Statutory ceiling under RA 12009
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'phase_sequence.required' => 'Phase step sequence index is required.',
            'phase_name.required' => 'Please provide a descriptive phase title.',
            'allowable_days.max' => 'Turnaround SLA ceiling cannot exceed 60 calendar days under RA 12009 statutory rules.',
        ];
    }
}
