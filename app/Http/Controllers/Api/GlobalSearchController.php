<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\Procurement;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = mb_substr(trim((string) $request->input('q', $request->input('search', ''))), 0, 120);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->hasPermission('procurement.view')), 403);
        $isCentralUser = $user->isAdmin() || $user->isCentralRole();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

        $results = Procurement::with(['endUserOffice', 'mode'])
            ->where(function ($q) use ($like) {
                $q->where('pr_number', 'LIKE', $like)
                    ->orWhere('purpose', 'LIKE', $like)
                    ->orWhere('status', 'LIKE', $like)
                    ->orWhereHas('endUserOffice', function ($oq) use ($like) {
                        $oq->where('code', 'LIKE', $like)
                            ->orWhere('name', 'LIKE', $like);
                    })
                    ->orWhereHas('mode', function ($mq) use ($like) {
                        $mq->where('name', 'LIKE', $like)
                            ->orWhere('code', 'LIKE', $like);
                    });
            })
            ->when(!$isCentralUser, function ($q) use ($user) {
                $userOfficeIds = $user->offices->pluck('id')->toArray();
                $userRoleIds = $user->roles()->pluck('roles.id');
                $q->where(function ($scope) use ($userOfficeIds, $userRoleIds) {
                    $scope->whereIn('end_user_office_id', $userOfficeIds)
                        ->orWhereHas('mode.phases.roles', fn ($roles) => $roles->whereIn('roles.id', $userRoleIds)->where('procurement_mode_phase_roles.can_act', true));
                });
            })
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'pr_number' => $p->pr_number,
                    'title' => $p->purpose,
                    'office' => $p->endUserOffice->code ?? 'N/A',
                    'mode' => $p->mode->name ?? 'N/A',
                    'abc' => '₱ ' . number_format($p->abc_amount, 2),
                    'status' => $p->status,
                    'url' => route('procurements.show', $p->id),
                ];
            });

        return response()->json($results);
    }
}
