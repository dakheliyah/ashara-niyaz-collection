<?php

namespace App\Http\Middleware;

use App\Http\AuthenticatedUser;
use App\Models\Admin;
use App\Models\Mumineen;
use App\Services\ItsTokenCipher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $resolvedBy = 'none';
        $itsId = $this->resolveItsId($request, $resolvedBy);
        if ($itsId === null) {
            if ((bool) config('its_onelogin.auth_debug', false)) {
                Log::warning('ITS auth failed: no valid credential found', [
                    'host' => $request->getHost(),
                    'path' => $request->path(),
                    'resolved_by' => $resolvedBy,
                    'token_header_present' => $request->header('Token') !== null,
                    'token_header_length' => strlen((string) $request->header('Token', '')),
                    'cookies_present' => [
                        'user' => $request->cookie('user') !== null,
                        'its_no' => $request->cookie('its_no') !== null,
                        'its_user_data' => $request->cookie('its_user_data') !== null,
                    ],
                ]);
            }
            return response()->json(['message' => 'Token header is required.'], 401);
        }

        if ((bool) config('its_onelogin.auth_debug', false)) {
            Log::info('ITS auth credential resolved', [
                'host' => $request->getHost(),
                'path' => $request->path(),
                'resolved_by' => $resolvedBy,
                'its_id_tail' => substr($itsId, -4),
            ]);
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

    private function resolveItsId(Request $request, ?string &$resolvedBy = null): ?string
    {
        // 1) Existing app contract: encrypted Token header
        $tokenHeader = $request->header('Token');
        if (is_string($tokenHeader) && $tokenHeader !== '') {
            $decryptedHeader = ItsTokenCipher::decrypt(urldecode($tokenHeader));
            if ($this->isValidItsId($decryptedHeader)) {
                $resolvedBy = 'token_header';
                return $decryptedHeader;
            }
        }

        // 2) WordPress OneLogin cross-subdomain cookie: plain 8-digit user id
        $userCookie = $request->cookie('user');
        if ($this->isValidItsId($userCookie)) {
            $resolvedBy = 'user_cookie';
            return $userCookie;
        }

        // 3) Fallback: app-encrypted its_no cookie (legacy flow)
        $itsNoCookie = $request->cookie('its_no');
        if (is_string($itsNoCookie) && $itsNoCookie !== '') {
            $decryptedCookie = ItsTokenCipher::decrypt(urldecode($itsNoCookie));
            if ($this->isValidItsId($decryptedCookie)) {
                $resolvedBy = 'its_no_cookie';
                return $decryptedCookie;
            }
        }

        // 4) Fallback: ITS user payload cookie from WP plugin
        $itsUserData = $request->cookie('its_user_data');
        if (is_string($itsUserData) && $itsUserData !== '') {
            $decoded = json_decode(urldecode($itsUserData), true);
            $itsNo = is_array($decoded) ? ($decoded['its_no'] ?? null) : null;
            if ($this->isValidItsId($itsNo)) {
                $resolvedBy = 'its_user_data_cookie';
                return $itsNo;
            }
        }

        $resolvedBy = 'none';
        return null;
    }

    private function isValidItsId(mixed $itsId): bool
    {
        return is_string($itsId) && preg_match('/^\d{8}$/', $itsId) === 1;
    }
}
