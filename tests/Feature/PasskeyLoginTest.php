<?php

namespace Tests\Feature;

use App\Features\Passkeys as PasskeysFeature;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
