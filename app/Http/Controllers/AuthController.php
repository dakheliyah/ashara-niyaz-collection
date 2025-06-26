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
        if (!$request->has('its_no')) {
            // If no token is present, redirect to the login page with an error.
            return redirect('/login')->with('error', 'Authentication token is missing.');
        }

        $itsNo = $request->input('its_no');

        // Create a new cookie with the token.
        // The cookie will be httpOnly for security, and will expire in 24 hours (1440 minutes).
        $cookie = Cookie::make('its_no', $itsNo, 1440, null, null, false, true);

        // Redirect to the homepage with the cookie.
        return redirect('/')->withCookie($cookie);
    }
}

