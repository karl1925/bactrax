<?php

namespace App\Services;

use App\Models\Procurement;
use Carbon\Carbon;

class GppbExportService
{
    /**
     * Generates GPPB RA 12009 compliance register output arrays.
     */
    public function generateAnnualRegister(int $year): array
    {
        $procurements = Procurement::with(['endUserOffice', 'mode', 'items', 'routes'])
            ->whereYear('created_at', $year)
            ->get();

        return $procurements->map(function ($p) {
            $firstRoute = $p->routes->sortBy('created_at')->first();
            $lastRoute = $p->routes->sortByDesc('created_at')->first();

            $totalLifecycleDays = $p->created_at->diffInDays($lastRoute?->created_at ?? Carbon::now());

            return [
                'tracking_number' => $p->tracking_number,
                'project_title' => $p->title,
                'end_user_office' => $p->endUserOffice->name ?? 'N/A',
                'procurement_mode' => $p->mode->name ?? 'N/A',
                'abc_amount' => $p->abc_amount,
                'status' => $p->status,
                'date_initiated' => $p->created_at->format('Y-m-d'),
                'total_elapsed_days' => $totalLifecycleDays,
                'ra12009_compliant' => $totalLifecycleDays <= 60 ? 'COMPLIANT (<=60 Days)' : 'BREACHED (>60 Days)',
            ];
        })->toArray();
    }
}
