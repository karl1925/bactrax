<?php

namespace App\Http\Controllers;

use App\Models\Procurement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $userOfficeIds = $user->offices
            ->pluck('id')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | 1. Base Query - Office Boundary
        |--------------------------------------------------------------------------
        |
        | Central roles can see everything.
        |
        | Non-central users can see procurements when:
        | - Their office is the end-user/requesting office, OR
        | - The procurement is currently routed to one of their offices.
        |
        */

        $query = Procurement::query()
            ->when(!$user->isCentralRole(), function ($q) use ($userOfficeIds) {

                $q->where(function ($subQ) use ($userOfficeIds) {

                    $subQ->whereIn(
                        'end_user_office_id',
                        $userOfficeIds
                    )

                        ->orWhereHas('routes', function ($routeQ) use ($userOfficeIds) {

                            $routeQ->whereIn(
                                'to_office_id',
                                $userOfficeIds
                            );
                        });
                });
            });

        /*
        |--------------------------------------------------------------------------
        | 2. KPI Metrics
        |--------------------------------------------------------------------------
        */

        $totalActive = (clone $query)
            ->whereIn('status', [
                'DRAFT',
                'ROUTED',
                'RETURNED',
            ])
            ->count();

        $totalCompleted = (clone $query)
            ->where('status', 'COMPLETED')
            ->count();

        $totalReturned = (clone $query)
            ->where('status', 'RETURNED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. SLA Violations
        |--------------------------------------------------------------------------
        */

        $slaViolations = (clone $query)
            ->where('status', 'ROUTED')
            ->with([
                'currentPhase',
                'currentRoute',
            ])
            ->get()
            ->filter(function ($procurement) {

                $phaseStartDate =
                    $procurement->currentRoute?->created_at
                    ?? $procurement->created_at;

                if (!$phaseStartDate) {
                    return false;
                }

                $elapsedDays = $phaseStartDate->diffInDays(
                    Carbon::now()
                );

                $allowableDays =
                    $procurement->currentPhase?->allowable_days
                    ?? 7;

                return $elapsedDays > $allowableDays;
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Pending Action Register
        |--------------------------------------------------------------------------
        */

        $pendingActions = (clone $query)
            ->whereIn('status', [
                'ROUTED',
                'RETURNED',
            ])
            ->with([
                'endUserOffice',
                'mode',
                'currentPhase.roles',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function ($item) use ($user) {

                if (!$item->currentPhase) {
                    return false;
                }

                return $user->canActOnPhase(
                    $item->currentPhase
                );
            })
            ->take(5);

        /*
        |--------------------------------------------------------------------------
        | 5. Recent Activity
        |--------------------------------------------------------------------------
        */

        $recentProcurements = (clone $query)
            ->with([
                'endUserOffice',
                'mode',
            ])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view('dashboard.index', compact(
            'totalActive',
            'totalCompleted',
            'totalReturned',
            'slaViolations',
            'pendingActions',
            'recentProcurements'
        ));
    }
}
