/**
 * Pemanggil endpoint JSON (di luar Inertia) dengan header CSRF yang sama
 * seperti yang dipakai axios milik Inertia.
 *
 * Dipakai untuk rute bawaan Fortify/Passkeys yang mengembalikan JSON, karena
 * form Inertia selalu mengharapkan respons redirect/Inertia.
 */
import { appUrl } from '@/lib/url';

export class RequestError extends Error {
    /**
     * @param {string} message
     * @param {{ status?: number, errors?: Record<string, string[]> }} [details]
     */
    constructor(message, { status = 0, errors = {} } = {}) {
        super(message);

        this.name = 'RequestError';
        this.status = status;
        this.errors = errors;
    }

    /**
     * Pesan error pertama untuk satu field, bila ada.
     *
     * @param {string} field
     * @returns {string|null}
     */
    fieldError(field) {
        return this.errors?.[field]?.[0] ?? null;
    }
}

const xsrfToken = () => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
};

/**
 * Kirim request JSON ke path aplikasi (base path otomatis ditambahkan).
 *
 * @param {string} path
 * @param {{ method?: string, body?: unknown }} [options]
 * @returns {Promise<any>} body JSON, atau null bila respons kosong
 * @throws {RequestError}
 */
export async function requestJson(path, { method = 'GET', body } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const token = xsrfToken();

    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(appUrl(path), {
        method,
        credentials: 'same-origin',
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (response.status === 204 || response.status === 205) {
        return null;
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        throw new RequestError(data?.message ?? 'Permintaan gagal diproses.', {
            status: response.status,
            errors: data?.errors ?? {},
        });
    }

    return data;
}
