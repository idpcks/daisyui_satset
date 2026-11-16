<?php

namespace Tests\Feature;

use App\Features\Passkeys as PasskeysFeature;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passkeys\Passkeys;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class PasskeyLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_is_allowed_to_sign_in_with_a_passkey(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(
            Passkeys::allowsLogin($this->loginRequest(), $this->passkeyFor($user))
        );
    }

    public function test_inactive_user_is_rejected_before_the_passkey_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse(
            Passkeys::allowsLogin($this->loginRequest(), $this->passkeyFor($user))
        );
    }

    public function test_passkey_of_a_soft_deleted_user_is_rejected_instead_of_erroring(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkeyFor($user);

        $user->delete();

        // Baris user sudah soft-deleted, jadi relasi passkey ini null.
        $passkey->unsetRelation('user');
        $this->assertNull($passkey->user);

        $this->assertFalse(Passkeys::allowsLogin($this->loginRequest(), $passkey));
    }

    public function test_login_page_receives_the_passkeys_feature_flag(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('features.passkeys', true));
    }

    public function test_passkey_routes_are_unavailable_when_the_feature_is_disabled(): void
    {
        Feature::deactivate(PasskeysFeature::class);
        Feature::flushCache();

        $this->get(route('passkey.login-options'))->assertNotFound();
        $this->post(route('passkey.login'))->assertNotFound();

        $this->get(route('passkey.registration-options'))->assertNotFound();
    }

    public function test_login_page_hides_passkeys_when_the_feature_is_disabled(): void
    {
        Feature::deactivate(PasskeysFeature::class);
        Feature::flushCache();

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('features.passkeys', false));
    }

    public function test_passkey_throttle_key_cannot_be_reset_by_dropping_the_session_cookie(): void
    {
        $limiter = RateLimiter::limiter('passkeys');

        $this->assertNotNull($limiter, 'Rate limiter passkey belum terdaftar.');

        $keyFor = function (string $sessionId, string $ip) use ($limiter): string {
            $request = Request::create('/passkeys/login', 'POST', server: ['REMOTE_ADDR' => $ip]);
            $request->setLaravelSession(
                tap(app('session.store'), fn ($session) => $session->setId($sessionId))
            );

            return $limiter($request)->key;
        };

        // ID sesi baru (mis. setelah cookie dihapus) tidak membuka jatah baru.
        $this->assertSame(
            $keyFor('sesi-lama', '10.0.0.1'),
            $keyFor('sesi-baru', '10.0.0.1'),
        );

        // Pembatasan tetap terpisah per alamat IP.
        $this->assertNotSame(
            $keyFor('sesi-lama', '10.0.0.1'),
            $keyFor('sesi-lama', '10.0.0.2'),
        );
    }

    private function loginRequest(): Request
    {
        return Request::create('/passkeys/login', 'POST');
    }

    private function passkeyFor(User $user): Passkey
    {
        $passkey = new Passkey([
            'name' => 'Laptop',
            'credential_id' => 'credential-id',
            'credential' => [],
        ]);

        $passkey->user_id = $user->getKey();
        $passkey->setRelation('user', $user);

        return $passkey;
    }
}
