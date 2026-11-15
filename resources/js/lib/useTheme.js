import { ref } from 'vue';

const theme = ref(document.documentElement.dataset.theme || 'light');

export function useTheme() {
    function setTheme(name) {
        theme.value = name;
        document.documentElement.dataset.theme = name;
        try {
            localStorage.setItem('theme', name);
        } catch (e) {}
    }

    return { theme, setTheme };
}