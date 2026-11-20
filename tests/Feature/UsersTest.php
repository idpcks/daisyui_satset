<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->get('/users')->assertRedirect('/auth/signin');
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

    public function test_index_flags_superadmin_accounts_as_read_only_for_non_superadmin(): void
    {
        $actor = User::factory()->create(['name' => 'Aa Admin']);
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        User::factory()->create(['name' => 'Bb Operator'])->assignRole('operator');
        $this->superadmin();

        $this->actingAs($actor)
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 3)
                // Akun sendiri: boleh diubah, tidak boleh dihapus.
                ->where('users.data.0.name', 'Aa Admin')
                ->where('users.data.0.can_update', true)
                ->where('users.data.0.can_delete', false)
                // Pengguna biasa: boleh dikelola sepenuhnya.
                ->where('users.data.1.name', 'Bb Operator')
                ->where('users.data.1.can_update', true)
                ->where('users.data.1.can_delete', true)
                // Akun superadmin: terkunci bagi non-superadmin.
                ->where('users.data.2.name', 'Zed Superadmin')
                ->where('users.data.2.can_update', false)
                ->where('users.data.2.can_delete', false)
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
                'password' => 'Str0ngPassw0rd2026!',
                'password_confirmation' => 'Str0ngPassw0rd2026!',
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
                'password' => 'Str0ngPassw0rd2026!',
                'password_confirmation' => 'Str0ngPassw0rd2026!',
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
                'password' => 'Str0ngPassw0rd2026!',
                'password_confirmation' => 'Str0ngPassw0rd2026!',
                'role' => 'admin',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'adminbaru')->firstOrFail();

        $this->assertTrue(Hash::check('Str0ngPassw0rd2026!', $user->password));
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
            ->assertSessionHasErrors(['ids' => 'Anda tidak dapat menonaktifkan akun sendiri.']);

        $this->assertTrue($actor->fresh()->is_active);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_superadmin_cannot_change_own_role_or_deactivate_self(): void
    {
        $actor = $this->superadmin();

        $payload = [
            'name' => $actor->name,
            'username' => $actor->username,
            'email' => $actor->email,
            'password' => '',
            'password_confirmation' => '',
        ];

        $this->actingAs($actor)
            ->put("/users/{$actor->id}", [...$payload, 'role' => 'admin', 'is_active' => true])
            ->assertSessionHasErrors(['role' => 'Anda tidak dapat mengubah peran akun sendiri.']);

        $this->assertTrue($actor->fresh()->hasRole('superadmin'));

        $this->actingAs($actor)
            ->put("/users/{$actor->id}", [...$payload, 'role' => 'superadmin', 'is_active' => false])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($actor->fresh()->is_active);
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
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun sendiri.');

        $this->assertNotSoftDeleted('users', ['id' => $actor->id]);
    }

    public function test_non_superadmin_cannot_update_superadmin(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        $target = $this->superadmin();

        $this->actingAs($actor)
            ->put("/users/{$target->id}", [
                'name' => 'Diubah Admin',
                'username' => $target->username,
                'email' => $target->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'admin',
                'is_active' => true,
            ])
            ->assertSessionHas('error', 'Akun superadmin tidak dapat diubah.');

        $target->refresh();

        $this->assertSame('Zed Superadmin', $target->name);
        $this->assertTrue($target->hasRole('superadmin'));
    }

    public function test_non_superadmin_cannot_delete_superadmin(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        $target = $this->superadmin();

        $this->actingAs($actor)
            ->delete("/users/{$target->id}")
            ->assertSessionHas('error', 'Akun superadmin tidak dapat dihapus.');

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_non_superadmin_cannot_deactivate_superadmin_in_bulk(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        $target = $this->superadmin();

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$target->id],
                'is_active' => false,
            ])
            ->assertSessionHasErrors(['ids' => 'Akun superadmin tidak dapat dinonaktifkan.']);

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_non_superadmin_cannot_activate_superadmin_in_bulk(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        $target = User::factory()->create(['is_active' => false]);
        $target->assignRole('superadmin');

        $this->actingAs($actor)
            ->patch('/users/bulk-status', [
                'ids' => [$target->id],
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['ids' => 'Akun superadmin tidak dapat diubah.']);

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_non_superadmin_can_update_another_non_superadmin(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        Role::findByName('admin')->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));

        $target = User::factory()->create();
        $target->assignRole('operator');

        $this->actingAs($actor)
            ->put("/users/{$target->id}", [
                'name' => 'Nama Dari Admin',
                'username' => $target->username,
                'email' => $target->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'operator',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame('Nama Dari Admin', $target->fresh()->name);
    }

    /**
     * Constraint lama harus sudah diganti partial index. Tanpa ini, validasi
     * FormRequest lolos tetapi INSERT ditolak database dengan 500.
     */
    public function test_users_have_partial_unique_indexes(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select('PRAGMA index_list(users)'))->pluck('name');
        } else {
            $indexes = collect(DB::select(
                "SELECT indexname AS name FROM pg_indexes WHERE tablename = 'users'"
            ))->pluck('name');
        }

        $this->assertTrue($indexes->contains('users_email_active_unique'));
        $this->assertTrue($indexes->contains('users_username_active_unique'));
        $this->assertFalse($indexes->contains('users_email_unique'));
        $this->assertFalse($indexes->contains('users_username_unique'));
    }

    /**
     * Email dan username milik user ter-soft-delete harus bisa dipakai ulang.
     * Sebelum partial unique index, ini gagal di constraint database.
     */
    public function test_deleted_user_email_and_username_can_be_reused(): void
    {
        $actor = $this->superadmin();

        $deleted = User::factory()->create([
            'name' => 'User Lama',
            'username' => 'pegawai',
            'email' => 'pegawai@example.com',
        ]);
        $deleted->delete();

        $this->actingAs($actor)
            ->post('/users', [
                'name' => 'User Baru',
                'username' => 'pegawai',
                'email' => 'pegawai@example.com',
                'password' => 'Str0ngPassw0rd2026!',
                'password_confirmation' => 'Str0ngPassw0rd2026!',
                'role' => 'operator',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'pegawai',
            'email' => 'pegawai@example.com',
            'deleted_at' => null,
        ]);
    }

    /**
     * User yang masih aktif tetap tidak boleh diduplikat.
     */
    public function test_active_user_email_cannot_be_reused(): void
    {
        $actor = $this->superadmin();

        User::factory()->create([
            'username' => 'masihaktif',
            'email' => 'masihaktif@example.com',
        ]);

        $this->actingAs($actor)
            ->post('/users', [
                'name' => 'Pendaftar Baru',
                'username' => 'pendaftarbaru',
                'email' => 'masihaktif@example.com',
                'password' => 'Str0ngPassw0rd2026!',
                'password_confirmation' => 'Str0ngPassw0rd2026!',
                'role' => 'operator',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_index_search_matches_name_email_and_username(): void
    {
        $actor = $this->superadmin();

        User::factory()->create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
        ])->assignRole('operator');

        User::factory()->create([
            'name' => 'Siti Aminah',
            'username' => 'siti',
            'email' => 'siti@example.com',
        ])->assignRole('operator');

        // Cocok lewat nama.
        $this->actingAs($actor)
            ->get('/users?search=Budi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'budi'));

        // Cocok lewat username.
        $this->actingAs($actor)
            ->get('/users?search=siti')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'siti'));

        // Cocok lewat email.
        $this->actingAs($actor)
            ->get('/users?search=budi@example.com')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'budi'));
    }

    public function test_index_search_is_case_insensitive(): void
    {
        $actor = $this->superadmin();

        User::factory()->create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
        ])->assignRole('operator');

        $this->actingAs($actor)
            ->get('/users?search=BUDI')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'budi'));
    }

    public function test_index_search_with_no_match_returns_an_empty_page(): void
    {
        $actor = $this->superadmin();

        User::factory()->create(['name' => 'Budi Santoso'])->assignRole('operator');

        $this->actingAs($actor)
            ->get('/users?search=tidakadasamasekali')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
    }

    public function test_index_status_filter_narrows_results(): void
    {
        $actor = $this->superadmin();

        User::factory()->create(['name' => 'Aa Aktif'])->assignRole('operator');
        User::factory()->inactive()->create(['name' => 'Bb Nonaktif'])->assignRole('operator');

        $this->actingAs($actor)
            ->get('/users?status=active')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 2)
                ->where('users.data.0.name', 'Aa Aktif')
                ->where('users.data.0.is_active', true));

        $this->actingAs($actor)
            ->get('/users?status=inactive')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Bb Nonaktif')
                ->where('users.data.0.is_active', false));
    }
}
