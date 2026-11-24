import { ref } from 'vue';

const KEY = 'navigation-layout';
const LAYOUTS = ['sidebar', 'topbar'];
const DEFAULT_LAYOUT = 'sidebar';

function read() {
    try {
        const stored = localStorage.getItem(KEY);

        if (LAYOUTS.includes(stored)) {
            return stored;
        }

        return DEFAULT_LAYOUT;
    } catch (e) {
        return DEFAULT_LAYOUT;
    }
}

const layout = ref(read());

/**
 * Preferensi tampilan navigasi perangkat.
 *
 * Nilai yang disimpan hanya dua: sidebar atau topbar. Nilai lama/tidak dikenal
 * dikembalikan ke sidebar supaya preferensi yang rusak tidak memecahkan layout.
 */
export function useNavigationLayout() {
    function setLayout(next) {
        if (!LAYOUTS.includes(next) || layout.value === next) {
            return;
        }

        layout.value = next;

        try {
            localStorage.setItem(KEY, next);
        } catch (e) {}
    }

    function toggleLayout() {
        setLayout(layout.value === 'sidebar' ? 'topbar' : 'sidebar');
    }

    return { layout, setLayout, toggleLayout };
}
