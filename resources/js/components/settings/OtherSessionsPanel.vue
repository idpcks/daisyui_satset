<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LogOut, MonitorSmartphone } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import Modal from '@/components/ui/Modal.vue';
import { routes, userSessionPath } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { formatDevice } from '@/lib/userAgent';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

const open = ref(false);
const selectedSession = ref(null);

const form = useForm({ password: '' });
const { t, locale } = useI18n();

const hasOtherSessions = computed(() => props.sessions.some((session) => !session.is_current));

const modalTitle = computed(() =>
    selectedSession.value ? t('Keluarkan Sesi') : t('Keluar dari Semua Sesi Lain'),
);

const endpoint = computed(() =>
    appUrl(
        selectedSession.value
            ? userSessionPath(selectedSession.value.id)
            : routes.settingsOtherSessions,
    ),
);

const openModal = (session = null) => {
    selectedSession.value = session;
    form.reset();
    form.clearErrors();
    open.value = true;
};

const closeModal = () => {
    open.value = false;
    selectedSession.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.delete(endpoint.value, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

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
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                        <MonitorSmartphone class="size-5" />
                    </div>
                    <div>
                        <h2 class="card-title text-base">{{ t('Sesi & Perangkat') }}</h2>
                        <p class="text-sm text-base-content/60">
                            {{
                                t(
                                    'Perangkat yang masih login ke akun Anda. Sesi yang sedang Anda pakai tidak dapat dikeluarkan dari daftar ini.',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="!hasOtherSessions"
                    @click="openModal()"
                >
                    <LogOut class="size-4" />
                    {{ t('Keluar dari Semua Sesi Lain') }}
                </button>
            </div>

            <div v-if="sessions.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ t('Perangkat') }}</th>
                            <th>IP</th>
                            <th>{{ t('Terakhir Aktif') }}</th>
                            <th
                                class="sticky right-0 z-20 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                            >
                                {{ t('Aksi') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in sessions" :key="session.id">
                            <td>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="max-w-md truncate"
                                        :title="session.user_agent || undefined"
                                    >
                                        {{ formatDevice(session.user_agent) }}
                                    </span>
                                    <span
                                        v-if="session.is_current"
                                        class="badge badge-success badge-sm whitespace-nowrap"
                                    >
                                        {{ t('Sesi ini') }}
                                    </span>
                                </div>
                            </td>
                            <td class="font-mono text-xs whitespace-nowrap">
                                {{ session.ip_address ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap">
                                {{ formatDate(session.last_activity) }}
                            </td>
                            <td
                                class="sticky right-0 z-10 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                            >
                                <button
                                    v-if="!session.is_current"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    @click="openModal(session)"
                                >
                                    <LogOut class="size-3.5" />
                                    {{ t('Keluarkan') }}
                                </button>
                                <span v-else class="text-xs text-base-content/50">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="MonitorSmartphone"
                :title="t('Belum ada sesi aktif yang tercatat.')"
                :description="t('Sesi akan muncul setelah Anda menggunakan akun ini.')"
                :bordered="false"
            />
        </div>
    </div>

    <Modal v-model="open" :title="modalTitle">
        <form id="sessions-form" class="space-y-3" @submit.prevent="submit">
            <p class="text-sm">
                {{ t('Masukkan password akun Anda untuk mengonfirmasi.') }}
                <template v-if="selectedSession">
                    {{ t('Sesi perangkat ini akan dikeluarkan.') }}
                </template>
                <template v-else>
                    {{ t('Semua sesi lain akan dikeluarkan.') }}
                </template>
            </p>

            <p v-if="selectedSession" class="text-sm text-base-content/60">
                {{ formatDevice(selectedSession.user_agent) }}
                <span v-if="selectedSession.ip_address"> · {{ selectedSession.ip_address }}</span>
            </p>

            <div class="form-control">
                <label for="sessions-password" class="mb-1 text-sm">{{ t('Password') }}</label>
                <input
                    id="sessions-password"
                    v-model="form.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="form.errors.password" class="mt-1 text-sm text-error">
                    {{ form.errors.password }}
                </span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="closeModal">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="sessions-form"
                class="btn btn-error"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                {{ selectedSession ? t('Keluarkan Sesi') : t('Keluar dari Sesi Lain') }}
            </button>
        </template>
    </Modal>
</template>
