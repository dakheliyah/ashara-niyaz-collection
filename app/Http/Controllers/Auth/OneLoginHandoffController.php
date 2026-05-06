<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ItsTokenCipher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneLoginHandoffController extends Controller
{
    /**
     * Exchange a one-time WordPress onlgn_token for an app session cookie, then redirect.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'onlgn_token' => ['required', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            'return_path' => ['nullable', 'string', 'max:2048'],
        ]);

        $handoffUrl = config('its_onelogin.handoff_url');
        if (empty($handoffUrl)) {
            Log::error('ITS OneLogin handoff: ITS_ONELOGIN_HANDOFF_URL is not configured.');

            return redirect('/?handoff_error=config');
        }

        try {
            $httpResponse = Http::timeout(15)
                ->acceptJson()
                ->get($handoffUrl, ['token' => $validated['onlgn_token']]);
        } catch (\Throwable $e) {
            Log::warning('ITS OneLogin handoff: HTTP request failed.', [
                'message' => $e->getMessage(),
            ]);

            return redirect('/?handoff_error=unavailable');
        }

        if (! $httpResponse->successful()) {
            Log::notice('ITS OneLogin handoff: upstream rejected token.', [
                'status' => $httpResponse->status(),
            ]);

            return redirect('/?handoff_error=token');
        }

        $payload = $httpResponse->json();
        $itsNo = is_array($payload) ? ($payload['its_no'] ?? null) : null;
        if (! is_string($itsNo) || $itsNo === '' || ! preg_match('/^\d{8}$/', $itsNo)) {
            Log::notice('ITS OneLogin handoff: missing or invalid its_no in response.');

            return redirect('/?handoff_error=invalid_user');
        }

        try {
            $encryptedCookie = ItsTokenCipher::encrypt($itsNo);
        } catch (\Throwable $e) {
            Log::error('ITS OneLogin handoff: encrypt failed.', ['message' => $e->getMessage()]);

            return redirect('/?handoff_error=config');
        }

        $target = $this->safeReturnPath($validated['return_path'] ?? null);

        return redirect($target)->cookie(
            'its_no',
            $encryptedCookie,
            60 * 24,
            '/',
            null,
            $request->secure(),
            false,
            false,
            'lax'
        );
    }

    /**
     * Only same-origin relative paths (optionally with query string); no protocol-relative or absolute URLs.
     */
    private function safeReturnPath(?string $returnPath): string
    {
        if ($returnPath === null || $returnPath === '') {
            return '/';
        }

        $returnPath = urldecode($returnPath);
        $returnPath = trim($returnPath);

        if ($returnPath === '' || str_starts_with($returnPath, '//') || str_contains($returnPath, '://')) {
            return '/';
        }

        if (! str_starts_with($returnPath, '/')) {
            return '/'.$returnPath;
        }

        return $returnPath;
    }
}
