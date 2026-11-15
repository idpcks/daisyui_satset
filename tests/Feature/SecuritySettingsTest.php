<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_page_shows_the_two_factor_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings?tab=keamanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('twoFactorEnabled', false)
                ->where('twoFactorPending', false)
                ->where('qrCodeSvg', null)
                ->where('recoveryCodes', []));
    }

    public function test_enabling_requires_the_account_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings?tab=keamanan')
            ->post('/settings/security/two-factor', ['password' => 'salah'])
            ->assertRedirect('/settings?tab=keamanan')
            ->assertSessionHasErrors('password');

        $this->assertNull($user->refresh()->two_factor_secret);
    }

    public function test_enabling_creates_a_pending_secret_and_qr_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/security/two-factor', ['password' => 'password'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->actingAs($user)
            ->get('/settings?tab=keamanan')
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorPending', true)
                ->where('twoFactorEnabled', false)
                ->where('secretKey', Fortify::currentEncrypter()->decrypt($user->two_factor_secret))
                ->where('qrCodeSvg', fn (?string $svg): bool => is_string($svg) && str_contains($svg, '<svg')));
    }

    public function test_confirmation_rejects_an_invalid_code(): void
    {
        $user = $this->userWithPendingSecret();

        $this->actingAs($user)
            ->post('/settings/security/two-factor/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->refresh()->two_factor_confirmed_at);
    }

    public function test_confirmation_activates_two_factor_authentication(): void
    {
        $user = $this->userWithPendingSecret();

        $this->actingAs($user)
            ->post('/settings/security/two-factor/confirm', ['code' => $this->currentCode($user)])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($user->refresh()->two_factor_confirmed_at);

        $this->actingAs($user)
            ->get('/settings?tab=keamanan')
            ->assertInertia(fn (Assert $page) => $page
                ->where('twoFactorEnabled', true)
                ->where('twoFactorPending', false)
                ->has('recoveryCodes', 8));
    }

    public function test_disabling_requires_password_and_clears_the_secret(): void
    {
        $user = $this->confirmedUser();

        $this->actingAs($user)
            ->delete('/settings/security/two-factor', ['password' => 'salah'])
            ->assertSessionHasErrors('password');

        $this->assertNotNull($user->refresh()->two_factor_secret);

        $this->actingAs($user)
            ->delete('/settings/security/two-factor', ['password' => 'password'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($user->refresh()->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = $this->confirmedUser();

        $before = $user->recoveryCodes();

        $this->actingAs($user)
            ->post('/settings/security/recovery-codes')
            ->assertRedirect()
            ->assertSessionHas('success');

        $after = $user->refresh()->recoveryCodes();

        $this->assertCount(8, $after);
        $this->assertNotSame($before, $after);
    }

    /**
     * Pengguna dengan secret 2FA yang belum dikonfirmasi.
     */
    private function userWithPendingSecret(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/security/two-factor', ['password' => 'password']);

        return $user->refresh();
    }

    /**
     * Pengguna dengan autentikasi dua faktor yang sudah aktif.
     */
    private function confirmedUser(): User
    {
        $user = $this->userWithPendingSecret();

        $this->actingAs($user)
            ->post('/settings/security/two-factor/confirm', ['code' => $this->currentCode($user)]);

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
