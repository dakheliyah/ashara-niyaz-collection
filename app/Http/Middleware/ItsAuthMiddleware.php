<?php

namespace App\Http\Middleware;

use App\Models\Admin;
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
        error_log("Decrypted Token: $decryptedToken");
        error_log("Token: $token");

        // Find the admin by its_id (decrypted from token)
        $admin = Admin::with('role')->where('its_id', $decryptedToken)->first();

        if (!$admin) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        // Attach the admin and role to the request attributes
        $request->attributes->add([
            'admin' => $admin,
            'its_id' => $admin->its_id,
            'role' => $admin->role->name
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
