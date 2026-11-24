<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Globe, Search } from 'lucide-vue-next';
import UserMenu from '@/components/navigation/UserMenu.vue';
import { useI18n } from '@/lib/i18n';
import { useTheme } from '@/lib/useTheme';
import { appUrl } from '@/lib/url';

defineProps({
    searchShortcutLabel: { type: String, default: 'Ctrl K' },
    topbarActions: { type: Array, default: () => [] },
    isActive: { type: Function, required: true },
    showSettings: { type: Boolean, default: true },
    showUserMenu: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    user: { type: Object, default: null },
    safeAvatar: { type: String, default: null },
    initial: { type: String, default: '?' },
});

const emit = defineEmits(['search']);

const page = usePage();
const { theme, setTheme } = useTheme();
const { t } = useI18n();

const themes = ['light', 'dark', 'cupcake', 'corporate'];

const switchLocale = (locale) => {
    router.post(appUrl('locale'), { locale }, { preserveScroll: true });
};

const localeCode = computed(() => String(page.props.locale ?? '').toUpperCase());
</script>

<template>
    <div class="flex min-w-0 items-center justify-end gap-1">
        <!-- Pemicu command palette. -->
        <button
            type="button"
            class="btn btn-ghost btn-sm gap-2 px-2"
            :aria-label="t('Cari menu')"
            :title="t('Cari menu')"
            @click="emit('search')"
        >
            <Search class="size-5" aria-hidden="true" />
            <span :class="compact ? 'hidden xl:inline' : 'hidden md:inline'">{{ t('Cari menu') }}</span>
            <kbd
                class="kbd kbd-sm"
                :class="compact ? 'hidden xl:inline-flex' : 'hidden lg:inline-flex'"
            >{{ searchShortcutLabel }}</kbd>
        </button>

        <!-- Aksi topbar (ikon saja) dari lib/navigation.js. -->
        <template v-for="action in topbarActions" :key="action.key">
            <Link
                v-if="showSettings || action.key !== 'settings'"
                :href="appUrl(action.href)"
                class="btn btn-square btn-ghost btn-sm"
                :class="{ 'btn-active': isActive(action.href) }"
                :aria-label="t(action.label)"
                :title="t(action.label)"
            >
                <component :is="action.icon" class="size-5" aria-hidden="true" />
            </Link>
        </template>

        <!-- Pemilih bahasa: ikon Globe, menu DaisyUI. -->
        <div class="dropdown dropdown-end">
            <button
                type="button"
                class="btn btn-square btn-ghost btn-sm relative"
                tabindex="0"
                :aria-label="localeCode ? `${t('Bahasa')}: ${localeCode}` : t('Bahasa')"
                :title="t('Bahasa')"
            >
                <Globe class="size-5" aria-hidden="true" />
                <span
                    v-if="localeCode"
                    aria-hidden="true"
                    class="absolute -top-1 -right-1 rounded-sm bg-primary px-1 text-[0.625rem] leading-4 font-bold tracking-wide text-primary-content shadow-sm"
                >
                    {{ localeCode }}
                </span>
            </button>
            <ul class="menu dropdown-content z-50 mt-1 w-40 rounded-box bg-base-100 p-2 shadow" tabindex="0">
                <li v-for="(label, code) in page.props.availableLocales" :key="code">
                    <button
                        type="button"
                        :aria-current="code === page.props.locale ? 'true' : undefined"
                        :class="{ 'menu-active': code === page.props.locale }"
                        @click="switchLocale(code); $event.currentTarget.blur()"
                    >
                        {{ label }}
                    </button>
                </li>
            </ul>
        </div>

        <select
            class="select select-bordered select-sm w-32"
            :value="theme"
            :aria-label="t('Tema')"
            @change="setTheme($event.target.value)"
        >
            <option v-for="themeName in themes" :key="themeName" :value="themeName">{{ themeName }}</option>
        </select>

        <UserMenu
            v-if="showUserMenu && user"
            :user="user"
            :safe-avatar="safeAvatar"
            :initial="initial"
        />
    </div>
</template>
