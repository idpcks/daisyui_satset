<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    /**
     * Tanpa proxy tepercaya, X-Forwarded-For dari klien harus diabaikan.
     * Kalau dipercaya begitu saja, penyerang bisa menyetel IP palsu di
     * login_histories, user_sessions, dan audit impersonation.
     */
    public function test_forwarded_for_is_ignored_without_a_trusted_proxy(): void
    {
        Route::middleware('web')->get('/_test/client-ip', fn (Request $request): array => [
            'ip' => $request->ip(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
            ->get('/_test/client-ip')
            ->assertOk()
            ->assertJson(['ip' => '203.0.113.10']);
    }

    /**
     * Dengan proxy tepercaya yang benar, IP asli dari X-Forwarded-For dipakai.
     */
    public function test_forwarded_for_is_used_when_the_proxy_is_trusted(): void
    {
        TrustProxies::at(['203.0.113.10']);

        Route::middleware('web')->get('/_test/client-ip', fn (Request $request): array => [
            'ip' => $request->ip(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
            ->get('/_test/client-ip')
            ->assertOk()
            ->assertJson(['ip' => '198.51.100.99']);
    }

    /**
     * Riwayat login memakai IP hasil resolusi yang sama dengan $request->ip().
     */
    public function test_login_history_uses_the_resolved_client_ip(): void
    {
        TrustProxies::at(['203.0.113.10']);

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
            ->post('/auth/signin', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'ip_address' => '198.51.100.99',
        ]);
    }
}
