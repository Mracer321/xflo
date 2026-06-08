<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('role:super_admin,leads_admin')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not authenticated → send to login.
        if (! $user) {
            return redirect()->route('login');
        }

        // Authenticated but role not permitted → 403.
        if (! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
