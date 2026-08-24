<?php

namespace App\Http\Middleware;

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Procurement;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Central processing roles bypass single-office restrictions
        if ($user->isCentralRole()) {
            return $next($request);
        }

        $procurementId = $request->route('procurement')
            ?? $request->route('id')
            ?? $request->input('procurement_id');

        if ($procurementId) {
            $procurement = $procurementId instanceof Procurement
                ? $procurementId
                : Procurement::findOrFail($procurementId);

            $userOfficeIds = $user->offices->pluck('id')->toArray();
            $activeRouteOfficeId = $procurement->latestRoute?->to_office_id;

            $hasOriginAccess = in_array($procurement->end_user_office_id, $userOfficeIds);
            $hasRouteAccess = $activeRouteOfficeId && in_array($activeRouteOfficeId, $userOfficeIds);

            if (!$hasOriginAccess && !$hasRouteAccess) {
                abort(403, 'Forbidden: Access denied to procurement records outside your assigned office scope.');
            }
        }

        return $next($request);
    }
}
