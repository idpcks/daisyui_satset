<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Aplikasi');
</script>

<template>
    <div class="grid min-h-screen place-items-center bg-base-200 p-4">
        <div class="w-full max-w-sm">
            <h1 class="mb-6 text-center text-2xl font-bold">{{ appName }}</h1>
            <slot />
        </div>
    </div>
</template>
