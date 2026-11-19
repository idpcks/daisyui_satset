<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemConfigTest extends TestCase
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
        $this->get('/settings')->assertRedirect('/auth/signin');
    }

    public function test_users_without_permission_cannot_open_or_save_the_tab(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        // Tab yang tidak diizinkan kembali ke Akun, bukan menampilkan data
        // konfigurasi sistem.
        $this->actingAs($user)
            ->get('/settings?tab=sistem')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'akun')
                ->missing('system'));

        $this->actingAs($user)
            ->put('/settings/system', $this->payload())
            ->assertForbidden();
    }

    public function test_users_with_permission_can_open_the_tab(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        $user->givePermissionTo('system.manage');

        $this->actingAs($user)
            ->get('/settings?tab=sistem')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'sistem')
                ->has('system'));
    }

    public function test_the_old_system_page_is_gone(): void
    {
        $this->actingAs($this->superadmin())->get('/system')->assertNotFound();
    }

    public function test_page_shows_effective_settings(): void
    {
        Setting::put([
            'app.name' => 'Panel Khusus',
            'app.locale' => 'en',
            'app.timezone' => 'Asia/Makassar',
            'maintenance.enabled' => true,
            'maintenance.message' => 'Sedang upgrade database.',
        ]);

        $this->actingAs($this->superadmin())
            ->get('/settings?tab=sistem')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'sistem')
                ->where('system.app_name', 'Panel Khusus')
                ->where('system.locale', 'en')
                ->where('system.timezone', 'Asia/Makassar')
                ->where('system.maintenance_enabled', true)
                ->where('system.maintenance_message', 'Sedang upgrade database.')
                ->where('locales.0.value', 'id')
                ->has('timezones'));
    }

    public function test_settings_can_be_updated(): void
    {
        $this->actingAs($this->superadmin())
            ->put('/settings/system', $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = Setting::values();

        $this->assertSame('Panel Baru', $settings['app.name']);
        $this->assertSame('en', $settings['app.locale']);
        $this->assertSame('Asia/Makassar', $settings['app.timezone']);
        $this->assertTrue($settings['maintenance.enabled']);
        $this->assertSame('Sedang upgrade database.', $settings['maintenance.message']);

        // Request berikutnya memakai nilai baru pada shared prop Inertia.
        $this->actingAs($this->superadmin())
            ->get('/app')
            ->assertInertia(fn (Assert $page) => $page->where('appName', 'Panel Baru'));
    }

    public function test_update_is_recorded_in_the_audit_log(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->put('/settings/system', $this->payload())
            ->assertRedirect();

        $activity = Activity::query()->where('log_name', 'system')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('system_config_updated', $activity->event);
        $this->assertSame('Panel Baru', $activity->properties->get('changes')['app_name']['new']);
        $this->assertTrue($activity->causer->is($superadmin));
    }

    public function test_saving_without_changes_does_not_write_an_activity(): void
    {
        $payload = [
            'app_name' => config('app.name'),
            'locale' => config('app.locale'),
            'timezone' => config('app.timezone'),
            'maintenance_enabled' => false,
            'maintenance_message' => '',
        ];

        $this->actingAs($this->superadmin())
            ->put('/settings/system', $payload)
            ->assertRedirect();

        $this->assertSame(0, Activity::query()->where('log_name', 'system')->count());
    }

    public function test_invalid_values_are_rejected(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->put('/settings/system', [...$this->payload(), 'app_name' => ''])
            ->assertSessionHasErrors('app_name');

        $this->actingAs($superadmin)
            ->put('/settings/system', [...$this->payload(), 'app_name' => str_repeat('a', 61)])
            ->assertSessionHasErrors('app_name');

        $this->actingAs($superadmin)
            ->put('/settings/system', [...$this->payload(), 'locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->actingAs($superadmin)
            ->put('/settings/system', [...$this->payload(), 'timezone' => 'Mars/Phobos'])
            ->assertSessionHasErrors('timezone');

        $this->actingAs($superadmin)
            ->put('/settings/system', [...$this->payload(), 'maintenance_message' => str_repeat('a', 501)])
            ->assertSessionHasErrors('maintenance_message');
    }

    public function test_maintenance_message_is_required_when_enabled(): void
    {
        $this->actingAs($this->superadmin())
            ->put('/settings/system', [
                ...$this->payload(),
                'maintenance_enabled' => true,
                'maintenance_message' => '',
            ])
            ->assertSessionHasErrors('maintenance_message');
    }

    public function test_guests_receive_503_during_maintenance(): void
    {
        $this->enableMaintenance();

        $this->get('/app')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '3600')
            ->assertSee('Sedang upgrade database.');
    }

    public function test_regular_users_receive_503_during_maintenance(): void
    {
        $this->enableMaintenance();

        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user)->get('/app')->assertStatus(503);
    }

    public function test_superadmin_and_login_page_remain_accessible_during_maintenance(): void
    {
        $this->enableMaintenance();

        $this->get('/auth/signin')->assertOk();

        $this->actingAs($this->superadmin())->get('/app')->assertOk();
    }

    public function test_site_is_accessible_again_when_maintenance_is_disabled(): void
    {
        $this->enableMaintenance();

        Setting::put(['maintenance.enabled' => false]);

        $this->get('/app')->assertRedirect('/auth/signin');
    }

    /**
     * Payload valid untuk menyimpan konfigurasi.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'app_name' => 'Panel Baru',
            'locale' => 'en',
            'timezone' => 'Asia/Makassar',
            'maintenance_enabled' => true,
            'maintenance_message' => 'Sedang upgrade database.',
        ];
    }

    private function enableMaintenance(): void
    {
        Setting::put([
            'maintenance.enabled' => true,
            'maintenance.message' => 'Sedang upgrade database.',
        ]);
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
