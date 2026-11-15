<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/rbac')->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user)->get('/rbac')->assertForbidden();
    }

    public function test_page_lists_roles_and_permissions(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/rbac')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('rbac/Index')
                ->has('roles', 3)
                ->where('roles.0.name', 'superadmin')
                ->where('roles.0.is_superadmin', true)
                ->has('permissions', 2));
    }

    public function test_permissions_can_be_replaced_for_a_role(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->superadmin())
            ->put("/rbac/roles/{$admin->id}", ['permissions' => ['role.manage']])
            ->assertRedirect()
            ->assertSessionHas('success');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertEquals(
            ['role.manage'],
            Role::findByName('admin')->permissions->pluck('name')->all(),
        );
    }

    public function test_superadmin_permissions_cannot_be_changed(): void
    {
        $superadmin = Role::findByName('superadmin');

        $this->actingAs($this->superadmin())
            ->put("/rbac/roles/{$superadmin->id}", ['permissions' => []])
            ->assertSessionHas('error');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertCount(2, Role::findByName('superadmin')->permissions);
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->superadmin())
            ->put("/rbac/roles/{$admin->id}", ['permissions' => ['tidak.ada']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_role_can_be_created(): void
    {
        $this->actingAs($this->superadmin())
            ->post('/rbac/roles', ['name' => 'auditor'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['name' => 'auditor', 'guard_name' => 'web']);
        $this->assertCount(0, Role::findByName('auditor')->permissions);
    }

    public function test_role_name_must_be_valid_and_unique(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post('/rbac/roles', ['name' => 'Auditor Baru'])
            ->assertSessionHasErrors('name');

        $this->actingAs($superadmin)
            ->post('/rbac/roles', ['name' => 'admin'])
            ->assertSessionHasErrors('name');
    }

    public function test_users_without_permission_cannot_create_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user)
            ->post('/rbac/roles', ['name' => 'auditor'])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'auditor']);
    }

    public function test_permissions_payload_is_required(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->superadmin())
            ->put("/rbac/roles/{$admin->id}", [])
            ->assertSessionHasErrors('permissions');
    }

    /**
     * Pengguna dengan peran superadmin yang boleh mengelola RBAC.
     */
    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
