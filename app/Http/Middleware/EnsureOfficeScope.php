<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Procurement\Procurement;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Admins always have access
        if ($user->isAdmin()) {
            return $next($request);
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
            $hasOriginAccess = in_array(
                $procurement->end_user_office_id,
                $userOfficeIds
            );

            $hasPhaseAccess = $procurement->mode?->phases?->contains(fn ($phase) => $user->canActOnPhase($phase)) ?? false;

            if (!$hasOriginAccess && !$hasPhaseAccess) {
                abort(
                    403,
                    'Forbidden: Access denied to procurement records outside your assigned office scope.'
                );
            }
        }

        return $next($request);
    }
}
