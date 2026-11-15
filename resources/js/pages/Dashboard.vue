<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { LayoutDashboard } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useAuth } from '@/lib/useAuth';

defineOptions({ layout: AppLayout });

const page = usePage();
const { user, roles } = useAuth();

const appName = computed(() => page.props.appName ?? 'Aplikasi');
const roleLabel = computed(() => roles.value.join(', ') || '—');
</script>

<template>
    <Head title="Dashboard" />

    <div class="card mb-4 bg-base-100 shadow">
        <div class="card-body">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <LayoutDashboard class="size-5" />
                </div>
                <div>
                    <h2 class="card-title">Selamat datang, {{ user?.name ?? 'Pengguna' }}</h2>
                    <p class="text-sm text-base-content/60">
                        Anda masuk ke {{ appName }} dengan peran {{ roleLabel }}. Gunakan menu di sisi kiri untuk
                        mengelola aplikasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">Kartu statistik</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">Ganti dengan data ringkasan aplikasi Anda.</p>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">Kartu statistik</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">Ganti dengan data ringkasan aplikasi Anda.</p>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-sm text-base-content/60">Kartu statistik</p>
                <p class="text-2xl font-semibold">—</p>
                <p class="text-xs text-base-content/50">Ganti dengan data ringkasan aplikasi Anda.</p>
            </div>
        </div>
    </div>
</template>
