<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPrivilege
{
    public function handle(Request $request, Closure $next, string $privilegeCode): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Admins always have all privileges
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any role assigned to the required workflow privilege
        $hasPrivilege = $user->roles()
            ->whereHas('privileges', function ($query) use ($privilegeCode) {
                $query->where('code', $privilegeCode);
            })
            ->exists();

        if (!$hasPrivilege) {
            abort(403, "Forbidden: You lack the required workflow privilege [{$privilegeCode}].");
        }

        return $next($request);
    }
}
