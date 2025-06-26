<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    /**
     * Capture the token from the URL, save it in a cookie, and redirect.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleTokenLogin(Request $request)
    {
        if (!$request->has('token')) {
            // If no token is present, redirect to the login page with an error.
            return redirect('/login')->with('error', 'Authentication token is missing.');
        }

        $token = $request->input('token');

        // Create a new cookie with the token.
        // The cookie will be httpOnly for security, and will expire in 24 hours (1440 minutes).
        $cookie = Cookie::make('its_no', $token, 1440, null, null, false, true);

        // Redirect to the dashboard with the cookie.
        return redirect('/dashboard')->withCookie($cookie);
    }
}

