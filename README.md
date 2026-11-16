# Starterkit Laravel + Inertia/Vue + DaisyUI

Starterkit aplikasi admin berbasis **Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 + DaisyUI 5**, sudah dilengkapi autentikasi, otorisasi berbasis peran, impersonation, feature flag runtime, dan layout dashboard dengan menu sidebar dinamis.

## Fitur

- **Auth** — login, logout, remember me, dan autentikasi dua faktor (TOTP + kode pemulihan) lewat Laravel Fortify dengan tampilan Inertia/Vue. Registrasi mandiri, reset password, dan verifikasi email sengaja dinonaktifkan di `config/fortify.php`; akun baru dibuat dari halaman Pengguna, sedangkan akun `superadmin` awal berasal dari seeder.
- **Passkey** — pendaftaran dan pengelolaan ada di **Pengaturan → Keamanan**: tambah passkey (nama + konfirmasi password, lalu prompt sidik jari/wajah/PIN perangkat), lihat daftar authenticator, dan hapus. Halaman login punya tombol **Masuk dengan passkey** yang hanya tampil bila flag `passkeys-login` aktif dan browser mendukung WebAuthn; status fitur dibagikan ke UI lewat shared prop Inertia, jadi mematikan flag membuat rute passkey membalas 404 dan tombolnya disembunyikan, sedangkan menghapus `Features::passkeys` di `config/fortify.php` membuat rutenya tidak didaftarkan sama sekali. Login passkey memakai `GET /passkeys/login/options` → `POST /passkeys/login` dengan rate limit dan pemeriksaan status akun. WebAuthn hanya berjalan di HTTPS atau `localhost`.
- **Catatan 2FA**: login passkey langsung membuat sesi, sehingga pengguna yang juga mengaktifkan 2FA tidak diminta kode TOTP saat masuk dengan passkey. Passkey diperlakukan sebagai faktor autentikasi kuat; ubah perilaku ini bila kebijakan mewajibkan TOTP pada setiap login.
- **RBAC** — role `superadmin` dibuat oleh seeder dengan permission `user.manage`, `role.manage`, `feature.manage`, dan `audit.view` lewat `spatie/laravel-permission`. Role lain dibuat dari halaman **RBAC**, dan permission tiap role diatur di sana; kolom superadmin dikunci karena selalu lolos pengecekan permission.
- **Feature Flags** — halaman `/features` untuk mengaktifkan/menonaktifkan fitur saat runtime lewat `laravel/pennant` tanpa deploy ulang. Flag yang tersedia: `passkeys-login` dan `magic-link-login`. Flag bersifat global untuk tamu dan semua pengguna; saat nonaktif rute terkait membalas 404 dan elemen UI-nya (bila ada) disembunyikan, dan setiap perubahan dicatat di activity log.
- **Dashboard** — halaman kosong siap diisi.
- **Manajemen pengguna** — daftar dengan pencarian & filter status, tambah/ubah lewat modal, hapus dengan konfirmasi, dan ubah status massal. Pengguna tidak bisa menghapus/menonaktifkan akunnya sendiri.
- **Impersonation** — superadmin bisa masuk sebagai pengguna lain, keluar lewat banner, dan setiap sesinya tercatat di audit log.
- **Pengaturan akun** — tiga tab: **Akun** (profil & password), **Keamanan** (2FA, passkey, daftar sesi & perangkat, keluarkan sesi lain), dan **Riwayat Login** (20 percobaan login terakhir beserta IP dan perangkat/user agent).
- **Keamanan sesi** — setiap sesi aktif tercatat di tabel `user_sessions` per session ID, sehingga pengguna bisa melihat dan mengeluarkan perangkat lain. Sesi diinvalidasi saat akun dinonaktifkan, cookie sesi otomatis `Secure` bila `APP_URL` memakai `https://`, dan baris kedaluwarsa dibersihkan scheduler tiap jam.
- **Layout** — sidebar yang bisa diciutkan (tersimpan di localStorage), drawer di mobile, pengganti tema DaisyUI, toast, skeleton saat navigasi.
- **Audit** — `spatie/laravel-activitylog` mencatat aktivitas impersonation dan perubahan feature flag. Riwayat login dicatat terpisah oleh listener `RecordLoginHistory`; halaman **Audit Log** menampilkan jejak impersonation dan hanya bisa dibuka pengguna dengan permission `audit.view`; perubahan feature flag memakai `log_name = feature` (belum ditampilkan di halaman Audit Log); penghapusan pengguna memakai soft delete.

