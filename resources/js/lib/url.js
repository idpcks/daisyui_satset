/**
 * Helper base path untuk deployment di sub-path, mis. /admin.
 *
 * Nilainya berasal dari shared prop Inertia (`basePath`) yang diturunkan dari
 * APP_URL, dipasang sekali saat aplikasi mount. Tanpa prefix, semua fungsi di
 * sini berperilaku seperti biasa.
 */
let basePath = '';

export const setBasePath = (value) => {
    basePath = typeof value === 'string' ? value.replace(/\/+$/, '') : '';
};

export const getBasePath = () => basePath;

/**
 * Tempelkan base path aplikasi ke sebuah path absolut.
 *
 * @param {string} path
 * @returns {string}
 */
export const appUrl = (path) => {
    const normalized = path.startsWith('/') ? path : `/${path}`;

    return `${basePath}${normalized}`;
};
