<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LogOut, MonitorSmartphone } from 'lucide-vue-next';
import Modal from '@/components/ui/Modal.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const open = ref(false);

const form = useForm({ password: '' });

const openModal = () => {
    form.reset();
    form.clearErrors();
    open.value = true;
};

const submit = () => {
    form.delete(appUrl(routes.settingsOtherSessions), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <MonitorSmartphone class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">Sesi & Perangkat</h2>
                    <p class="text-sm text-base-content/60">
                        Keluar dari semua browser dan perangkat lain yang masih login. Sesi yang sedang Anda pakai
                        tetap aktif.
                    </p>
                </div>
            </div>

            <div>
                <button type="button" class="btn btn-outline btn-sm" @click="openModal">
                    <LogOut class="size-4" />
                    Keluar dari Semua Sesi Lain
                </button>
            </div>
        </div>
    </div>

    <Modal v-model="open" title="Keluar dari Semua Sesi Lain">
        <form id="other-sessions-form" class="space-y-3" @submit.prevent="submit">
            <p class="text-sm">
                Masukkan password akun Anda untuk mengonfirmasi. Semua sesi lain akan dikeluarkan.
            </p>
            <div class="form-control">
                <label for="other-sessions-password" class="mb-1 text-sm">Password</label>
                <input
                    id="other-sessions-password"
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
            <button type="button" class="btn btn-ghost" @click="open = false">Batal</button>
            <button type="submit" form="other-sessions-form" class="btn btn-error" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                Keluar dari Sesi Lain
            </button>
        </template>
    </Modal>
</template>
