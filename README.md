# Starterkit Laravel + Inertia/Vue + DaisyUI

Starterkit aplikasi admin berbasis **Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 + DaisyUI 5**, dengan autentikasi, RBAC, feature flag runtime, impersonation, manajemen pengguna, pengaturan akun, audit log, dan keamanan sesi.

## Fitur Utama

- **Auth**: login, logout, remember me, 2FA (TOTP + recovery codes), passkey, dan verifikasi email lewat Laravel Fortify + Inertia/Vue.
- **RBAC**: role `superadmin` dari seeder; role lain dibuat dari halaman RBAC.
- **Feature Flags**: saklar runtime `passkeys-login` dan `magic-link-login` via Laravel Pennant.
- **Manajemen Pengguna**: daftar, filter, tambah/ubah modal, hapus, ubah status massal.
- **Password Akun Baru**: dibuat otomatis (OWASP), ditampilkan sekali, pengguna wajib ganti saat login pertama.
- **Impersonation**: superadmin masuk sebagai pengguna lain, tercatat di audit log.
- **Pengaturan Akun**: profil, password, 2FA, passkey, sesi perangkat, riwayat login.
- **Keamanan Sesi**: registry `user_sessions`, prune tiap jam, cookie Secure otomatis untuk HTTPS.
- **Audit**: impersonation dan perubahan konfigurasi/feature flag dicatat via Spatie Activitylog.
- **Layout**: sidebar 3 kondisi, drawer mobile, tema DaisyUI, toast, skeleton navigasi.

## Tech Stack

- PHP 8.4 / Laravel 13
- PostgreSQL (produksi), SQLite in-memory (testing)
- Redis: session, cache, queue (predis)
- Laravel Sanctum, Fortify, Passkeys, Pennant, Reverb (opsional), Spatie permission & activitylog
- Inertia.js + Vue 3
- Vite + Tailwind CSS 4 + DaisyUI 5
- Bun sebagai package manager & script runner

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
bun install && bun run build
composer run dev
```

> Gunakan Bun, jangan npm.

Seeder membuat:
- role `superadmin` dan akun `superadmin@example.com`
- akun contoh `user01` (role `user`, password `passworduser`, email belum terverifikasi, wajib ganti password)

## Scheduler

```bash
php artisan user-sessions:prune
php artisan schedule:work
```

Di server, tambahkan cron:

```bash
* * * * * php artisan schedule:run
```

## Peran & Permission

| Peran | Akses |
|---|---|
| `superadmin` | semua permission lewat `Gate::before`, plus `user.impersonate`; seeded: `user.manage`, `role.manage`, `feature.manage`, `audit.view` |
| peran lain | permission yang dicentang di halaman RBAC |

Menu sidebar otomatis disaring berdasarkan permission pengguna.

## Feature Flags

Halaman `/features` mengubah status fitur runtime:

| Flag | Default | Efek saat nonaktif |
|---|---|---|
| `passkeys-login` | mengikuti `Features::passkeys` | rute passkey membalas 404, tombol/panel disembunyikan |
| `magic-link-login` | mengikuti `email-magic-link.enabled` (default false) | seluruh rute magic link tidak didaftarkan |

Config paket tetap saklar utama; flag Pennant hanya saklar runtime di atasnya.

## Deploy di Sub-Path

Isi `APP_URL` beserta prefix, mis. `https://app.example.com/admin`, dan set `SESSION_PATH=/admin`. Reverse proxy harus membuang prefix sebelum request ke Laravel.

Cookie sesi otomatis `Secure` bila `APP_URL` memakai `https://`. Jika berada di balik reverse proxy, isi `TRUSTED_PROXIES`.

## Keamanan (Env)

- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE` kosongkan bila `APP_URL` sudah HTTPS
- `PASSKEYS_RELYING_PARTY_ID`, `PASSKEYS_ALLOWED_ORIGINS`, `PASSKEYS_USER_HANDLE_SECRET` (opsional)
- `SECURITY_HEADERS_ENABLED=true` untuk CSP, HSTS, dan header keamanan lain
- SMTP tetap dikonfigurasi lewat `.env` (`MAIL_MAILER`, `MAIL_HOST`, dll.)

## Testing

```bash
composer test
vendor/bin/pint --dirty --format agent
bun run test:js
bun run build
```

`phpunit.xml` memakai SQLite in-memory.

## Realtime (Opsional)

`laravel/reverb` sudah terpasang tetapi belum dipakai. Aktifkan lewat channel di `routes/channels.php`, isi `REVERB_*`, lalu jalankan `php artisan reverb:start`.
