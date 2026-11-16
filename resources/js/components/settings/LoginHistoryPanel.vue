<script setup>
import { History } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import { formatDevice } from '@/lib/userAgent';

defineProps({
    entries: { type: Array, default: () => [] },
});

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const statusLabel = (successful) => (successful ? 'Berhasil' : 'Gagal');
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <History class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">Riwayat Login</h2>
                    <p class="text-sm text-base-content/60">
                        Dua puluh aktivitas login terbaru untuk akun Anda, termasuk percobaan yang gagal.
                    </p>
                </div>
            </div>

            <div v-if="entries.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Perangkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries" :key="entry.id">
                            <td class="whitespace-nowrap">{{ formatDate(entry.created_at) }}</td>
                            <td>
                                <span
                                    class="badge badge-sm whitespace-nowrap"
                                    :class="entry.successful ? 'badge-success' : 'badge-error'"
                                >
                                    {{ statusLabel(entry.successful) }}
                                </span>
                            </td>
                            <td class="font-mono text-xs whitespace-nowrap">{{ entry.ip_address ?? '—' }}</td>
                            <td>
                                <span
                                    class="block max-w-md truncate"
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
                title="Belum ada riwayat login."
                description="Aktivitas login berhasil maupun gagal akan muncul di sini."
                :bordered="false"
            />

            <p class="text-xs text-base-content/60">
                Percobaan login gagal yang memakai email akun Anda juga dicatat, tanpa menyimpan password.
            </p>
        </div>
    </div>
</template>
