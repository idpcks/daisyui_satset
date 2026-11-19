<script setup>
import { History } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import { formatDevice } from '@/lib/userAgent';
import { useI18n } from '@/lib/i18n';

defineProps({
    entries: { type: Array, default: () => [] },
});

const { t, locale } = useI18n();

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

const statusLabel = (successful) => (successful ? t('Berhasil') : t('Gagal'));
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <History class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">{{ t('Riwayat Login') }}</h2>
                    <p class="text-sm text-base-content/60">
                        {{ t('Dua puluh aktivitas login terbaru untuk akun Anda, termasuk percobaan yang gagal.') }}
                    </p>
                </div>
            </div>

            <div v-if="entries.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ t('Waktu') }}</th>
                            <th class="hidden sm:table-cell">{{ t('Status') }}</th>
                            <th class="hidden sm:table-cell">IP</th>
                            <th>{{ t('Perangkat') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries" :key="entry.id">
                            <td class="whitespace-nowrap">
                                {{ formatDate(entry.created_at) }}
                                <div class="mt-1 flex flex-wrap items-center gap-1 sm:hidden">
                                    <span
                                        class="badge badge-xs whitespace-nowrap"
                                        :class="entry.successful ? 'badge-success' : 'badge-error'"
                                    >
                                        {{ statusLabel(entry.successful) }}
                                    </span>
                                    <span class="font-mono text-xs text-base-content/60">
                                        {{ entry.ip_address ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell">
                                <span
                                    class="badge badge-sm whitespace-nowrap"
                                    :class="entry.successful ? 'badge-success' : 'badge-error'"
                                >
                                    {{ statusLabel(entry.successful) }}
                                </span>
                            </td>
                            <td class="hidden font-mono text-xs whitespace-nowrap sm:table-cell">
                                {{ entry.ip_address ?? '—' }}
                            </td>
                            <td>
                                <span
                                    class="block max-w-40 truncate sm:max-w-md"
                                    :title="entry.user_agent || undefined"
                                >
                                    {{ formatDevice(entry.user_agent) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="History"
                :title="t('Belum ada riwayat login.')"
                :description="t('Aktivitas login berhasil maupun gagal akan muncul di sini.')"
                :bordered="false"
            />

            <p class="text-xs text-base-content/60">
                {{ t('Percobaan login gagal yang memakai email akun Anda juga dicatat, tanpa menyimpan password.') }}
            </p>
        </div>
    </div>
</template>
