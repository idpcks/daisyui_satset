<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Power, TriangleAlert, Wrench } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/components/ui/Modal.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useToast } from '@/lib/useToast';

defineOptions({ layout: AppLayout, inheritAttrs: false });

const props = defineProps({
    active: { type: Boolean, default: false },
    retry: { type: Number, default: null },
});

const { error: toastError } = useToast();

const processing = ref(false);
const enableOpen = ref(false);
const disableOpen = ref(false);
const retrySeconds = ref('');

const retryHint = computed(() => {
    if (!props.retry) {
        return null;
    }

    return `Browser diminta mencoba lagi setelah ${props.retry} detik.`;
});

const enable = () => {
    if (processing.value) {
        return;
    }

    processing.value = true;

    router.post(
        appUrl(routes.maintenance),
        { retry: retrySeconds.value === '' ? null : Number(retrySeconds.value) },
        {
            preserveScroll: true,
            onSuccess: () => {
                enableOpen.value = false;
                retrySeconds.value = '';
            },
            onError: () => toastError('Maintenance mode gagal diaktifkan. Silakan coba lagi.'),
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const disable = () => {
    if (processing.value) {
        return;
    }

    processing.value = true;

    router.delete(appUrl(routes.maintenance), {
        preserveScroll: true,
        onSuccess: () => {
            disableOpen.value = false;
        },
        onError: () => toastError('Maintenance mode gagal dinonaktifkan. Silakan coba lagi.'),
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Maintenance" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">Maintenance</h1>
        <p class="text-sm text-base-content/60">
            Tutup aplikasi sementara untuk pemeliharaan. Pengguna lain akan melihat halaman 503 sampai mode ini
            dinonaktifkan.
        </p>
    </div>

    <div role="alert" class="alert alert-warning mb-4 text-sm">
        <TriangleAlert class="size-5 shrink-0" />
        <span>
            Maintenance mode berbeda dari feature flag: pengecekannya berjalan sebelum aplikasi boot, sehingga seluruh
            rute ikut tertutup. Queue dan scheduler tetap berjalan; hentikan keduanya secara terpisah bila perlu.
        </span>
    </div>

    <div class="rounded-box bg-base-100 shadow">
        <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="grid size-11 shrink-0 place-items-center rounded-full bg-base-200">
                    <Wrench class="size-5" />
                </div>
                <div>
                    <p class="font-medium">Status aplikasi</p>
                    <p class="mt-1 text-sm text-base-content/60">
                        <span class="badge badge-sm gap-1" :class="active ? 'badge-error' : 'badge-success'">
                            {{ active ? 'Maintenance aktif' : 'Aplikasi normal' }}
                        </span>
                    </p>
                    <p v-if="active && retryHint" class="mt-2 text-xs text-base-content/60">{{ retryHint }}</p>
                </div>
            </div>

            <button
                type="button"
                class="btn"
                :class="active ? 'btn-success' : 'btn-error'"
                :disabled="processing"
                @click="active ? (disableOpen = true) : (enableOpen = true)"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                <Power v-else class="size-4" />
                {{ active ? 'Nonaktifkan Maintenance' : 'Aktifkan Maintenance' }}
            </button>
        </div>

        <div v-if="active" class="border-t border-base-300 p-6 text-sm">
            <p class="font-medium">Tetap bisa masuk</p>
            <p class="mt-1 text-base-content/60">
                Cookie bypass berlaku 12 jam sejak maintenance diaktifkan. Bila cookie hilang atau kedaluwarsa sebelum
                mode ini dimatikan, pulihkan dari server:
            </p>
            <code class="mt-2 inline-block rounded-lg bg-base-200 px-3 py-2 text-xs">php artisan up</code>
        </div>
    </div>

    <Modal v-model="enableOpen" title="Aktifkan Maintenance">
        <p>
            Yakin ingin mengaktifkan maintenance mode? Seluruh pengguna selain Anda akan melihat halaman 503.
        </p>

        <label class="mt-4 block text-sm font-medium" for="retry">Retry setelah (detik, opsional)</label>
        <input
            id="retry"
            v-model="retrySeconds"
            type="number"
            min="1"
            max="86400"
            class="input input-bordered mt-1 w-full"
            placeholder="Misalnya 3600"
        />
        <p class="mt-1 text-xs text-base-content/60">
            Nilai ini dikirim sebagai header <code>Retry-After</code> agar browser tahu kapan sebaiknya mencoba lagi.
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="enableOpen = false">Batal</button>
            <button
                type="button"
                class="btn btn-error"
                data-testid="confirm-enable"
                :disabled="processing"
                @click="enable"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                <Power v-else class="size-4" />
                {{ processing ? 'Mengaktifkan...' : 'Aktifkan' }}
            </button>
        </template>
    </Modal>

    <Modal v-model="disableOpen" title="Nonaktifkan Maintenance">
        <p>Yakin ingin menonaktifkan maintenance mode? Aplikasi akan langsung dapat diakses semua pengguna.</p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="disableOpen = false">Batal</button>
            <button
                type="button"
                class="btn btn-success"
                data-testid="confirm-disable"
                :disabled="processing"
                @click="disable"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                <Power v-else class="size-4" />
                {{ processing ? 'Menonaktifkan...' : 'Nonaktifkan' }}
            </button>
        </template>
    </Modal>
</template>
