<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        return $user->roles()->whereHas('permissions', fn($q) => $q->whereIn('name', ['procurement.add', 'procurement.create']))->exists()
            || $user->roles()->whereHas('privileges', fn($q) => $q->whereIn('code', ['procurement.add', 'procurement.create', 'can_add', 'can_create']))->exists();
    }

    public function rules(): array
    {
        $user = $this->user();
        $officeRule = $user->isAdmin() || $user->isCentralRole()
            ? Rule::exists('offices', 'id')->where(fn ($query) => $query->where('is_active', true))
            : Rule::in($user->offices()->where('offices.is_active', true)->pluck('offices.id')->all());

        return [
            'procurement_mode_id' => ['required', 'integer', Rule::exists('procurement_modes', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'purpose' => ['required', 'string', 'max:5000'],
            'fund_cluster' => ['required', 'string', 'max:255', Rule::exists('fund_clusters', 'code')->where(fn ($query) => $query->where('is_active', true))],
            'end_user_office_id' => ['required', 'integer', $officeRule],
            'pr_date' => ['required', 'date'],
            'responsibility_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
