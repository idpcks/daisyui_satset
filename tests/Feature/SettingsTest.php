<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/auth/signin');
    }

    public function test_page_shows_the_account_data_and_the_default_tab(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Aminah',
            'username' => 'siti',
        ]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'akun')
                ->where('account.name', 'Siti Aminah')
                ->where('account.username', 'siti')
                ->where('account.email', $user->email));
    }

    public function test_security_tab_can_be_selected_from_the_query_string(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/settings?tab=keamanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'keamanan'));
    }

    public function test_user_can_update_their_own_account(): void
    {
        $user = User::factory()->create(['username' => 'lama']);

        $this->actingAs($user)
            ->put('/settings/account', [
                'name' => 'Nama Baru',
                'username' => 'nama-baru',
                'email' => 'nama-baru@example.test',
                'current_password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'username' => 'nama-baru',
            'email' => 'nama-baru@example.test',
            'updated_by' => 'lama',
        ]);
    }

    public function test_username_and_email_must_be_unique(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/account', [
                'name' => 'Nama Baru',
                'username' => $other->username,
                'email' => $other->email,
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors(['username', 'email']);
    }

    public function test_account_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/settings/account', [
                'name' => '',
                'username' => 'tanpa-nama',
                'email' => 'tanpa-nama@example.test',
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_account_update_is_rejected_without_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/account', [
                'name' => 'Nama Baru',
                'username' => 'nama-baru',
                'email' => 'nama-baru@example.test',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ]);
    }

    public function test_account_update_is_rejected_when_the_current_password_is_wrong(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/account', [
                'name' => 'Nama Baru',
                'username' => 'nama-baru',
                'email' => 'nama-baru@example.test',
                'current_password' => 'salah',
            ])
            ->assertSessionHasErrors(['current_password' => 'Password saat ini tidak cocok.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ]);
    }

    public function test_user_can_change_their_own_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('password-baru', $user->refresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'password-baru',
                'password_confirmation' => 'beda',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_guests_cannot_log_out_other_sessions(): void
    {
        $this->delete('/settings/security/other-sessions', ['password' => 'password'])
            ->assertRedirect('/auth/signin');
    }

    public function test_logging_out_other_sessions_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/settings?tab=keamanan')
            ->delete('/settings/security/other-sessions', ['password' => 'salah'])
            ->assertRedirect('/settings?tab=keamanan')
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_log_out_other_sessions_and_keep_the_current_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/settings/security/other-sessions', ['password' => 'password'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    /**
     * Rute profil milik Fortify tidak meminta current_password, sehingga
     * meninggalkannya aktif melemahkan aturan di PUT /settings/account.
     */
    public function test_fortify_profile_route_is_not_registered(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->put('/user/profile-information', [
                'name' => 'Nama Diambil Alih',
                'email' => 'baru@example.com',
            ])
            ->assertNotFound();

        $this->assertSame('lama@example.com', $user->refresh()->email);
    }

    /**
     * Jalur yang dipakai aplikasi tetap menolak perubahan tanpa password.
     */
    public function test_account_update_still_requires_current_password(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->from('/settings?tab=akun')
            ->put('/settings/account', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => 'baru@example.com',
            ])
            ->assertRedirect('/settings?tab=akun')
            ->assertSessionHasErrors('current_password');

        $this->assertSame('lama@example.com', $user->refresh()->email);
    }
}