## Tech Stack

- PHP 8.4 / Laravel 13
- PostgreSQL (produksi), SQLite in-memory (testing)
- Redis — session, cache, queue (klien predis)
- Laravel Sanctum, Fortify, Passkeys, Pennant, Reverb (opsional), Spatie permission & activitylog
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

Seeder membuat akun dan role `superadmin` dengan password dari `SEED_SUPERADMIN_PASSWORD` (default `password` saat environment local). Di luar local variabel ini wajib diisi dengan password yang kuat; seeder menolak nilai kosong maupun nilai contoh `password`. Emailnya `superadmin@example.com`. Role lain tidak dibuat otomatis; buat lewat halaman RBAC.

### Scheduler

`routes/console.php` menjadwalkan `php artisan user-sessions:prune` setiap jam untuk menghapus baris `user_sessions` yang melewati `SESSION_LIFETIME`. Perintahnya juga bisa dijalankan manual.

```bash
php artisan user-sessions:prune   # hapus baris sesi kedaluwarsa sekarang
php artisan schedule:work         # jalankan scheduler di lokal
```

Di server, tambahkan cron `* * * * * php artisan schedule:run`. Tanpa cron, baris sesi kedaluwarsa baru dibersihkan saat pemiliknya membuka halaman Pengaturan; logout dan regenerasi sesi hanya menghapus baris terkait.

## Peran & Permission

| Peran | Hak akses | Akses halaman |
|---|---|---|
| `superadmin` | semua permission lewat `Gate::before`, plus ability `user.impersonate`; permission yang di-seed: `user.manage`, `role.manage`, `feature.manage`, `audit.view` | seluruh menu, termasuk Audit Log, Feature Flags, dan impersonation |
| peran lain | permission yang dicentang untuk peran tersebut di halaman RBAC | menu dan aksi mengikuti permission |

Seeder hanya membuat role `superadmin`; role lain (mis. `admin` atau `operator`) dibuat lewat halaman RBAC dan belum memiliki permission sampai dicentang.

Menu sidebar disaring otomatis berdasarkan permission pengguna (`resources/js/lib/useAuth.js` → `can()`), sehingga menambah menu cukup dengan mengisi `permission` pada daftar `sidebarGroups` di `resources/js/lib/navigation.js`. Menu Audit Log memakai permission `audit.view`. Halaman Pengaturan tidak ada di sidebar: setiap pengguna membukanya lewat ikon roda gigi di kanan atas navbar.

Halaman RBAC (`/rbac`) memakai dua kolom: kiri berisi daftar peran dan form tambah peran, kanan berisi matriks permission (baris permission × kolom peran). Matriks punya pencarian dan pengelompokan permission, header serta kolom permission yang tetap terlihat saat di-scroll, dan perubahan disimpan per peran lewat tombol **Simpan** (bisa dibatalkan sebelum dikirim). Ada aksi pilih semua per peran maupun per kelompok permission, sedangkan kolom superadmin dikunci. Peran selain superadmin punya aksi ubah nama dan hapus: peran yang masih dipakai pengguna tidak bisa dihapus, dan superadmin tidak bisa diubah namanya atau dihapus. Peran baru dibuat tanpa permission dan langsung bisa diatur di matriks. Label peran dan permission untuk UI ada di `resources/js/lib/labels.js`.

