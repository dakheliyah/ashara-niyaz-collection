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
        $itsId = $this->resolveItsId($request);
        if ($itsId === null) {
            return response()->json(['message' => 'Token header is required.'], 401);
        }

        // Find the user in the Admin table first
        $user = Admin::where('its_id', $itsId)->first();

        // If not found in Admin, check the Mumineen table
        if (! $user) {
            $user = Mumineen::where('its_id', $itsId)->first();
        }

        if ($user) {
            Auth::setUser($user);

            // Use the AuthenticatedUser class to standardize the user object
            $request->attributes->add(['admin' => new AuthenticatedUser($user)]);

            return $next($request);
        }
        $token = $request->header('Token');
        $cookieUser = $request->cookie('user');
        error_log('Token: '.($token ?? ''));
        error_log('Cookie user: '.($cookieUser ?? ''));
        error_log('User: '.$user);

        // If not found in either table, the token is invalid
        return response()->json(['message' => 'User not found.'], 401);
    }

    private function resolveItsId(Request $request): ?string
    {
        // 1) Existing app contract: encrypted Token header
        $tokenHeader = $request->header('Token');
        if (is_string($tokenHeader) && $tokenHeader !== '') {
            $decryptedHeader = ItsTokenCipher::decrypt(urldecode($tokenHeader));
            if ($this->isValidItsId($decryptedHeader)) {
                return $decryptedHeader;
            }
        }

        // 2) WordPress OneLogin cross-subdomain cookie: plain 8-digit user id
        $userCookie = $request->cookie('user');
        if ($this->isValidItsId($userCookie)) {
            return $userCookie;
        }

        // 3) Fallback: app-encrypted its_no cookie (legacy flow)
        $itsNoCookie = $request->cookie('its_no');
        if (is_string($itsNoCookie) && $itsNoCookie !== '') {
            $decryptedCookie = ItsTokenCipher::decrypt(urldecode($itsNoCookie));
            if ($this->isValidItsId($decryptedCookie)) {
                return $decryptedCookie;
            }
        }

        // 4) Fallback: ITS user payload cookie from WP plugin
        $itsUserData = $request->cookie('its_user_data');
        if (is_string($itsUserData) && $itsUserData !== '') {
            $decoded = json_decode(urldecode($itsUserData), true);
            $itsNo = is_array($decoded) ? ($decoded['its_no'] ?? null) : null;
            if ($this->isValidItsId($itsNo)) {
                return $itsNo;
            }
        }

        return null;
    }

    private function isValidItsId(mixed $itsId): bool
    {
        return is_string($itsId) && preg_match('/^\d{8}$/', $itsId) === 1;
    }
}
