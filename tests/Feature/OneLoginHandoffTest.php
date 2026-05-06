<?php

namespace Tests\Feature;

use App\Services\ItsTokenCipher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneLoginHandoffTest extends TestCase
{
    public function test_handoff_exchanges_token_and_sets_cookie(): void
    {
        config([
            'its_onelogin.handoff_url' => 'https://wp.example/wp-json/its-onelogin/v1/handoff',
            'its_onelogin.encryption_key' => str_repeat('a', 64),
        ]);

        Http::fake([
            'https://wp.example/wp-json/its-onelogin/v1/handoff*' => Http::response([
                'its_no' => '12345678',
                'name' => 'Test User',
            ], 200),
        ]);

        $token = str_repeat('a', 64);

        $response = $this->get('/auth/onlgn-handoff?onlgn_token='.$token.'&return_path='.rawurlencode('/admin'));

        $response->assertRedirect('/admin');
        $plain = ItsTokenCipher::decrypt($response->getCookie('its_no', false)->getValue());
        $this->assertSame('12345678', $plain);
    }

    public function test_handoff_rejects_invalid_token_format(): void
    {
        config([
            'its_onelogin.handoff_url' => 'https://wp.example/wp-json/its-onelogin/v1/handoff',
        ]);

        $this->get('/auth/onlgn-handoff?onlgn_token=short')
            ->assertSessionHasErrors('onlgn_token');
    }
}
