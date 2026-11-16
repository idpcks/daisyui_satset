<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Driver array menjaga test tidak menyentuh file `storage/framework/down`
        // milik aplikasi yang sedang berjalan di mesin developer.
        config(['app.maintenance.driver' => 'array']);
    }

    protected function tearDown(): void
    {
        app()->maintenanceMode()->deactivate();

        if (is_file(storage_path('framework/maintenance.php'))) {
            unlink(storage_path('framework/maintenance.php'));
        }

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/maintenance')->assertRedirect('/login');
    }

    public function test_operator_cannot_manage_maintenance(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)->get('/maintenance')->assertForbidden();
        $this->actingAs($operator)->post('/maintenance')->assertForbidden();
        $this->actingAs($operator)->delete('/maintenance')->assertForbidden();
    }

    public function test_admin_without_the_permission_cannot_lock_the_application(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/maintenance')->assertForbidden();
        $this->actingAs($admin)->post('/maintenance')->assertForbidden();
        $this->actingAs($admin)->delete('/maintenance')->assertForbidden();
    }

    public function test_page_shows_the_current_status(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/maintenance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('maintenance/Index')
                ->where('active', false)
                ->where('retry', null));
    }

    public function test_superadmin_can_enable_maintenance_and_keeps_a_bypass_cookie(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)
            ->post('/maintenance', ['retry' => 600])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(app()->maintenanceMode()->active());

        $cookie = $this->bypassCookie($response);
        $this->assertNotSame('', $cookie);

        // Cookie bypass harus plain agar terbaca stub 503 sebelum Laravel boot.
        $payload = json_decode((string) base64_decode($cookie, true), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('expires_at', $payload);
        $this->assertArrayHasKey('mac', $payload);

        $this->withUnencryptedCookie('laravel_maintenance', $cookie)
            ->actingAs($superadmin)
            ->get('/maintenance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('maintenance/Index')
                ->where('active', true)
                ->where('retry', 600));
    }

    public function test_visitors_without_the_bypass_cookie_see_the_maintenance_page(): void
    {
        $this->actingAs($this->superadmin())->post('/maintenance')->assertRedirect();

        $this->get('/dashboard')
            ->assertStatus(503)
            ->assertSeeText('Aplikasi sedang dalam perbaikan');
    }

    public function test_superadmin_can_disable_maintenance_with_the_bypass_cookie(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post('/maintenance');
        $cookie = $this->bypassCookie($response);

        $this->withUnencryptedCookie('laravel_maintenance', $cookie)
            ->actingAs($superadmin)
            ->delete('/maintenance')
            ->assertRedirect(route('maintenance.index'))
            ->assertSessionHas('success');

        $this->assertFalse(app()->maintenanceMode()->active());
    }

    public function test_disabling_an_inactive_maintenance_returns_an_error(): void
    {
        $this->actingAs($this->superadmin())
            ->delete('/maintenance')
            ->assertSessionHas('error', 'Maintenance mode sedang tidak aktif.');
    }

    public function test_enabling_twice_returns_an_error(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post('/maintenance')->assertRedirect();

        $this->withUnencryptedCookie('laravel_maintenance', $this->bypassCookie($response))
            ->actingAs($superadmin)
            ->post('/maintenance')
            ->assertSessionHas('error', 'Maintenance mode sudah aktif.');
    }

    public function test_changes_are_recorded_in_the_audit_log(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)
            ->post('/maintenance', ['retry' => 300])
            ->assertRedirect();

        $enabled = Activity::query()->where('log_name', 'maintenance')->orderByDesc('id')->first();

        $this->assertNotNull($enabled);
        $this->assertSame('maintenance_enabled', $enabled->event);
        $this->assertSame(300, $enabled->properties->get('retry'));
        $this->assertTrue($enabled->causer->is($superadmin));

        $this->withUnencryptedCookie('laravel_maintenance', $this->bypassCookie($response))
            ->actingAs($superadmin)
            ->delete('/maintenance')
            ->assertRedirect();

        $disabled = Activity::query()->where('log_name', 'maintenance')->orderByDesc('id')->first();

        $this->assertNotNull($disabled);
        $this->assertSame('maintenance_disabled', $disabled->event);
        $this->assertTrue($disabled->causer->is($superadmin));
    }

    public function test_retry_value_must_be_a_positive_integer(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post('/maintenance', ['retry' => 'sebentar'])
            ->assertSessionHasErrors('retry');

        $this->actingAs($superadmin)
            ->post('/maintenance', ['retry' => 0])
            ->assertSessionHasErrors('retry');

        $this->assertFalse(app()->maintenanceMode()->active());
    }

    /**
     * Ambil nilai cookie bypass dari response redirect.
     */
    private function bypassCookie(TestResponse $response): string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie): bool => $cookie->getName() === 'laravel_maintenance');

        $this->assertNotNull($cookie, 'Response tidak memuat cookie bypass maintenance.');

        return (string) $cookie->getValue();
    }

    /**
     * Pengguna dengan peran superadmin untuk mengelola maintenance.
     */
    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
