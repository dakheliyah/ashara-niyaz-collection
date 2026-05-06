<?php

namespace App\Http\Middleware;

use App\Http\AuthenticatedUser;
use App\Models\Admin;
use App\Models\Mumineen;
use App\Services\ItsTokenCipher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ItsAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('Token')) {
            return response()->json(['message' => 'Token header is required.'], 401);
        }

        $token = $request->header('Token');

        if (empty($token)) {
            return response()->json(['message' => 'Token header cannot be empty.'], 401);
        }

        // The token value might be URL-encoded.
        $token = urldecode($token);

        $decryptedToken = ItsTokenCipher::decrypt($token);

        if ($decryptedToken === null) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        // Find the user in the Admin table first
        $user = Admin::where('its_id', $decryptedToken)->first();

        // If not found in Admin, check the Mumineen table
        if (! $user) {
            $user = Mumineen::where('its_id', $decryptedToken)->first();
        }

        if ($user) {
            Auth::setUser($user);

            // Use the AuthenticatedUser class to standardize the user object
            $request->attributes->add(['admin' => new AuthenticatedUser($user)]);

            return $next($request);
        }
        error_log('Token: '.$token);
        error_log('Decrypted Token: '.$decryptedToken);
        error_log('User: '.$user);

        // If not found in either table, the token is invalid
        return response()->json(['message' => 'User not found.'], 401);
    }
}
