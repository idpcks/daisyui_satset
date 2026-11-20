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

        // Nilai contoh di .env.example sempat memakai "password". Menolaknya di
        // luar local mencegah salinan konfigurasi template menjadi kredensial
        // superadmin yang bisa ditebak.
        if (! app()->isLocal() && $password === 'password') {
            throw new SeedingException(
                'SEED_SUPERADMIN_PASSWORD masih memakai nilai contoh "password"; ganti dengan password yang kuat.'
            );
        }

        $user = User::withTrashed()->firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make($password),
                'is_active' => true,
                'must_change_password' => false,
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
