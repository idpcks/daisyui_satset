<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Flag, Power } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/components/ui/Modal.vue';
import { featurePath } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useToast } from '@/lib/useToast';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: AppLayout, inheritAttrs: false });

defineProps({
    flags: { type: Array, default: () => [] },
});

const { error: toastError } = useToast();
const { t } = useI18n();

const processing = ref(null);
const disabling = ref(null);

const disableOpen = computed({
    get: () => disabling.value !== null,
    set: (open) => {
        if (!open) {
            disabling.value = null;
        }
    },
});

const send = (flag, active) => {
    processing.value = flag.name;

    router.patch(
        appUrl(featurePath(flag.name)),
        { active },
        {
            preserveScroll: true,
            onSuccess: () => {
                disabling.value = null;
            },
            onError: () =>
                toastError(
                    t('Fitur :label gagal diperbarui. Silakan coba lagi.', { label: flag.label }),
                ),
            onFinish: () => {
                processing.value = null;
            },
        },
    );
};

const requestToggle = (flag) => {
    if (flag.active) {
        disabling.value = flag;
        return;
    }

    send(flag, true);
};

const confirmDisable = () => {
    if (!disabling.value || processing.value) {
        return;
    }

    send(disabling.value, false);
};
</script>

<template>
    <Head :title="t('Feature Flags')" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ t('Feature Flags') }}</h1>
        <p class="text-sm text-base-content/60">
            {{
                t(
                    'Aktifkan atau nonaktifkan fitur tanpa deploy ulang. Perubahan berlaku segera setelah disimpan.',
                )
            }}
        </p>
    </div>

    <div role="alert" class="alert alert-info mb-4 text-sm">
        <Flag class="size-5 shrink-0" />
        <span>
            {{
                t(
                    'Mematikan fitur akan menutup rute dan antarmukanya, bukan mengubah izin pengguna. Pengaturan akses tetap diatur lewat RBAC.',
                )
            }}
        </span>
    </div>

    <div class="overflow-x-auto rounded-box bg-base-100 shadow">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ t('Fitur') }}</th>
                    <th>{{ t('Status') }}</th>
                    <th
                        class="sticky right-0 z-20 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                    >
                        {{ t('Aksi') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="flag in flags" :key="flag.name">
                    <td>
                        <p class="font-medium">{{ flag.label }}</p>
                        <p class="mt-1 max-w-md text-xs text-base-content/60">
                            {{ flag.description }}
                        </p>
                        <code class="mt-1 inline-block text-xs text-base-content/40">{{
                            flag.name
                        }}</code>
                    </td>
                    <td>
                        <span
                            class="badge badge-sm gap-1"
                            :class="flag.active ? 'badge-success' : 'badge-ghost'"
                        >
                            {{ flag.active ? t('Aktif') : t('Nonaktif') }}
                        </span>
                    </td>
                    <td
                        class="sticky right-0 z-10 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                    >
                        <button
                            type="button"
                            class="btn btn-sm"
                            :class="flag.active ? 'btn-error btn-outline' : 'btn-primary'"
                            :aria-label="`${flag.active ? t('Nonaktifkan') : t('Aktifkan')} ${flag.label}`"
                            :title="`${flag.active ? t('Nonaktifkan') : t('Aktifkan')} ${flag.label}`"
                            :disabled="processing === flag.name"
                            @click="requestToggle(flag)"
                        >
                            <span
                                v-if="processing === flag.name"
                                class="loading loading-spinner loading-sm"
                            ></span>
                            <Power v-else class="size-4" />
                            <span class="hidden sm:inline">{{
                                flag.active ? t('Nonaktifkan') : t('Aktifkan')
                            }}</span>
                        </button>
                    </td>
                </tr>
                <tr v-if="flags.length === 0">
                    <td colspan="3" class="text-sm text-base-content/60">
                        {{ t('Belum ada feature flag yang terdaftar.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal v-model="disableOpen" :title="t('Nonaktifkan Fitur')">
        <p v-if="disabling">
            {{ t('Yakin ingin menonaktifkan') }} <strong>{{ disabling.label }}</strong>?
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            {{
                t(
                    'Rute dan antarmuka fitur ini langsung tidak dapat diakses sampai diaktifkan kembali.',
                )
            }}
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="disableOpen = false">
                {{ t('Batal') }}
            </button>
            <button
                type="button"
                class="btn btn-error"
                data-testid="confirm-disable"
                :disabled="processing !== null"
                @click="confirmDisable"
            >
                <span v-if="processing !== null" class="loading loading-spinner loading-sm"></span>
                <Power v-else class="size-4" />
                {{ processing !== null ? t('Menonaktifkan...') : t('Nonaktifkan') }}
            </button>
        </template>
    </Modal>
</template>
