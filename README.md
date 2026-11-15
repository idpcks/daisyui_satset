# Starterkit Laravel + Inertia/Vue + DaisyUI

Starterkit aplikasi admin berbasis **Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 + DaisyUI 5**, sudah dilengkapi autentikasi, otorisasi berbasis peran, dan layout dashboard dengan menu sidebar dinamis.

## Fitur

- **Auth** — login, logout, remember me, reset password, autentikasi dua faktor (TOTP + kode pemulihan), dan passkey. Semua ditangani Laravel Fortify, tampilannya Inertia/Vue.
- **RBAC** — tiga peran bawaan (`superadmin`, `admin`, `operator`) dengan permission global `user.manage` lewat `spatie/laravel-permission`. Superadmin selalu lolos pengecekan permission.
- **Dashboard** — halaman kosong siap diisi.
- **Manajemen pengguna** — daftar dengan pencarian & filter status, tambah/ubah lewat modal, hapus dengan konfirmasi. Pengguna tidak bisa menghapus/menonaktifkan akunnya sendiri.
- **Pengaturan keamanan** — aktivasi 2FA, QR code, kode pemulihan.
- **Layout** — sidebar yang bisa diciutkan (tersimpan di localStorage), drawer di mobile, pengganti tema DaisyUI, toast, skeleton saat navigasi.
- **Audit** — `spatie/laravel-activitylog` dan soft delete pada tabel pengguna.

## Tech Stack

- PHP 8.4 / Laravel 13
- PostgreSQL (produksi), SQLite in-memory (testing)
- Redis — session, cache, queue (klien predis)
- Laravel Sanctum, Fortify, Reverb (opsional), Spatie permission & activitylog
- Inertia.js + Vue 3 (JavaScript, tanpa vue-router)
- Vite + Tailwind CSS 4 + DaisyUI 5, ikon lucide-vue-next
- Bun sebagai package manager & script runner

## Setup

```bash
composer install
cp .env.example .env      # isi APP_KEY, DB_*, REDIS_*
php artisan key:generate
php artisan migrate --seed
bun install && bun run build
composer run dev          # atau: php artisan dev
```

> Proyek memakai **Bun** (lockfile `bun.lock`). Jangan jalankan `npm install`.

Seeder membuat akun `superadmin` dengan password dari `SEED_SUPERADMIN_PASSWORD` (default `password` saat environment local). Emailnya `superadmin@example.com`.

## Peran & Permission

| Peran | Permission | Akses |
|---|---|---|
| `superadmin` | semua (juga lewat `Gate::before`) | seluruh menu |
| `admin` | `user.manage` | Dashboard, Pengguna |
| `operator` | — | Dashboard |

Menu sidebar disaring otomatis berdasarkan permission pengguna (`resources/js/lib/useAuth.js` → `can()`), sehingga menambah menu cukup dengan mengisi `permission` pada daftar `groups` di `AppLayout.vue`. Halaman Pengaturan tidak ada di sidebar: setiap pengguna membukanya lewat ikon roda gigi di kanan atas navbar.

Menambah permission baru:

1. Tambahkan namanya di `database/seeders/RolePermissionSeeder.php`, lalu `php artisan db:seed --class=RolePermissionSeeder`.
2. Lindungi route dengan middleware `can:nama.permission`.
3. Daftarkan menu dengan `permission: 'nama.permission'`.

## Struktur

```
app/Http/Controllers  AuthController, DashboardController, SecurityController, SettingsController, UsersController
app/Http/Middleware   CheckUserStatus (blokir akun nonaktif), HandleInertiaRequests (shared props)
app/Models            User, Role, Permission, Passkey, Activity
resources/js/Layouts  AppLayout (sidebar + navbar), GuestLayout
resources/js/pages    Dashboard, auth/*, settings/Index (tab Akun & Keamanan), users/Index
resources/js/components/settings  AccountPanel, SecurityPanel
resources/js/lib      useAuth, useSidebar, useTheme, useToast, url (appUrl)
```

URL di sisi klien selalu dibentuk lewat `appUrl()` dari `resources/js/lib/url.js` agar tetap benar saat aplikasi dipasang di sub-path.

## Deploy di Sub-Path

Isi `APP_URL` beserta prefix-nya, mis. `https://app.example.com/admin`, dan set `SESSION_PATH=/admin`. Reverse proxy harus membuang prefix sebelum meneruskan request ke Laravel. `AppServiceProvider` menyesuaikan `URL::forceRootUrl`/`forceScheme` dan `basePath` dikirim ke Vue sebagai shared prop.

## Testing

```bash
composer test          # config:clear + php artisan test
vendor/bin/pint --dirty --format agent
bun run test:js
bun run build
```

## Realtime (opsional)

`laravel/reverb` dan `config/reverb.php` sudah terpasang tetapi belum dipakai halaman mana pun. Untuk mengaktifkan, buat channel di `routes/channels.php`, isi `REVERB_*` pada `.env`, lalu jalankan `php artisan reverb:start`. Bila tidak dipakai, paket dan konfigurasinya bisa dihapus.
