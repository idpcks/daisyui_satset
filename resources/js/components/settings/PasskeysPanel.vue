<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Fingerprint, Plus } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import PasskeyTable from '@/components/settings/PasskeyTable.vue';
import Modal from '@/components/ui/Modal.vue';
import { RequestError, requestJson } from '@/lib/http';
import { passkeyPath, routes } from '@/lib/routes';
import { useToast } from '@/lib/useToast';
import { useI18n } from '@/lib/i18n';
import { createPasskeyCredential, isPasskeySupported } from '@/lib/webauthn';

defineProps({
    passkeys: { type: Array, default: () => [] },
});

const { success: toastSuccess, error: toastError } = useToast();
const { t, locale } = useI18n();

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
        return t('Pendaftaran dibatalkan atau tidak didukung perangkat ini.');
    }

    if (error?.name === 'InvalidStateError') {
        return t('Passkey untuk perangkat ini sudah terdaftar.');
    }

    return error?.message ?? t('Terjadi kesalahan. Coba lagi.');
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
        toastSuccess(t('Passkey berhasil ditambahkan.'));
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
        toastSuccess(t('Passkey berhasil dihapus.'));
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
                        <Fingerprint class="size-5" />
                    </div>
                    <div>
                        <h2 class="card-title text-base">{{ t('Passkey') }}</h2>
                        <p class="text-sm text-base-content/60">
                            {{
                                t(
                                    'Masuk tanpa mengetik password memakai sidik jari, wajah, atau PIN perangkat. Passkey terikat pada perangkat dan domain aplikasi ini.',
                                )
                            }}
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
                    {{ t('Tambah Passkey') }}
                </button>
            </div>

            <div v-if="!supported" role="alert" class="alert alert-warning alert-soft text-sm">
                {{
                    t(
                        'Browser ini tidak mendukung passkey. Gunakan browser terbaru di perangkat yang memiliki autentikator (sidik jari, wajah, atau PIN).',
                    )
                }}
            </div>

            <PasskeyTable
                v-if="passkeys.length > 0"
                :passkeys="passkeys"
                :format-date="formatDate"
                @delete="openDelete"
            />

            <EmptyState
                v-else
                :icon="Fingerprint"
                :title="t('Belum ada passkey terdaftar.')"
                :description="
                    t('Tambahkan passkey untuk bisa masuk tanpa password dari perangkat ini.')
                "
                :bordered="false"
            />
        </div>
    </div>

    <!-- Password dikonfirmasi dulu karena endpoint manajemen passkey memakai middleware password.confirm. -->
    <Modal v-model="registerOpen" :title="t('Tambah Passkey')">
        <form id="passkey-register-form" class="space-y-3" @submit.prevent="submitRegister">
            <p class="text-sm">
                {{
                    t(
                        'Masukkan nama untuk mengenali perangkat ini, lalu konfirmasi password akun Anda. Browser akan meminta sidik jari, wajah, atau PIN perangkat.',
                    )
                }}
            </p>

            <div class="form-control">
                <label for="passkey-name" class="mb-1 text-sm">{{ t('Nama Passkey') }}</label>
                <input
                    id="passkey-name"
                    v-model="registerForm.name"
                    type="text"
                    maxlength="255"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.name }"
                    :placeholder="t('Contoh: Laptop Kantor')"
                    autocomplete="off"
                    required
                />
                <span v-if="errors.name" class="mt-1 text-sm text-error">{{ errors.name[0] }}</span>
            </div>

            <div class="form-control">
                <label for="passkey-register-password" class="mb-1 text-sm">{{
                    t('Password')
                }}</label>
                <input
                    id="passkey-register-password"
                    v-model="registerForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="errors.password" class="mt-1 text-sm text-error">{{
                    errors.password[0]
                }}</span>
                <span v-if="errors.credential" class="mt-1 text-sm text-error">{{
                    errors.credential[0]
                }}</span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="closeRegister">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="passkey-register-form"
                class="btn btn-primary"
                :disabled="processing"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                {{ t('Tambah Passkey') }}
            </button>
        </template>
    </Modal>

    <Modal v-model="deleteOpen" :title="t('Hapus Passkey')">
        <form id="passkey-delete-form" class="space-y-3" @submit.prevent="submitDelete">
            <p class="text-sm">
                {{ t('Passkey') }} <span class="font-medium">{{ deleteTarget?.name }}</span>
                {{
                    t(
                        'akan dihapus dan tidak bisa lagi dipakai untuk masuk. Masukkan password akun Anda untuk melanjutkan.',
                    )
                }}
            </p>

            <div class="form-control">
                <label for="passkey-delete-password" class="mb-1 text-sm">{{
                    t('Password')
                }}</label>
                <input
                    id="passkey-delete-password"
                    v-model="deleteForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="errors.password" class="mt-1 text-sm text-error">{{
                    errors.password[0]
                }}</span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="closeDelete">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="passkey-delete-form"
                class="btn btn-error"
                :disabled="processing"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                {{ t('Hapus Passkey') }}
            </button>
        </template>
    </Modal>
</template>
