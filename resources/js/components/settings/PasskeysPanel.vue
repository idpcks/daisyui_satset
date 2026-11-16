<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Fingerprint, KeyRound, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import Modal from '@/components/ui/Modal.vue';
import { RequestError, requestJson } from '@/lib/http';
import { passkeyPath, routes } from '@/lib/routes';
import { useToast } from '@/lib/useToast';
import { createPasskeyCredential, isPasskeySupported } from '@/lib/webauthn';

defineProps({
    passkeys: { type: Array, default: () => [] },
});

const { success: toastSuccess, error: toastError } = useToast();

const supported = isPasskeySupported();

const registerOpen = ref(false);
const deleteTarget = ref(null);
const processing = ref(false);
const errors = ref({});

const registerForm = reactive({ name: '', password: '' });
const deleteForm = reactive({ password: '' });

const deleteOpen = computed({
    get: () => deleteTarget.value !== null,
    set: (value) => {
        if (!value) {
            deleteTarget.value = null;
        }
    },
});

const resetErrors = () => {
    errors.value = {};
};

const openRegister = () => {
    registerForm.name = '';
    registerForm.password = '';
    resetErrors();
    registerOpen.value = true;
};

const closeRegister = () => {
    registerOpen.value = false;
    registerForm.name = '';
    registerForm.password = '';
    resetErrors();
};

const openDelete = (passkey) => {
    deleteForm.password = '';
    resetErrors();
    deleteTarget.value = passkey;
};

const closeDelete = () => {
    deleteTarget.value = null;
    deleteForm.password = '';
    resetErrors();
};

/**
 * Terjemahkan error fetch/WebAuthn menjadi pesan yang bisa ditampilkan.
 *
 * @param {unknown} error
 * @returns {string}
 */
const messageFor = (error) => {
    if (error instanceof RequestError) {
        return error.message;
    }

    if (error?.name === 'NotAllowedError') {
        return 'Pendaftaran dibatalkan atau tidak didukung perangkat ini.';
    }

    if (error?.name === 'InvalidStateError') {
        return 'Passkey untuk perangkat ini sudah terdaftar.';
    }

    return error?.message ?? 'Terjadi kesalahan. Coba lagi.';
};

/**
 * @param {unknown} error
 */
const handleError = (error) => {
    if (error instanceof RequestError) {
        errors.value = error.errors;
    }

    toastError(messageFor(error));
};

// Rute manajemen passkey dilindungi middleware `password.confirm`, jadi
// password dikonfirmasi lebih dulu lewat endpoint Fortify sebelum memanggil
// endpoint passkey.
const confirmPassword = (password) =>
    requestJson(routes.confirmPassword, {
        method: 'POST',
        body: { password },
    });

const submitRegister = async () => {
    if (processing.value) {
        return;
    }

    resetErrors();
    processing.value = true;

    try {
        await confirmPassword(registerForm.password);

        const { options } = await requestJson(routes.passkeysOptions);
        const credential = await createPasskeyCredential(options);

        await requestJson(routes.passkeys, {
            method: 'POST',
            body: { name: registerForm.name, credential },
        });

        closeRegister();
        toastSuccess('Passkey berhasil ditambahkan.');
        router.reload({ only: ['passkeys'] });
    } catch (error) {
        handleError(error);
    } finally {
        processing.value = false;
    }
};

