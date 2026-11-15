/**
 * Label tampilan untuk peran dan permission. Nama teknis tetap dipakai untuk
 * komunikasi dengan backend; modul ini hanya untuk teks di UI.
 */
const roleLabels = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    operator: 'Operator',
};

const permissionLabels = {
    'user.manage': 'Kelola pengguna',
    'role.manage': 'Kelola peran & permission',
};

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
 * @returns {string}
 */
export const roleLabel = (role) => roleLabels[role] ?? (role ? prettifyRole(role) : '-');

/**
 * @param {string} permission
 * @returns {string}
 */
export const permissionLabel = (permission) => permissionLabels[permission] ?? permission;
