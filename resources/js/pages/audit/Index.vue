<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';
import { History } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

defineOptions({ layout: AppLayout, inheritAttrs: false });

defineProps({
    logs: { type: Object, required: true },
});

const { t, locale } = useI18n();

const eventLabel = (event) =>
    ({
        impersonation_started: t('Mulai impersonation'),
        impersonation_stopped: t('Selesai impersonation'),
    })[event] ?? event;

const eventClass = (event) =>
    event === 'impersonation_started' ? 'badge-warning' : 'badge-success';

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) {
        return '—';
    }

    const total = Math.max(0, Number(seconds));

    if (total < 60) {
        return `${total} detik`;
    }

    const minutes = Math.floor(total / 60);
    const remainingSeconds = total % 60;

    if (minutes < 60) {
        return remainingSeconds > 0 ? `${minutes} mnt ${remainingSeconds} dtk` : `${minutes} menit`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return remainingMinutes > 0 ? `${hours} jam ${remainingMinutes} mnt` : `${hours} jam`;
};
</script>

<template>
    <Head :title="t('Audit Log')" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ t('Audit Log') }}</h1>
        <p class="text-sm text-base-content/60">
            {{
                t(
                    'Riwayat kapan superadmin masuk sebagai pengguna lain dan kapan kembali ke akun asalnya.',
                )
            }}
        </p>
    </div>

    <div class="overflow-x-auto rounded-box bg-base-100 shadow">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ t('Waktu') }}</th>
                    <th>{{ t('Aksi') }}</th>
                    <th>{{ t('Pelaku') }}</th>
                    <th>{{ t('Target') }}</th>
                    <th>IP</th>
                    <th>{{ t('Durasi') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs.data" :key="log.id">
                    <td class="whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                    <td>
                        <span
                            class="badge badge-sm whitespace-nowrap"
                            :class="eventClass(log.event)"
                        >
                            {{ eventLabel(log.event) }}
                        </span>
                        <p class="mt-1 max-w-xs text-xs text-base-content/60">
                            {{ log.description }}
                        </p>
                    </td>
                    <td>
                        <p class="font-medium">{{ log.impersonator_name ?? '—' }}</p>
                        <p class="text-xs text-base-content/60">
                            {{ log.impersonator_username ?? '—' }}
                        </p>
                    </td>
                    <td>
                        <p class="font-medium">{{ log.impersonated_name ?? '—' }}</p>
                        <p class="text-xs text-base-content/60">
                            {{ log.impersonated_username ?? '—' }}
                        </p>
                    </td>
                    <td class="whitespace-nowrap font-mono text-xs">{{ log.ip_address ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ formatDuration(log.duration_seconds) }}</td>
                </tr>
                <tr v-if="logs.data.length === 0">
                    <td colspan="6" class="p-0">
                        <EmptyState
                            :icon="History"
                            :title="t('Belum ada aktivitas impersonation.')"
                            :description="
                                t(
                                    'Setiap superadmin yang masuk sebagai pengguna lain akan tercatat di sini.',
                                )
                            "
                            :bordered="false"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="logs.last_page > 1" class="mt-4 overflow-x-auto pb-1">
        <div class="join">
            <template v-for="link in logs.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="btn join-item btn-sm"
                    :class="{ 'btn-active': link.active }"
                    preserve-scroll
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label"></span>
                </Link>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <span v-else class="btn join-item btn-sm btn-disabled" v-html="link.label"></span>
            </template>
        </div>
    </div>
</template>
