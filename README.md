# Starterkit Laravel + Inertia/Vue + DaisyUI

Starterkit aplikasi admin berbasis **Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 + DaisyUI 5**, sudah dilengkapi autentikasi, otorisasi berbasis peran, impersonation, dan layout dashboard dengan menu sidebar dinamis.

## Fitur

- **Auth** — login, logout, remember me, dan autentikasi dua faktor (TOTP + kode pemulihan) lewat Laravel Fortify dengan tampilan Inertia/Vue. Registrasi mandiri, reset password, dan verifikasi email sengaja dinonaktifkan di `config/fortify.php`; akun dibuat dari halaman Pengguna.
- **Passkey** — pendaftaran dan pengelolaan ada di **Pengaturan → Keamanan**: tambah passkey (nama + konfirmasi password, lalu prompt sidik jari/wajah/PIN perangkat), lihat daftar authenticator, dan hapus. Rute login passkey juga aktif di backend (`POST /passkeys/login`, dengan rate limit dan pemeriksaan status akun), meski tombol login passkey di halaman masuk belum ada. WebAuthn hanya berjalan di HTTPS atau `localhost`.
- **RBAC** — tiga peran bawaan (`superadmin`, `admin`, `operator`) dengan permission `user.manage` dan `role.manage` lewat `spatie/laravel-permission`. Halaman **RBAC** mengatur permission tiap peran; daftar superadmin dikunci karena selalu lolos pengecekan permission.
- **Dashboard** — halaman kosong siap diisi.
- **Manajemen pengguna** — daftar dengan pencarian & filter status, tambah/ubah lewat modal, hapus dengan konfirmasi, dan ubah status massal. Pengguna tidak bisa menghapus/menonaktifkan akunnya sendiri.
- **Impersonation** — superadmin bisa masuk sebagai pengguna lain, keluar lewat banner, dan setiap sesinya tercatat di audit log.
- **Pengaturan akun** — tiga tab: **Akun** (profil & password), **Keamanan** (2FA, passkey, daftar sesi & perangkat, keluarkan sesi lain), dan **Riwayat Login** (20 percobaan login terakhir beserta IP dan user agent).
- **Keamanan sesi** — setiap sesi aktif tercatat di tabel `user_sessions` per session ID, sehingga pengguna bisa melihat dan mengeluarkan perangkat lain. Sesi diinvalidasi saat akun dinonaktifkan, cookie sesi otomatis `Secure` bila `APP_URL` memakai `https://`, dan baris kedaluwarsa dibersihkan scheduler tiap jam.
- **Layout** — sidebar yang bisa diciutkan (tersimpan di localStorage), drawer di mobile, pengganti tema DaisyUI, toast, skeleton saat navigasi.
- **Audit** — `spatie/laravel-activitylog` untuk perubahan data, riwayat login, halaman **Audit Log** untuk jejak impersonation, dan soft delete pada tabel pengguna.

## Tech Stack

- PHP 8.4 / Laravel 13
- PostgreSQL (produksi), SQLite in-memory (testing)
- Redis — session, cache, queue (klien predis)
- Laravel Sanctum, Fortify, Passkeys, Reverb (opsional), Spatie permission & activitylog
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

Seeder membuat akun `superadmin` dengan password dari `SEED_SUPERADMIN_PASSWORD` (default `password` saat environment local). Di luar local variabel ini wajib diisi dengan password yang kuat; seeder menolak nilai kosong maupun nilai contoh `password`. Emailnya `superadmin@example.com`.

### Scheduler

`routes/console.php` menjadwalkan `php artisan user-sessions:prune` setiap jam untuk menghapus baris `user_sessions` yang melewati `SESSION_LIFETIME`. Perintahnya juga bisa dijalankan manual.

```bash
php artisan user-sessions:prune   # hapus baris sesi kedaluwarsa sekarang
php artisan schedule:work         # jalankan scheduler di lokal
```

Di server, tambahkan cron `* * * * * php artisan schedule:run`. Tanpa cron, registry sesi hanya dibersihkan saat pemiliknya membuka halaman Pengaturan.

## Peran & Permission

| Peran | Hak akses | Akses halaman |
|---|---|---|
| `superadmin` | semua permission lewat `Gate::before`, plus ability `user.impersonate` dan `view-audit-log` | seluruh menu, termasuk Audit Log dan impersonation |
| `admin` | `user.manage`, `role.manage` | Dashboard, Pengguna, RBAC |
| `operator` | — | Dashboard, Pengaturan |

Menu sidebar disaring otomatis berdasarkan permission pengguna (`resources/js/lib/useAuth.js` → `can()`), sehingga menambah menu cukup dengan mengisi `permission` pada daftar `sidebarGroups` di `resources/js/lib/navigation.js`. Menu dengan `superadminOnly: true` hanya tampil untuk superadmin. Halaman Pengaturan tidak ada di sidebar: setiap pengguna membukanya lewat ikon roda gigi di kanan atas navbar.

