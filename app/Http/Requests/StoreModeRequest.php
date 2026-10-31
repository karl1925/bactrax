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
        $user = $this->user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        $permission = $this->isMethod('POST') ? 'modes.add' : 'modes.edit';
        return $user->hasPermission($permission);
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
                'max:9999999999999.99',
            ],
            'max_abc' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'gte:min_abc',
            ],
            'description' => ['nullable', 'string', 'max:10000'],
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
            'code' => is_string($this->input('code')) ? strtoupper(trim($this->input('code'))) : $this->input('code'),
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
