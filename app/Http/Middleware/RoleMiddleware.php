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
        // This assumes that ItsAuthMiddleware has already authenticated the user
        // and attached an AuthenticatedUser object to the request.
        $user = $request->attributes->get('admin');

        if (!$user || !property_exists($user, 'role')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $userRole = $user->role;

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