Halaman RBAC (`/rbac`) memakai dua kolom: kiri berisi daftar peran dan form tambah peran, kanan berisi matriks permission (baris permission × kolom peran). Matriks punya pencarian dan pengelompokan permission, header serta kolom permission yang tetap terlihat saat di-scroll, dan perubahan disimpan per peran lewat tombol **Simpan** (bisa dibatalkan sebelum dikirim). Ada aksi pilih semua per peran maupun per kelompok permission, sedangkan kolom superadmin dikunci. Tiap peran punya aksi ubah nama dan hapus: peran yang masih dipakai pengguna tidak bisa dihapus, dan superadmin tidak bisa diubah namanya atau dihapus. Peran baru dibuat tanpa permission dan langsung bisa diatur di matriks. Label peran dan permission untuk UI ada di `resources/js/lib/labels.js`.

Menambah permission baru:

1. Tambahkan namanya di `database/seeders/RolePermissionSeeder.php`, lalu `php artisan db:seed --class=RolePermissionSeeder`.
2. Lindungi route dengan middleware `can:nama.permission`.
3. Daftarkan menu dengan `permission: 'nama.permission'`.

## Struktur

```
app/Http/Controllers  AuditLogController, AuthController, DashboardController, ImpersonationController, RbacController, SecurityController, SettingsController, UsersController
app/Http/Middleware   CheckUserStatus (blokir & logout akun nonaktif), HandleInertiaRequests (shared props), TrackUserSession (registry sesi)
app/Listeners         ForgetUserSession (hapus registry saat logout), RecordLoginHistory
app/Models            User, Role, Permission, Passkey, Activity, LoginHistory, UserSession
resources/js/Layouts  AppLayout (sidebar + navbar), GuestLayout
resources/js/pages    Dashboard, audit/Index, auth/*, rbac/Index, settings/Index (tab Akun, Keamanan, Riwayat Login), users/Index
resources/js/components/ui  EmptyState, Footer, ImpersonationBanner, Modal, PageSkeleton, Toast
resources/js/components/rbac  PermissionMatrix (matriks checkbox permission)
resources/js/components/settings  AccountPanel, PasswordPanel, SecurityPanel, PasskeysPanel, OtherSessionsPanel, LoginHistoryPanel
resources/js/components/users  UserForm
resources/js/lib      routes, navigation, useNavigation, labels, useAuth, useSidebar, useTheme, useToast, userAgent, url (appUrl), http (requestJson), webauthn
```

URL di sisi klien selalu dibentuk lewat `appUrl()` dari `resources/js/lib/url.js` agar tetap benar saat aplikasi dipasang di sub-path.

Semua path ada di `resources/js/lib/routes.js`, sedangkan struktur menu sidebar dan aksi topbar ada di `resources/js/lib/navigation.js`. `useNavigation()` menyaring menu sesuai permission dan menyediakan penanda menu aktif, jadi `AppLayout.vue` hanya merender hasilnya — menambah menu cukup mengubah satu file konfigurasi.

## Deploy di Sub-Path

Isi `APP_URL` beserta prefix-nya, mis. `https://app.example.com/admin`, dan set `SESSION_PATH=/admin`. Reverse proxy harus membuang prefix sebelum meneruskan request ke Laravel. `AppServiceProvider` menyesuaikan `URL::forceRootUrl`/`forceScheme` dan `basePath` dikirim ke Vue sebagai shared prop.

Saat `APP_URL` memakai `https://`, `AppServiceProvider` juga memaksa cookie sesi `Secure`, sehingga tidak perlu mengisi `SESSION_SECURE_COOKIE`. Jangan lupa cron `schedule:run` agar prune sesi berjalan (lihat bagian **Scheduler**).

## Testing

```bash
composer test          # config:clear + php artisan test
vendor/bin/pint --dirty --format agent
bun run test:js
bun run build
```

`phpunit.xml` memakai SQLite in-memory. Bila ekstensi `pdo_sqlite` tidak tersedia di mesin Anda, jalankan test dengan PostgreSQL: `DB_CONNECTION=pgsql DB_DATABASE=nama_database_test php artisan test`.

## Realtime (opsional)

`laravel/reverb` dan `config/reverb.php` sudah terpasang tetapi belum dipakai halaman mana pun. Untuk mengaktifkan, buat channel di `routes/channels.php`, isi `REVERB_*` pada `.env`, lalu jalankan `php artisan reverb:start`. Bila tidak dipakai, paket dan konfigurasinya bisa dihapus.
