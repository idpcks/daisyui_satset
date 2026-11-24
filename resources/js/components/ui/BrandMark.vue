<script setup>
import { LayoutDashboard } from 'lucide-vue-next';

defineProps({
    name: { type: String, required: true },
    // Di rail ikon, label disembunyikan hanya pada desktop; drawer mobile
    // tetap menampilkan nama aplikasi.
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary text-primary-content"
        >
            <LayoutDashboard class="size-5" />
        </div>
        <span class="truncate text-lg font-bold" :class="{ 'lg:hidden': compact }">{{ name }}</span>
    </div>
</template>
