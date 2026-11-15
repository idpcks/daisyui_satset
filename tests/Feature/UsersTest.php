<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('operator', 'web');

        Role::findByName('superadmin', 'web')
            ->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));
    }

    private function superadmin(): User
    {
        $user = User::factory()->create(['name' => 'Zed Superadmin']);
        $user->assignRole('superadmin');

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_index_lists_users(): void
    {
        $actor = $this->superadmin();
        User::factory()->create([
            'name' => 'Operator Satu',
            'username' => 'operator1',
            'email' => 'operator1@example.com',
        ])->assignRole('operator');

        $this->actingAs($actor)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->has('users.data', 2)
                ->has('roles', 3)
                ->where('users.data.0.name', 'Operator Satu')
                ->where('users.data.0.role', 'operator')
            );
    }

    public function test_role_options_are_loaded_from_database_for_superadmin(): void
    {
        Role::create(['name' => 'auditor', 'guard_name' => 'web']);

        $this->actingAs($this->superadmin())
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('roles', 4)
                ->where('roles', ['admin', 'auditor', 'operator', 'superadmin'])
            );
    }

    public function test_custom_role_can_be_assigned_to_a_new_user(): void
    {
        Role::create(['name' => 'auditor', 'guard_name' => 'web']);

        $this->actingAs($this->superadmin())
            ->post('/users', [
                'name' => 'Auditor Baru',
                'username' => 'auditorbaru',
                'email' => 'auditorbaru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'auditor',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'auditorbaru')->firstOrFail();

        $this->assertTrue($user->hasRole('auditor'));
    }

    public function test_non_superadmin_gets_dynamic_roles_but_cannot_assign_superadmin(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));
        Role::create(['name' => 'auditor', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles', ['admin', 'auditor', 'operator'])
            );

        $this->actingAs($actor)
            ->post('/users', [
                'name' => 'Superadmin Palsu',
                'username' => 'superadminpalsu',
                'email' => 'superadminpalsu@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'superadmin',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'superadminpalsu']);
    }

    public function test_user_can_be_created(): void
    {
        $actor = $this->superadmin();

        $this->actingAs($actor)
            ->post('/users', [
                'name' => 'Admin Baru',
                'username' => 'adminbaru',
                'email' => 'adminbaru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'adminbaru')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->is_active);
    }

    public function test_user_can_be_updated_without_changing_password(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create([
            'password' => 'password-lama',
        ]);
        $target->assignRole('operator');

        $this->actingAs($actor)
            ->put("/users/{$target->id}", [
                'name' => 'Nama Baru',
                'username' => $target->username,
                'email' => $target->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index'));

        $target->refresh();

        $this->assertSame('Nama Baru', $target->name);
        $this->assertFalse($target->is_active);
        $this->assertTrue($target->hasRole('admin'));
        $this->assertTrue(Hash::check('password-lama', $target->password));
    }

    public function test_bulk_status_requires_permission(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$target->id],
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_bulk_status_validates_ids_and_status(): void
    {
        $this->actingAs($this->superadmin())
            ->patch('/users/bulk-status', [
                'ids' => ['bukan-uuid', (string) Str::uuid()],
                'is_active' => 'mungkin',
            ])
            ->assertSessionHasErrors(['ids.0', 'ids.1', 'is_active']);
    }

    public function test_users_can_be_deactivated_in_bulk(): void
    {
        $actor = $this->superadmin();
        $first = User::factory()->create(['is_active' => true]);
        $second = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$first->id, $second->id],
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 pengguna berhasil dinonaktifkan.');

        foreach ([$first, $second] as $target) {
            $target->refresh();

            $this->assertFalse($target->is_active);
            $this->assertSame($actor->username, $target->updated_by);
        }
    }

    public function test_users_can_be_activated_in_bulk(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create(['is_active' => false]);

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$target->id],
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '1 pengguna berhasil diaktifkan.');

        $target->refresh();

        $this->assertTrue($target->is_active);
        $this->assertSame($actor->username, $target->updated_by);
    }

    public function test_bulk_deactivation_rejects_own_account(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$actor->id, $target->id],
                'is_active' => false,
            ])
            ->assertSessionHasErrors('ids');

        $this->assertTrue($actor->fresh()->is_active);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_user_can_be_deleted_but_not_self(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->delete("/users/{$target->id}")
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', [
            'id' => $target->id,
            'deleted_by' => $actor->username,
        ]);

        // User yang sudah di-soft-delete tidak boleh muncul lagi di daftar.
        $this->actingAs($actor)
            ->get('/users')
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.total', 1)
                ->where('users.data.0.id', $actor->id)
            );

        // Route model binding juga harus mengabaikan user yang sudah dihapus.
        $this->actingAs($actor)
            ->delete("/users/{$target->id}")
            ->assertNotFound();

        $this->actingAs($actor)
            ->delete("/users/{$actor->id}")
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('users', ['id' => $actor->id]);
    }
}
