<?php

namespace Tests\Feature;

use App\Exceptions\SeedingException;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_superadmin_with_the_configured_password(): void
    {
        config(['seeding.superadmin_password' => 'kata-sandi-yang-kuat']);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(UserSeeder::class);

        $user = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->assertTrue(Hash::check('kata-sandi-yang-kuat', $user->password));
        $this->assertTrue($user->hasRole('superadmin'));

        // Seeder hanya menyiapkan role superadmin; peran lain dibuat lewat RBAC.
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseHas('roles', ['name' => 'superadmin']);
    }

    public function test_seeder_refuses_the_example_password_outside_local(): void
    {
        config(['seeding.superadmin_password' => 'password']);

        $this->expectException(SeedingException::class);

        $this->seed(UserSeeder::class);
    }

    public function test_seeder_refuses_an_empty_password_outside_local(): void
    {
        config(['seeding.superadmin_password' => null]);

        $this->expectException(SeedingException::class);

        $this->seed(UserSeeder::class);
    }
}
