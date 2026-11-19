/**
 * Helper terjemahan frontend berbasis Inertia shared props.
 *
 * Kunci adalah teks sumber (Bahasa Indonesia); nilai adalah terjemahan
 * sesuai locale aktif. Parameter interpolasi memakai gaya Laravel: `:name`.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useI18n() {
    const page = usePage();

    const translations = computed(() => page.props.translations || {});
    const locale = computed(() => page.props.locale || 'id');

    const t = (key, replacements = {}) => {
        let text = Object.prototype.hasOwnProperty.call(translations.value, key)
            ? translations.value[key]
            : key;

        Object.entries(replacements || {}).forEach(([name, value]) => {
            text = String(text).replace(
                new RegExp(`:${name}\\b`, 'g'),
                String(value),
            );
        });

        return text;
    };

    return { t, locale, translations };
}

export default useI18n;