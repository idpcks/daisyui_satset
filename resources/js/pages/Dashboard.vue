<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: AppLayout });

const { t } = useI18n();
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="grid gap-4 md:grid-cols-3">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">{{ t('Kartu statistik') }}</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">
                    {{ t('Ganti dengan data ringkasan aplikasi Anda.') }}
                </p>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">{{ t('Kartu statistik') }}</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">
                    {{ t('Ganti dengan data ringkasan aplikasi Anda.') }}
                </p>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">{{ t('Kartu statistik') }}</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">
                    {{ t('Ganti dengan data ringkasan aplikasi Anda.') }}
                </p>
            </div>
        </div>
    </div>
</template>