Menambah permission baru:

1. Tambahkan namanya di `database/seeders/RolePermissionSeeder.php`, lalu `php artisan db:seed --class=RolePermissionSeeder`.
2. Lindungi route dengan middleware `can:nama.permission`.
3. Daftarkan menu dengan `permission: 'nama.permission'`.

## Feature Flags

Halaman **Feature Flags** (`/features`) mengubah status fitur pada runtime tanpa deploy ulang. Flag disimpan di tabel `features` lewat `laravel/pennant` dan sengaja memakai scope global (`Feature::resolveScopeUsing(fn () => null)` di `AppServiceProvider`), sehingga satu saklar berlaku untuk tamu maupun semua pengguna — bukan hanya akun yang menekan tombol.

| Flag | Default | Efek saat nonaktif |
|---|---|---|
| `passkeys-login` | mengikuti `Features::passkeys` di `config/fortify.php` | Rute `passkey.*` membalas 404; tombol login passkey dan panel passkey di Pengaturan disembunyikan. |
| `magic-link-login` | mengikuti `email-magic-link.enabled` | Seluruh rute magic link membalas 404. |

Config paket tetap menjadi saklar utama: bila fitur dimatikan di `config/fortify.php` atau `config/email-magic-link.php`, rutenya tidak didaftarkan sama sekali dan flag tidak bisa menghidupkannya. Mengaktifkan/menonaktifkan flag memerlukan permission `feature.manage`; penonaktifan meminta konfirmasi, dan setiap perubahan dicatat di activity log dengan `log_name = feature` serta event `feature_enabled`/`feature_disabled`.

Menambah flag baru:

1. Buat class di `app/Features` dengan atribut `#[Name('slug-flag')]` dan method `resolve()` untuk nilai default.
2. Daftarkan slug, class, label, dan deskripsi di `FeatureFlagController::FLAGS`.
3. Pasang pengecekan `Feature::active(...)` pada rute/middleware dan shared prop UI yang perlu digating.

## Struktur

```
app/Features          Passkeys, MagicLinkLogin (class flag Pennant beserta nilai default)
app/Http/Controllers  AuditLogController, AuthController, DashboardController, FeatureFlagController, ImpersonationController, RbacController, SecurityController, SettingsController, UsersController
app/Http/Middleware   CheckUserStatus (blokir & logout akun nonaktif), EnsurePasskeysAreEnabled (404 rute passkey saat flag nonaktif), HandleInertiaRequests (shared props), TrackUserSession (registry sesi)
app/Listeners         ForgetUserSession (hapus registry saat logout), RecordLoginHistory
app/Models            User, Role, Permission, Passkey, Activity, LoginHistory, UserSession
resources/js/Layouts  AppLayout (sidebar + navbar), GuestLayout
resources/js/pages    Dashboard, audit/Index, auth/*, features/Index, rbac/Index, settings/Index (tab Akun, Keamanan, Riwayat Login), users/Index
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

## Keamanan (variabel lingkungan)

- `SESSION_ENCRYPT` — enkripsi payload sesi di storage (default `true`). Set `false` hanya bila perlu debugging.
- `SESSION_SECURE_COOKIE` — paksa cookie sesi `Secure`. Kosongkan bila `APP_URL` sudah `https://` (otomatis).
- `PASSKEYS_RELYING_PARTY_ID` / `PASSKEYS_ALLOWED_ORIGINS` — override RP ID dan origin passkey (kosongkan untuk memakai `APP_URL`).
- `PASSKEYS_USER_HANDLE_SECRET` — secret terpisah untuk menurunkan user handle passkey. Isi dengan nilai acak panjang di produksi agar rotasi `APP_KEY` tidak memutus passkey pengguna dan kebocoran `APP_KEY` tidak langsung membocorkan handle. Kosongkan untuk memakai `APP_KEY` (hanya dev).

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
