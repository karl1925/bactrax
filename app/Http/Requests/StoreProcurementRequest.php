<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProcurementMode;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'procurement.add');
        })->exists();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'end_user_office_id' => ['required', 'exists:offices,id'],
            'procurement_mode_id' => ['required', 'exists:procurement_modes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:32'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mode = ProcurementMode::find($this->input('procurement_mode_id'));
            if (!$mode) return;

            $items = $this->input('items', []);
            $calculatedTotal = 0;
            foreach ($items as $item) {
                $calculatedTotal += ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0);
            }

            if ($mode->min_abc > 0 && $calculatedTotal < $mode->min_abc) {
                $validator->errors()->add('procurement_mode_id', "Calculated ABC (₱ " . number_format($calculatedTotal, 2) . ") is below the minimum threshold (₱ " . number_format($mode->min_abc, 2) . ") for {$mode->name}.");
            }

            if ($mode->max_abc !== null && $calculatedTotal > $mode->max_abc) {
                $validator->errors()->add('procurement_mode_id', "Calculated ABC (₱ " . number_format($calculatedTotal, 2) . ") exceeds the statutory maximum threshold (₱ " . number_format($mode->max_abc, 2) . ") for {$mode->name}.");
            }
        });
    }
}
