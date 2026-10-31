<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhaseRequest extends FormRequest // or StoreModePhaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->hasPermission('modes.edit'));
    }

    public function rules(): array
    {
        $modeId = $this->route('mode')?->id;
        $phaseId = $this->route('phase')?->id;

        return [
            'phase_sequence' => ['required', 'integer', 'min:1', Rule::unique('procurement_mode_phases', 'phase_sequence')->where(fn ($query) => $query->where('procurement_mode_id', $modeId))->ignore($phaseId)],
            'phase_name'     => ['required', 'string', 'max:255'],
            'allowable_days' => ['required', 'integer', 'min:1', 'max:180'],
            'description'    => ['nullable', 'string'],
            'role_ids'       => ['nullable', 'array', 'max:100'],
            'role_ids.*'     => ['required', 'integer', 'distinct', 'exists:roles,id'],

            // Attachment rules validation
            'attachments'                            => ['nullable', 'array', 'max:100'],
            'attachments.*.id'                       => ['nullable', 'integer', 'exists:attachments,id'],
            'attachments.*.name'                     => ['required', 'string', 'max:255'],
            'attachments.*.form_id'                  => ['nullable', 'integer', Rule::exists('forms', 'id')->where(fn ($query) => $query->where('status', 'published'))],
            'attachments.*.is_mandatory'             => ['nullable', 'boolean'],

            // Signatory chain validation (CRITICAL FOR $request->validated())
            'attachments.*.signatories'              => ['nullable', 'array', 'max:50'],
            'attachments.*.signatories.*.role_id'   => ['required', 'exists:roles,id'],
            'attachments.*.signatories.*.sequence'  => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
