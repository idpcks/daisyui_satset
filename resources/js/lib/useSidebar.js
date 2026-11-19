import { ref } from 'vue';

const KEY = 'sidebar-collapsed';
const STATES = ['expanded', 'collapsed', 'hidden'];

// Versi lama hanya menyimpan '1' (collapsed) / '0' (expanded); nilai itu tetap
// dibaca supaya pilihan pengguna tidak hilang setelah pembaruan.
function read() {
    try {
        const stored = localStorage.getItem(KEY);

        if (STATES.includes(stored)) {
            return stored;
        }

        return stored === '1' ? 'collapsed' : 'expanded';
    } catch (e) {
        return 'expanded';
    }
}

// Urutan satu tombol toggle: penuh -> rail ikon -> disembunyikan -> penuh.
const NEXT_STATE = {
    expanded: 'collapsed',
    collapsed: 'hidden',
    hidden: 'expanded',
};

const state = ref(read());

export function useSidebar() {
    function setState(next) {
        if (!STATES.includes(next) || state.value === next) {
            return;
        }

        state.value = next;

        try {
            localStorage.setItem(KEY, next);
        } catch (e) {}
    }

    // Satu tombol menggilir ketiga kondisi secara berurutan.
    function toggle() {
        setState(NEXT_STATE[state.value] ?? 'expanded');
    }

    return { state, setState, toggle };
}
