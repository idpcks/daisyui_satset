<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission dasar yang dipakai halaman admin.
     *
     * @var list<string>
     */
    private array $permissions = [
        'user.manage',
        'role.manage',
        'feature.manage',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superadmin = Role::findOrCreate('superadmin', 'web');

        // Superadmin juga lolos semua pengecekan lewat Gate::before di
        // AppServiceProvider; daftar eksplisit ini menjaga role tersebut tetap
        // terbaca lengkap saat diperiksa dari panel.
        $superadmin->syncPermissions($this->permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
