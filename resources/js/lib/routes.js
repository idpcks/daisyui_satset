/**
 * Satu-satunya sumber path yang dipakai sisi klien.
 *
 * Setiap nilai harus cocok dengan route Laravel di `routes/web.php` atau route
 * bawaan Fortify. Jangan menulis path literal di komponen; impor dari sini.
 */
export const routes = {
    login: '/login',
    logout: '/logout',
    twoFactorChallenge: '/two-factor-challenge',

    dashboard: '/dashboard',
    users: '/users',

    settings: '/settings',
    settingsAccount: '/settings/account',
    settingsPassword: '/settings/password',
    settingsTwoFactor: '/settings/security/two-factor',
    settingsTwoFactorConfirm: '/settings/security/two-factor/confirm',
    settingsRecoveryCodes: '/settings/security/recovery-codes',
};

/**
 * Path detail satu pengguna, mis. untuk update/hapus.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const userPath = (id) => `${routes.users}/${id}`;
