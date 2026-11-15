import { ref } from 'vue';

/**
 * State toast disimpan di level modul supaya bisa dipanggil dari komponen
 * mana pun, tanpa perlu mengoper props atau menyentuh <Toast /> langsung.
 */
const AUTO_DISMISS_MS = 5000;

const toasts = ref([]);
const timers = new Map();
let lastId = 0;

/**
 * Tutup satu toast dan batalkan timer tutup otomatisnya.
 *
 * @param {number} id
 */
function dismiss(id) {
    clearTimeout(timers.get(id));
    timers.delete(id);

    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

/**
 * Tampilkan toast baru.
 *
 * @param {'success' | 'error'} type
 * @param {string} message
 * @param {{ duration?: number }} [options] `duration` dalam milidetik, 0 untuk menonaktifkan tutup otomatis.
 * @returns {number} id toast
 */
function push(type, message, { duration = AUTO_DISMISS_MS } = {}) {
    const id = ++lastId;

    toasts.value = [...toasts.value, { id, type, message }];

    if (duration > 0) {
        timers.set(
            id,
            setTimeout(() => dismiss(id), duration),
        );
    }

    return id;
}

/**
 * @param {string} message
 * @param {{ duration?: number }} [options]
 * @returns {number} id toast
 */
function success(message, options) {
    return push('success', message, options);
}

/**
 * @param {string} message
 * @param {{ duration?: number }} [options]
 * @returns {number} id toast
 */
function error(message, options) {
    return push('error', message, options);
}

export function useToast() {
    return { toasts, success, error, dismiss };
}
