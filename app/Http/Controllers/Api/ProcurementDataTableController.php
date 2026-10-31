<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\Procurement;
use Illuminate\Http\JsonResponse;

class ProcurementDataTableController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user(), 401);
        $request->validate([
            'draw' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'start' => ['sometimes', 'integer', 'min:0', 'max:10000000'],
            'length' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search.value' => ['sometimes', 'nullable', 'string', 'max:120'],
            'office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
            'mode_id' => ['sometimes', 'nullable', 'integer', 'exists:procurement_modes,id'],
            'status' => ['sometimes', 'nullable', 'in:DRAFT,ROUTING,RETURNED,REJECTED,COMPLETED,CANCELLED,ARCHIVED'],
            'order.0.column' => ['sometimes', 'integer', 'between:0,6'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
        ]);

        $user = $request->user();
        abort_unless($user->isAdmin() || $user->hasPermission('procurement.view'), 403);
        $draw = max(0, min(1000000, (int) $request->input('draw', 1)));
        $start = max(0, min(10000000, (int) $request->input('start', 0)));
        $length = max(1, min(100, (int) $request->input('length', 10)));
        $searchValue = mb_substr(trim((string) $request->input('search.value', '')), 0, 120);

        $query = Procurement::with(['endUserOffice', 'mode', 'currentPhase']);

        // 1. Office Scope Boundary (Originating Office OR Currently Routed Office OR Central Role)
        if (!$user->isCentralRole()) {
            $userOfficeIds = $user->offices->pluck('id')->toArray();
            $userRoleIds = $user->roles()->pluck('roles.id');

            $query->where(function ($q) use ($userOfficeIds, $userRoleIds) {
                $q->whereIn('end_user_office_id', $userOfficeIds)
                ->orWhereHas('mode.phases.roles', fn($rq) => $rq->whereIn('roles.id', $userRoleIds)->where('procurement_mode_phase_roles.can_act', true));
            });
        }
        $totalRecords = (clone $query)->count();

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
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchValue).'%';
            $query->where(function ($q) use ($like) {
                $q->where('pr_number', 'LIKE', $like)
                    ->orWhere('purpose', 'LIKE', $like)
                    ->orWhere('status', 'LIKE', "%{$searchValue}%") // Enables searching by status text (e.g., DRAFT, ROUTING)
                    ->orWhereHas('endUserOffice', function ($oq) use ($like) {
                        $oq->where('code', 'LIKE', $like)
                            ->orWhere('name', 'LIKE', $like);
                    })
                    ->orWhereHas('mode', function ($mq) use ($like) {
                        $mq->where('name', 'LIKE', $like)
                            ->orWhere('code', 'LIKE', $like);
                    });
            });
        }

        $filteredRecords = (clone $query)->count();

        // 4. Column Ordering
        $orderColumnIndex = $request->input('order.0.column');
        $orderDirection = $request->input('order.0.dir', 'desc');
        $columns = [
            0 => 'pr_number',
            1 => 'purpose',
            2 => 'end_user_office_id',
            3 => 'procurement_mode_id',
            4 => 'fund_cluster',
            5 => 'abc_amount',
            6 => 'status',
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], strtolower((string) $orderDirection) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $data = $query->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($p) {
                return [
                    'pr_number' => '<span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">' . e($p->pr_number) . '</span>',
                    'purpose' => e($p->purpose),
                    'office_code' => e($p->endUserOffice->code ?? 'N/A'),
                    'mode_code' => e($p->mode->code ?? 'N/A'),
                    'procurement_mode_name' => e($p->mode->name ?? 'N/A'),
                    'fund_cluster' => e($p->fund_cluster ?? 'N/A'),
                    'abc_amount' => '₱ ' . number_format($p->abc_amount, 2),
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
