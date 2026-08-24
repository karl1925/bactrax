<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement;
use Illuminate\Http\JsonResponse;

class ProcurementDataTableController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $draw = intval($request->input('draw', 1));
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));
        $searchValue = trim($request->input('search.value', ''));

        $query = Procurement::with(['endUserOffice', 'mode', 'currentPhase']);

        // 1. Office Scope Boundary (Originating Office OR Currently Routed Office OR Central Role)
        if (!$user->isCentralRole()) {
            $userOfficeIds = $user->offices->pluck('id')->toArray();

            $query->where(function ($q) use ($userOfficeIds) {
                $q->whereIn('end_user_office_id', $userOfficeIds)
                    ->orWhereHas('latestRoute', function ($rq) use ($userOfficeIds) {
                        $rq->whereIn('to_office_id', $userOfficeIds);
                    });
            });
        }

        // 2. Dropdown Filters
        if ($request->filled('office_id')) {
            $query->where('end_user_office_id', $request->input('office_id'));
        }
        if ($request->filled('mode_id')) {
            $query->where('procurement_mode_id', $request->input('mode_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 3. Global Text Search (Includes status column)
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('tracking_number', 'LIKE', "%{$searchValue}%")
                    ->orWhere('title', 'LIKE', "%{$searchValue}%")
                    ->orWhere('status', 'LIKE', "%{$searchValue}%") // Enables searching by status text (e.g., DRAFT, ROUTED)
                    ->orWhereHas('endUserOffice', function ($oq) use ($searchValue) {
                        $oq->where('code', 'LIKE', "%{$searchValue}%")
                            ->orWhere('name', 'LIKE', "%{$searchValue}%");
                    })
                    ->orWhereHas('mode', function ($mq) use ($searchValue) {
                        $mq->where('name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('code', 'LIKE', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = Procurement::count();
        $filteredRecords = (clone $query)->count();

        // 4. Column Ordering
        $orderColumnIndex = $request->input('order.0.column');
        $orderDirection = $request->input('order.0.dir', 'desc');
        $columns = [
            0 => 'tracking_number',
            1 => 'title',
            2 => 'end_user_office_id',
            3 => 'procurement_mode_id',
            4 => 'abc_amount',
            5 => 'status',
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        $data = $query->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($p) {
                return [
                    'tracking_number' => '<span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">' . e($p->tracking_number) . '</span>',
                    'title' => e($p->title),
                    'office_code' => e($p->endUserOffice->code ?? 'N/A'),
                    'mode_name' => e($p->mode->name ?? 'N/A'),
                    'abc_formatted' => '₱ ' . number_format($p->abc_amount, 2),
                    'status' => $p->status,
                    'actions' => '<a href="' . route('procurements.show', $p->id) . '" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">View</a>',
                ];
            });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
