<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_two_factor_logs_in_directly(): void
    {
        $user = User::factory()->create();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_two_factor_is_redirected_to_the_challenge(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    public function test_challenge_page_renders_for_the_challenged_user(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password']);

        $this->get('/two-factor-challenge')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/TwoFactorChallenge'));
    }

    public function test_valid_code_completes_the_login(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password']);

        $this->post('/two-factor-challenge', ['code' => $this->currentCode($user)])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_code_keeps_the_user_signed_out(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password']);

        $this->from('/two-factor-challenge')
            ->post('/two-factor-challenge', ['code' => '000000'])
            ->assertRedirect('/two-factor-challenge')
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_recovery_code_completes_the_login(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/auth/signin', ['email' => $user->email, 'password' => 'password']);

        $this->post('/two-factor-challenge', ['recovery_code' => $user->recoveryCodes()[0]])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Pengguna dengan autentikasi dua faktor aktif beserta kode pemulihannya.
     */
    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create();

        app(EnableTwoFactorAuthentication::class)($user);

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->refresh();
    }

    /**
     * Kode TOTP yang berlaku saat ini untuk secret pengguna.
     */
    private function currentCode(User $user): string
    {
        return (new Google2FA)->getCurrentOtp(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
        );
    }
}
