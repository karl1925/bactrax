<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $procurement = $this->route('procurement');
        if (!$user || !$procurement) return false;
        if ($user->isAdmin() || (int)$user->id === (int)$procurement->created_by) return true;
        return $user->roles()->whereHas('permissions', fn($q) => $q->where('name', 'procurement.edit'))->exists()
            || $user->roles()->whereHas('privileges', fn($q) => $q->whereIn('code', ['procurement.edit', 'can_edit']))->exists();
    }

    public function rules(): array
    {
        return [
            'procurement_mode_id' => ['sometimes', 'required', 'integer', 'exists:procurement_modes,id'],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'fund_cluster' => ['sometimes', 'nullable', 'string', 'max:255', Rule::exists('fund_clusters', 'code')->where(fn ($query) => $query->where('is_active', true))],
            'end_user_office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
            'pr_date' => ['sometimes', 'nullable', 'date'],
            'responsibility_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'array', 'max:500'],
            'items.*' => ['array'],
            'items.*.id' => ['nullable', 'integer', 'min:1'],
            'items.*.is_lot' => ['sometimes', 'boolean'],
            'items.*.item_bank_item_id' => ['nullable', 'integer', Rule::exists('item_bank_items', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'items.*.cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'items.*.sub_items' => ['nullable', 'array', 'max:100'],
            'items.*.sub_items.*.id' => ['nullable', 'integer', 'min:1'],
            'items.*.sub_items.*.item_bank_item_id' => ['nullable', 'integer', Rule::exists('item_bank_items', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'items.*.sub_items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.sub_items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.sub_items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'items.*.sub_items.*.cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'attachment_fields' => ['sometimes', 'nullable', 'json', 'max:2000000'],
            'attachment_completion_data' => ['sometimes', 'nullable', 'json', 'max:2000000'],
            'attachment_files' => ['sometimes', 'array', 'max:100'],
            'attachment_files.*' => ['file', 'mimes:pdf', 'max:20480'],
            'save_action' => ['sometimes', 'in:draft,route'],
        ];
    }
}