const submitDelete = async () => {
    if (processing.value || !deleteTarget.value) {
        return;
    }

    resetErrors();
    processing.value = true;

    try {
        await confirmPassword(deleteForm.password);
        await requestJson(passkeyPath(deleteTarget.value.id), { method: 'DELETE' });

        closeDelete();
        toastSuccess('Passkey berhasil dihapus.');
        router.reload({ only: ['passkeys'] });
    } catch (error) {
        handleError(error);
    } finally {
        processing.value = false;
    }
};

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
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                        <Fingerprint class="size-5" />
                    </div>
                    <div>
                        <h2 class="card-title text-base">Passkey</h2>
                        <p class="text-sm text-base-content/60">
                            Masuk tanpa mengetik password memakai sidik jari, wajah, atau PIN perangkat. Passkey
                            terikat pada perangkat dan domain aplikasi ini.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    :disabled="!supported"
                    @click="openRegister"
                >
                    <Plus class="size-4" />
                    Tambah Passkey
                </button>
            </div>

            <div v-if="!supported" role="alert" class="alert alert-warning alert-soft text-sm">
                Browser ini tidak mendukung passkey. Gunakan browser terbaru di perangkat yang memiliki
                autentikator (sidik jari, wajah, atau PIN).
            </div>

            <div v-if="passkeys.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th class="hidden sm:table-cell">Authenticator</th>
                            <th>Dibuat</th>
                            <th>Terakhir Dipakai</th>
                            <th class="sticky right-0 z-20 border-l border-base-300 bg-base-100 text-right md:border-l-0">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="passkey in passkeys" :key="passkey.id">
                            <td>
                                <div class="flex items-center gap-2">
                                    <KeyRound class="size-4 text-base-content/60" />
                                    <span class="font-medium">{{ passkey.name }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-base-content/60 sm:hidden">
                                    {{ passkey.authenticator ?? '—' }}
                                </p>
                            </td>
                            <td class="hidden sm:table-cell">{{ passkey.authenticator ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ formatDate(passkey.created_at) }}</td>
                            <td class="whitespace-nowrap">
                                {{ passkey.last_used_at ? formatDate(passkey.last_used_at) : 'Belum pernah' }}
                            </td>
                            <td class="sticky right-0 z-10 border-l border-base-300 bg-base-100 text-right md:border-l-0">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    @click="openDelete(passkey)"
                                >
                                    <Trash2 class="size-3.5" />
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="Fingerprint"
                title="Belum ada passkey terdaftar."
                description="Tambahkan passkey untuk bisa masuk tanpa password dari perangkat ini."
                :bordered="false"
            />
        </div>
    </div>

    <!-- Password dikonfirmasi dulu karena endpoint manajemen passkey memakai middleware password.confirm. -->
    <Modal v-model="registerOpen" title="Tambah Passkey">
        <form id="passkey-register-form" class="space-y-3" @submit.prevent="submitRegister">
            <p class="text-sm">
                Masukkan nama untuk mengenali perangkat ini, lalu konfirmasi password akun Anda. Browser akan
                meminta sidik jari, wajah, atau PIN perangkat.
            </p>

            <div class="form-control">
                <label for="passkey-name" class="mb-1 text-sm">Nama Passkey</label>
                <input
                    id="passkey-name"
                    v-model="registerForm.name"
                    type="text"
                    maxlength="255"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.name }"
                    placeholder="Contoh: Laptop Kantor"
                    autocomplete="off"
                    required
                />
                <span v-if="errors.name" class="mt-1 text-sm text-error">{{ errors.name[0] }}</span>
            </div>

            <div class="form-control">
                <label for="passkey-register-password" class="mb-1 text-sm">Password</label>
                <input
                    id="passkey-register-password"
                    v-model="registerForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="errors.password" class="mt-1 text-sm text-error">{{ errors.password[0] }}</span>
                <span v-if="errors.credential" class="mt-1 text-sm text-error">{{ errors.credential[0] }}</span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="closeRegister">Batal</button>
            <button type="submit" form="passkey-register-form" class="btn btn-primary" :disabled="processing">
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                Tambah Passkey
            </button>
        </template>
    </Modal>

    <Modal v-model="deleteOpen" title="Hapus Passkey">
        <form id="passkey-delete-form" class="space-y-3" @submit.prevent="submitDelete">
            <p class="text-sm">
                Passkey <span class="font-medium">{{ deleteTarget?.name }}</span> akan dihapus dan tidak bisa lagi
                dipakai untuk masuk. Masukkan password akun Anda untuk melanjutkan.
            </p>

            <div class="form-control">
                <label for="passkey-delete-password" class="mb-1 text-sm">Password</label>
                <input
                    id="passkey-delete-password"
                    v-model="deleteForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="errors.password" class="mt-1 text-sm text-error">{{ errors.password[0] }}</span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="closeDelete">Batal</button>
            <button type="submit" form="passkey-delete-form" class="btn btn-error" :disabled="processing">
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                Hapus Passkey
            </button>
        </template>
    </Modal>
</template>
