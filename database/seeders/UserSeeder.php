<?php

namespace Database\Seeders;

use App\Exceptions\SeedingException;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('seeding.superadmin_password');

        if (blank($password)) {
            if (! app()->isLocal()) {
                throw new SeedingException(
                    'Set SEED_SUPERADMIN_PASSWORD sebelum menjalankan seeder di luar environment local.'
                );
            }

            $password = 'password';
        }

        $user = User::withTrashed()->firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make($password),
                'is_active' => true,
                'created_by' => 'system',
                'updated_by' => 'system',
            ],
        );

        if ($user->trashed()) {
            $user->restore();
        }

        if (! $user->is_active) {
            $user->update(['is_active' => true]);
        }

        if (! $user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }
    }
}
