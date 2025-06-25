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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // This assumes that a preceding middleware (ItsAuthMiddleware) has already
        // authenticated the user and attached their role to the request.
        $userRole = $request->attributes->get('role');

        if (!$userRole) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Implement hierarchical permissions: admins can access collector features
        $hasPermission = false;
        
        if ($role === 'admin' && $userRole === 'admin') {
            $hasPermission = true;
        } elseif ($role === 'collector' && in_array($userRole, ['admin', 'collector'])) {
            $hasPermission = true;
        } elseif ($userRole === $role) {
            $hasPermission = true;
        }

        if (!$hasPermission) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return $next($request);
    }
}
