<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 *
 * Verifies that the authenticated user has one of the allowed roles.
 * Usage in routes: ->middleware('role:admin,caisse_outils')
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  One or more allowed roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Non authentifié.',
            ], 401);
        }

        if (empty($roles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'error' => 'Accès refusé. Vous ne disposez pas des droits nécessaires pour cette action.',
            'required_roles' => $roles,
            'your_role' => $user->role,
        ], 403);
    }
}
