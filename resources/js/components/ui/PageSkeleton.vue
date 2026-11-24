<script setup>
import { useI18n } from '@/lib/i18n';

defineProps({
    rows: { type: Number, default: 6 },
});

const { t } = useI18n();
</script>

<template>
    <div class="animate-in fade-in" aria-busy="true" :aria-label="t('Memuat halaman')">
        <!-- Judul + tombol -->
        <div class="mb-4 flex items-center justify-between">
            <div class="skeleton h-7 w-48"></div>
            <div class="skeleton h-8 w-32"></div>
        </div>

        <!-- Filter -->
        <div class="mb-4 flex gap-2">
            <div class="skeleton h-8 w-48"></div>
            <div class="skeleton h-8 w-32"></div>
            <div class="skeleton h-8 w-32"></div>
        </div>

        <!-- Tabel -->
        <div class="rounded-box bg-base-100 p-4 shadow">
            <div class="skeleton mb-4 h-5 w-full"></div>
            <div v-for="i in rows" :key="i" class="mb-3 flex items-center gap-4">
                <div class="skeleton h-4 w-20"></div>
                <div class="skeleton h-4 w-28"></div>
                <div class="skeleton h-4 flex-1"></div>
                <div class="skeleton h-4 w-24"></div>
                <div class="skeleton h-6 w-16 rounded-full"></div>
            </div>
        </div>
    </div>
</template>
