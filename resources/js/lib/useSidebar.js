import { ref } from 'vue';

const KEY = 'sidebar-collapsed';

function read() {
    try {
        return localStorage.getItem(KEY) === '1';
    } catch (e) {
        return false;
    }
}

const collapsed = ref(read());

export function useSidebar() {
    function toggle() {
        collapsed.value = !collapsed.value;
        try {
            localStorage.setItem(KEY, collapsed.value ? '1' : '0');
        } catch (e) {}
    }

    return { collapsed, toggle };
}