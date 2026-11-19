<?php

namespace Tests\Feature;

use App\Features\MagicLinkLogin;
use App\Features\Passkeys;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Role::findOrCreate('operator', 'web');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/features')->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user)->get('/features')->assertForbidden();

        $this->actingAs($user)
            ->patch('/features/passkeys-login', ['active' => false])
            ->assertForbidden();
    }

    public function test_page_lists_flags_and_their_status(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/features')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/Index')
                ->has('flags', 2)
                ->where('flags.0.name', 'passkeys-login')
                ->where('flags.0.active', true)
                ->where('flags.1.name', 'magic-link-login')
                ->where('flags.1.active', false));
    }

    public function test_flag_can_be_disabled_and_enabled_again(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->patch('/features/passkeys-login', ['active' => false])
            ->assertRedirect()
            ->assertSessionHas('success');

        Feature::flushCache();
        $this->assertFalse(Feature::active(Passkeys::class));

        $this->actingAs($superadmin)
            ->patch('/features/passkeys-login', ['active' => true])
            ->assertRedirect()
            ->assertSessionHas('success');

        Feature::flushCache();
        $this->assertTrue(Feature::active(Passkeys::class));
    }

    public function test_disabling_passkeys_applies_to_guests_and_other_users(): void
    {
        $this->actingAs($this->superadmin())
            ->patch('/features/passkeys-login', ['active' => false])
            ->assertRedirect();

        Feature::flushCache();

        $this->assertFalse(Feature::for(null)->active(Passkeys::class));

        $this->post('/logout');

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('features.passkeys', false));
    }

    /**
     * Saat config paket menyalakan magic link, flag Pennant tetap bisa
     * menutup rutenya lewat EnsureFeaturesAreActive.
     */
    public function test_disabling_magic_link_closes_its_routes(): void
    {
        Feature::deactivate(MagicLinkLogin::class);
        Feature::flushCache();

        $this->get('/magic-link')->assertNotFound();
    }

    public function test_changes_are_recorded_in_the_audit_log(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->patch('/features/magic-link-login', ['active' => false])
            ->assertRedirect();

        $activity = Activity::query()->where('log_name', 'feature')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('feature_disabled', $activity->event);
        $this->assertSame('magic-link-login', $activity->properties->get('feature'));
        $this->assertFalse($activity->properties->get('active'));
        $this->assertTrue($activity->causer->is($superadmin));
    }

    public function test_unknown_flag_returns_not_found(): void
    {
        $this->actingAs($this->superadmin())
            ->patch('/features/tidak-ada', ['active' => false])
            ->assertNotFound();
    }

    public function test_active_value_must_be_boolean(): void
    {
        $this->actingAs($this->superadmin())
            ->patch('/features/passkeys-login', ['active' => 'mungkin'])
            ->assertSessionHasErrors('active');
    }

    /**
     * Pengguna dengan peran superadmin untuk mengelola feature flag.
     */
    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
