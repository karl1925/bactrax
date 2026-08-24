<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModeRequest extends FormRequest
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
        $modeId = $this->route('mode') ? $this->route('mode')->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:64',
                'unique:procurement_modes,code,' . $modeId,
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'min_abc' => [
                'required',
                'numeric',
                'min:0',
            ],
            'max_abc' => [
                'nullable',
                'numeric',
                'gte:min_abc',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Prepare data for validation (sanitize inputs).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim($this->code)),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'A unique mode code is required (e.g., SVP, DIRECT).',
            'code.unique' => 'This procurement mode code is already registered.',
            'max_abc.gte' => 'The maximum ABC cap must be greater than or equal to the minimum ABC.',
        ];
    }
}
