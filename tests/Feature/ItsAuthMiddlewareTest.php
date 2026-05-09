<?php

namespace Tests\Feature;

use App\Http\Middleware\ItsAuthMiddleware;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;

class ItsAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticates_using_wordpress_user_cookie(): void
    {
        DB::table('roles')->insert([
            'name' => 'admin',
            'label' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('roles')->where('name', 'admin')->value('id');

        Admin::query()->create([
            'its_id' => '12345678',
            'role_id' => $roleId,
            'created_by' => 'system',
            'status' => 'active',
        ]);

        $request = Request::create('/api/me', 'GET');
        $request->cookies->set('user', '12345678');

        $middleware = new ItsAuthMiddleware;
        $response = $middleware->handle($request, function (Request $request): JsonResponse {
            $user = $request->attributes->get('admin');

            return response()->json([
                'its_id' => $user->its_id,
                'role' => $user->role,
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"its_id":"12345678","role":"admin"}', $response->getContent());
    }

    public function test_rejects_request_without_any_auth_credential(): void
    {
        $request = Request::create('/api/me', 'GET');
        $middleware = new ItsAuthMiddleware;
        $response = $middleware->handle($request, function (): JsonResponse {
            return response()->json(['ok' => true]);
        });

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('{"message":"Token header is required."}', $response->getContent());
    }
}

