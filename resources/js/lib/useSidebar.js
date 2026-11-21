import { ref } from 'vue';

const KEY = 'sidebar-collapsed';
const STATES = ['expanded', 'hidden'];

// Versi lama menyimpan 'expanded'/'collapsed'/'hidden' (atau '1'/'0'). State
// `collapsed` (rail ikon) sudah dihapus, jadi nilainya dipetakan ke
// `expanded` (sidebar penuh) supaya preferensi pengguna yang tersimpan tidak
// hilang.
function read() {
    try {
        const stored = localStorage.getItem(KEY);

        if (STATES.includes(stored)) {
            return stored;
        }

        return 'expanded';
    } catch (e) {
        return 'expanded';
    }
}

// Urutan dua state: penuh -> disembunyikan -> penuh.
const NEXT_STATE = {
    expanded: 'hidden',
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

    // Satu tombol menggilir dua kondisi secara berurutan.
    function toggle() {
        setState(NEXT_STATE[state.value] ?? 'expanded');
    }

    return { state, setState, toggle };
}