/**
 * Label tampilan untuk peran dan permission. Nama teknis tetap dipakai untuk
 * komunikasi dengan backend; modul ini hanya untuk teks di UI.
 *
 * Parameter `translate` opsional supaya komponen bisa meneruskan helper
 * `t()` dari `useI18n()`; tanpa itu label jatuh ke teks sumber Bahasa
 * Indonesia.
 */
const roleLabels = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    operator: 'Operator',
};

const permissionLabels = {
    'user.manage': 'Kelola pengguna',
    'role.manage': 'Kelola peran & permission',
    'audit.view': 'Melihat audit log',
};

const identity = (key) => key;

/**
 * Ubah nama teknis peran buatan sendiri menjadi judul yang enak dibaca.
 *
 * @param {string} role
 * @returns {string}
 */
const prettifyRole = (role) =>
    role
        .split(/[._-]+/)
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

/**
 * @param {string} role
 * @param {(key: string) => string} [translate]
 * @returns {string}
 */
export const roleLabel = (role, translate = identity) => {
    if (!role) {
        return '-';
    }

    return roleLabels[role] ? translate(roleLabels[role]) : prettifyRole(role);
};

/**
 * @param {string} permission
 * @param {(key: string) => string} [translate]
 * @returns {string}
 */
export const permissionLabel = (permission, translate = identity) =>
    permissionLabels[permission] ? translate(permissionLabels[permission]) : permission;

/**
 * Label kelompok permission berdasarkan prefix nama teknisnya.
 *
 * @param {string} group
 * @param {(key: string) => string} [translate]
 * @returns {string}
 */
export const permissionGroupLabel = (group, translate = identity) =>
    group === 'lainnya' ? translate('Lainnya') : prettifyRole(group);
