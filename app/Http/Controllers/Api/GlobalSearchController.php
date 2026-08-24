<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->input('q', $request->input('search', '')));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $user = $request->user();
        $isCentralUser = $user->isAdmin() || $user->roles()->whereIn('name', ['bac_secretariat', 'hope'])->exists();

        $results = Procurement::with(['endUserOffice', 'mode'])
            ->where(function ($q) use ($query) {
                $q->where('tracking_number', 'LIKE', "%{$query}%")
                    ->orWhere('title', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->orWhereHas('endUserOffice', function ($oq) use ($query) {
                        $oq->where('code', 'LIKE', "%{$query}%")
                            ->orWhere('name', 'LIKE', "%{$query}%");
                    })
                    ->orWhereHas('mode', function ($mq) use ($query) {
                        $mq->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('code', 'LIKE', "%{$query}%");
                    });
            })
            ->when(!$isCentralUser, function ($q) use ($user) {
                $userOfficeIds = $user->offices->pluck('id')->toArray();
                $q->whereIn('end_user_office_id', $userOfficeIds);
            })
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'tracking_number' => $p->tracking_number,
                    'title' => $p->title,
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
