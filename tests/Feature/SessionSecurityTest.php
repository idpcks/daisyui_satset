<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_is_logged_out_and_the_session_is_invalidated(): void
    {
        $user = User::factory()->inactive()->create();
        $sessionId = 'existing-session-id';

        $this->actingAs($user)
            ->withSession(['leftover' => 'data'])
            ->withCookie((string) config('session.cookie'), $sessionId)
            ->get('/dashboard')
            ->assertRedirect('/auth/signin')
            ->assertSessionHas('error', 'Akun Anda telah dinonaktifkan.');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNull(session('leftover'));
    }

    public function test_active_user_keeps_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['leftover' => 'data'])
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('data', session('leftover'));
    }

    /**
     * Di produksi, PASSKEYS_USER_HANDLE_SECRET wajib diisi. Tanpa itu secret
     * jatuh ke APP_KEY: rotasi APP_KEY memutus semua passkey pengguna.
     */
    public function test_production_requires_an_explicit_passkey_user_handle_secret(): void
    {
        config([
            'app.env' => 'production',
            'fortify.passkeys.user_handle_secret' => config('app.key'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PASSKEYS_USER_HANDLE_SECRET');

        (new AppServiceProvider($this->app))->boot();
    }

    public function test_local_environment_allows_the_app_key_fallback(): void
    {
        config([
            'app.env' => 'local',
            'fortify.passkeys.user_handle_secret' => config('app.key'),
        ]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertTrue(true);
    }

    public function test_session_cookie_is_forced_secure_when_app_url_uses_https(): void
    {
        config(['app.url' => 'https://admin.example.test']);

        // boot() dijalankan ulang karena perubahan APP_URL setelah aplikasi
        // hidup tidak memicu ulang provider.
        (new AppServiceProvider($this->app))->boot();

        $this->assertTrue(config('session.secure'));
    }

    public function test_session_cookie_is_not_forced_when_app_url_uses_http(): void
    {
        config(['app.url' => 'http://localhost:8000']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertFalse((bool) config('session.secure'));
    }
}
