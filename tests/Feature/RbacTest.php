<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
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

        // Seeder hanya menyiapkan role superadmin; role lain dibuat lewat
        // halaman RBAC dan di sini disiapkan sebagai fixture test.
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('operator', 'web');
    }

    /**
     * Gate::before milik Spatie memetakan nama ability ke nama permission dan
     * berjalan sebelum policy, jadi nama yang bertabrakan akan meloloskan
     * pengguna tanpa aturan kontekstual dievaluasi.
     */
    public function test_permission_names_do_not_collide_with_policy_abilities(): void
    {
        $contextualAbilities = [
            ...UserPolicy::CONTEXTUAL_ABILITIES,
            ...RolePolicy::CONTEXTUAL_ABILITIES,
        ];

        $collisions = Permission::query()
            ->whereIn('name', $contextualAbilities)
            ->pluck('name')
            ->all();

        $this->assertSame([], $collisions);
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
                ->where('roles.0.can_rename', false)
                ->where('roles.0.can_delete', false)
                ->where('roles.0.can_change_permissions', false)
                ->where('roles.1.name', 'admin')
                ->where('roles.1.can_rename', true)
                ->where('roles.1.can_delete', true)
                ->where('roles.1.can_change_permissions', true)
                ->has('permissions', 4));
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
            ->assertSessionHas('error', 'Permission superadmin tidak dapat diubah.');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertCount(4, Role::findByName('superadmin')->permissions);
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

    public function test_role_can_be_renamed(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->superadmin())
            ->patch("/rbac/roles/{$admin->id}", ['name' => 'administrator'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['id' => $admin->id, 'name' => 'administrator']);
        $this->assertDatabaseMissing('roles', ['name' => 'admin']);
    }

    public function test_role_name_must_be_unique_when_renaming(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->superadmin())
            ->patch("/rbac/roles/{$admin->id}", ['name' => 'operator'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('roles', ['id' => $admin->id, 'name' => 'admin']);
    }

    public function test_superadmin_role_cannot_be_renamed(): void
    {
        $superadmin = Role::findByName('superadmin');

        $this->actingAs($this->superadmin())
            ->patch("/rbac/roles/{$superadmin->id}", ['name' => 'root'])
            ->assertSessionHas('error', 'Nama peran superadmin tidak dapat diubah.');

        $this->assertDatabaseHas('roles', ['id' => $superadmin->id, 'name' => 'superadmin']);
    }

    public function test_role_without_users_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'auditor', 'guard_name' => 'web']);

        $this->actingAs($this->superadmin())
            ->delete("/rbac/roles/{$role->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $operator = Role::findByName('operator');

        $this->actingAs($this->superadmin())
            ->delete("/rbac/roles/{$operator->id}")
            ->assertSessionHas(
                'error',
                'Peran operator masih dipakai 1 pengguna, jadi belum bisa dihapus.',
            );

        $this->assertDatabaseHas('roles', ['id' => $operator->id]);
    }

    public function test_superadmin_role_cannot_be_deleted(): void
    {
        $superadmin = Role::findByName('superadmin');

        $this->actingAs($this->superadmin())
            ->delete("/rbac/roles/{$superadmin->id}")
            ->assertSessionHas('error', 'Peran superadmin tidak dapat dihapus.');

        $this->assertDatabaseHas('roles', ['id' => $superadmin->id]);
    }

    public function test_users_without_permission_cannot_rename_or_delete_roles(): void
    {
        $admin = Role::findByName('admin');
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user)
            ->patch("/rbac/roles/{$admin->id}", ['name' => 'administrator'])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete("/rbac/roles/{$admin->id}")
            ->assertForbidden();
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
