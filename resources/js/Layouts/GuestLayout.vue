<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { appUrl } from '@/lib/url';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Aplikasi');
const locales = computed(() => page.props.availableLocales || {});

const switchLocale = (locale) => {
    router.post(appUrl('locale'), { locale }, { preserveScroll: true });
};
</script>

<template>
    <div class="grid min-h-screen place-items-center bg-base-200 p-4">
        <div class="w-full max-w-sm">
            <div v-if="Object.keys(locales).length" class="mb-4 flex justify-center">
                <select
                    class="select select-bordered select-sm w-32"
                    :value="page.props.locale"
                    @change="switchLocale($event.target.value)"
                    aria-label="Bahasa"
                >
                    <option v-for="(label, code) in locales" :key="code" :value="code">
                        {{ label }}
                    </option>
                </select>
            </div>
            <h1 class="mb-6 text-center text-2xl font-bold">{{ appName }}</h1>
            <slot />
        </div>
    </div>
</template>