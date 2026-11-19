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
            ->post('/settings/security/recovery-codes', ['password' => 'password'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $after = $user->refresh()->recoveryCodes();

        $this->assertCount(8, $after);
        $this->assertNotSame($before, $after);
    }

    public function test_recovery_codes_cannot_be_regenerated_without_the_account_password(): void
    {
        $user = $this->confirmedUser();

        $before = $user->recoveryCodes();

        $this->actingAs($user)
            ->post('/settings/security/recovery-codes')
            ->assertSessionHasErrors('password');

        $this->assertSame($before, $user->refresh()->recoveryCodes());
    }

    public function test_recovery_codes_cannot_be_regenerated_with_a_wrong_password(): void
    {
        $user = $this->confirmedUser();

        $before = $user->recoveryCodes();

        $this->actingAs($user)
            ->post('/settings/security/recovery-codes', ['password' => 'salah'])
            ->assertSessionHasErrors(['password' => 'Password tidak cocok.']);

        $this->assertSame($before, $user->refresh()->recoveryCodes());
    }

    /**
     * Konfirmasi password melindungi aksi sensitif. Tanpa batas, sesi yang
     * dibajak bisa dipakai menebak password tanpa henti.
     */
    public function test_two_factor_enable_is_throttled_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->post('/settings/security/two-factor', ['password' => 'salah'])
                ->assertSessionHasErrors('password');
        }

        $this->actingAs($user)
            ->post('/settings/security/two-factor', ['password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_recovery_code_regeneration_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->post('/settings/security/recovery-codes', ['password' => 'salah'])
                ->assertSessionHasErrors('password');
        }

        $this->actingAs($user)
            ->post('/settings/security/recovery-codes', ['password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_fortify_password_confirm_endpoint_is_throttled(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->post('/user/confirm-password', ['password' => 'salah']);
        }

        $this->actingAs($user)
            ->post('/user/confirm-password', ['password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_throttle_does_not_block_a_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/security/two-factor', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertNotNull($user->refresh()->two_factor_secret);
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
