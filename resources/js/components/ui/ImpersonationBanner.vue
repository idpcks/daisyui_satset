<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ShieldAlert } from 'lucide-vue-next';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useAuth } from '@/lib/useAuth';
import { useToast } from '@/lib/useToast';

const { user, impersonation } = useAuth();
const { error: toastError } = useToast();

const stopping = ref(false);

const impersonatorName = computed(() => impersonation.value?.impersonator?.name ?? 'superadmin');

const stopImpersonation = () => {
    if (stopping.value) {
        return;
    }

    stopping.value = true;

    router.delete(appUrl(routes.impersonation), {
        // Inertia mengirim body {} secara default; DELETE berbody menggantung di PHP dev server.
        data: undefined,
        onError: () => toastError('Gagal kembali ke akun asal. Silakan coba lagi.'),
        onFinish: () => (stopping.value = false),
    });
};
</script>

<template>
    <div
        v-if="impersonation"
        class="alert alert-warning flex flex-wrap items-center gap-3 rounded-none border-x-0 border-t-0"
        role="status"
    >
        <ShieldAlert class="size-5 shrink-0" aria-hidden="true" />

        <div class="min-w-0 flex-1">
            <p class="font-medium">Mode impersonation aktif</p>
            <p class="text-sm">
                Anda masuk sebagai <strong>{{ user?.name }}</strong> ({{ user?.username }}), bukan
                {{ impersonatorName }}. Mulai dan berhentinya sesi ini tercatat di Audit Log.
            </p>
        </div>

        <button type="button" class="btn btn-neutral btn-sm" :disabled="stopping" @click="stopImpersonation">
            <span v-if="stopping" class="loading loading-spinner loading-sm"></span>
            {{ stopping ? 'Kembali...' : 'Kembali ke akun saya' }}
        </button>
    </div>
</template>
