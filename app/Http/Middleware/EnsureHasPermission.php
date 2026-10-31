<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string $permissionName): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Admins always have permission
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any role assigned to the required permission
        $hasPermission = $user->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();

        if (!$hasPermission) {
            abort(403, "Forbidden: You lack the required permission [{$permissionName}].");
        }

        return $next($request);
    }
}
