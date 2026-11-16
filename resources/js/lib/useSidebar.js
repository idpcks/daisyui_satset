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

const state = ref(read());

// Kondisi sebelum disembunyikan, dipakai tombol toggle untuk mengembalikan
// sidebar ke rail ikon atau ukuran normal sesuai pilihan terakhir.
let lastVisible = state.value === 'hidden' ? 'expanded' : state.value;

export function useSidebar() {
    function setState(next) {
        if (!STATES.includes(next) || state.value === next) {
            return;
        }

        if (state.value !== 'hidden') {
            lastVisible = state.value;
        }

        state.value = next;

        try {
            localStorage.setItem(KEY, next);
        } catch (e) {}
    }

    // expanded <-> collapsed seperti sebelumnya; saat hidden, sidebar kembali
    // ke kondisi terakhir yang terlihat.
    function toggle() {
        if (state.value === 'hidden') {
            setState(lastVisible);

            return;
        }

        setState(state.value === 'expanded' ? 'collapsed' : 'expanded');
    }

    return { state, setState, toggle };
}
