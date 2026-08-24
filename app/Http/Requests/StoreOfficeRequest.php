<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'modes.manage'); // Or admin permissions
        })->exists();
    }

    public function rules(): array
    {
        $officeId = $this->route('office') ? $this->route('office')->id : null;

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('offices', 'code')->ignore($officeId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'location_type' => [
                'required',
                Rule::in(['REGIONAL', 'PROVINCIAL', 'FIELD']),
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim($this->code)),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    public function messages(): array
    {
        return [
            'code.required' => 'An office code is required (e.g., ORD, PFO_ISABELA).',
            'code.unique' => 'This office code is already registered in the system directory.',
            'location_type.in' => 'Location classification must be REGIONAL, PROVINCIAL, or FIELD.',
        ];
    }
}
