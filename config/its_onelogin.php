<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WordPress ITS OneLogin handoff endpoint
    |--------------------------------------------------------------------------
    |
    | Full URL to the handoff REST route (no query string), e.g.:
    | https://example.org/wp-json/its-onelogin/v1/handoff
    |
    | Laravel calls this server-to-server with ?token=... to exchange a
    | one-time onlgn_token for JSON user fields (its_no, name, ...).
    |
    */

    'handoff_url' => env('ITS_ONELOGIN_HANDOFF_URL'),

    /*
    |--------------------------------------------------------------------------
    | App token encryption (cookie / Token header)
    |--------------------------------------------------------------------------
    |
    | Same key used by ItsAuthMiddleware to decrypt the its_no cookie.
    |
    */

    'encryption_key' => env('ITS_ENCRYPTION_KEY'),

];
