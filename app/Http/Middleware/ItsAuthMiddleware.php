<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Mumineen;
use Closure;
use Illuminate\Http\Request;
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
        if (!$request->hasHeader('Token')) {
            return response()->json(['message' => 'Token header is required.'], 401);
        }

        $token = $request->header('Token');

        if (empty($token)) {
            return response()->json(['message' => 'Token header cannot be empty.'], 401);
        }

        // The token value might be URL-encoded.
        $token = urldecode($token);

        $decryptedToken = $this->decrypt($token);
        error_log("Decrypted Token from middleware: $decryptedToken");

        // Find the user in the Admin table first
        $user = Admin::with('role')->where('its_id', $decryptedToken)->first();

        if (!$user) {
            // If not found in Admin, check the Mumineen table
            $mumin = Mumineen::where('its_id', $decryptedToken)->first();

            if ($mumin) {
                // If found in Mumineen, create a temporary user object with a 'donor' role
                $user = new Admin(); // Use Admin model for structure
                $user->its_id = $mumin->its_id;
                $user->name = $mumin->name; // Assuming a 'name' attribute on Mumineen
                $user->setRelation('role', (object)['name' => 'donor']);
                error_log("User found in Mumineen table: " . $mumin->its_id);
            } else {
                // If not found in either table, the token is invalid
                error_log("User not found in Admin or Mumineen table for ITS: " . $decryptedToken);
                return response()->json(['message' => 'Invalid token.'], 401);
            }
        } else {
            error_log("User found in Admin table: " . $user->its_id);
        }

        // Attach the user object and their role name to the request
        $request->attributes->add([
            'admin' => $user, // Keep 'admin' key for consistency
            'role' => $user->role->name ?? null,
        ]);

        return $next($request);
    }

    /**
     * Decrypt data from secure storage using OpenSSL.
     */
    private function decrypt($encrypted)
    {
        if (empty($encrypted)) {
            return null;
        }
        
        $key = env('ITS_ENCRYPTION_KEY');
        $decoded = base64_decode($encrypted);
        
        if ($decoded === false) {
            return null;
        }
        
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        
        if (strlen($decoded) <= $ivLength) {
            return null;
        }
        
        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);
        
        $decrypted = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        return $decrypted === false ? null : $decrypted;
    }
}
