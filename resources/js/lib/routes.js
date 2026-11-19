/**
 * Satu-satunya sumber path yang dipakai sisi klien.
 *
 * Setiap nilai harus cocok dengan route Laravel di `routes/web.php` atau route
 * bawaan Fortify. Jangan menulis path literal di komponen; impor dari sini.
 */
export const routes = {
    login: '/auth/signin',
    logout: '/logout',
    twoFactorChallenge: '/two-factor-challenge',

    confirmPassword: '/user/confirm-password',
    passkeys: '/user/passkeys',
    passkeysOptions: '/user/passkeys/options',
    passkeyLoginOptions: '/passkeys/login/options',
    passkeyLogin: '/passkeys/login',

    dashboard: '/app',
    users: '/users',
    usersBulkStatus: '/users/bulk-status',
    impersonation: '/impersonation',
    audit: '/audit',
    rbac: '/rbac',
    rbacRoles: '/rbac/roles',
    features: '/features',

    settings: '/settings',
    settingsAccount: '/settings/account',
    settingsPassword: '/settings/password',
    settingsSystem: '/settings/system',
    settingsTwoFactor: '/settings/security/two-factor',
    settingsTwoFactorConfirm: '/settings/security/two-factor/confirm',
    settingsRecoveryCodes: '/settings/security/recovery-codes',
    settingsOtherSessions: '/settings/security/other-sessions',
    settingsSessions: '/settings/security/sessions',
};

/**
 * Path detail satu pengguna, mis. untuk update/hapus.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const userPath = (id) => `${routes.users}/${id}`;

/**
 * Path untuk mulai masuk sebagai pengguna lain.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const userImpersonatePath = (id) => `${routes.users}/${id}/impersonate`;

/**
 * Path satu feature flag untuk diaktifkan/dinonaktifkan.
 *
 * @param {string} name
 * @returns {string}
 */
export const featurePath = (name) => `${routes.features}/${name}`;

/**
 * Path untuk mengganti permission satu peran.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const rolePath = (id) => `${routes.rbacRoles}/${id}`;

/**
 * Path satu passkey untuk dihapus.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const passkeyPath = (id) => `${routes.passkeys}/${id}`;

/**
 * Path untuk mengeluarkan satu sesi perangkat.
 *
 * @param {string|number} id
 * @returns {string}
 */
export const userSessionPath = (id) => `${routes.settingsSessions}/${id}`;
