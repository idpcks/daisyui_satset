<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Check, Copy, RefreshCw, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import Modal from '@/components/ui/Modal.vue';
import { routes } from '@/lib/routes';
import { useToast } from '@/lib/useToast';
import { appUrl } from '@/lib/url';

const props = defineProps({
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorPending: { type: Boolean, default: false },
    qrCodeSvg: { type: String, default: null },
    secretKey: { type: String, default: null },
    recoveryCodes: { type: Array, default: () => [] },
});

const { error: toastError } = useToast();

const enableOpen = ref(false);
const disableOpen = ref(false);
const recoveryVisible = ref(false);
const regenerating = ref(false);
const copied = ref(null);

// Fortify meminta konfirmasi password sebelum 2FA diaktifkan/dinonaktifkan.
const enableForm = useForm({ password: '' });
const disableForm = useForm({ password: '' });
const confirmForm = useForm({ code: '' });

const openEnable = () => {
    enableForm.reset();
    enableForm.clearErrors();
    enableOpen.value = true;
};

const openDisable = () => {
    disableForm.reset();
    disableForm.clearErrors();
    disableOpen.value = true;
};

const submitEnable = () => {
    enableForm.post(appUrl(routes.settingsTwoFactor), {
        preserveScroll: true,
        onSuccess: () => {
            enableOpen.value = false;
            enableForm.reset();
        },
    });
};

const submitDisable = () => {
    disableForm.delete(appUrl(routes.settingsTwoFactor), {
        preserveScroll: true,
        onSuccess: () => {
            disableOpen.value = false;
            disableForm.reset();
            recoveryVisible.value = false;
        },
    });
};

const submitConfirm = () => {
    confirmForm.post(appUrl(routes.settingsTwoFactorConfirm), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset(),
    });
};

const regenerate = () => {
    if (regenerating.value) {
        return;
    }

    regenerating.value = true;

    router.post(
        appUrl(routes.settingsRecoveryCodes),
        {},
        {
            preserveScroll: true,
            onSuccess: () => (recoveryVisible.value = true),
            onFinish: () => (regenerating.value = false),
        },
    );
};

const copy = async (value, key) => {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;

        setTimeout(() => {
            if (copied.value === key) {
                copied.value = null;
            }
        }, 2000);
    } catch {
        toastError('Gagal menyalin ke clipboard.');
    }
};

const statusLabel = () => {
    if (props.twoFactorEnabled) {
        return 'Aktif';
    }

    return props.twoFactorPending ? 'Menunggu konfirmasi' : 'Nonaktif';
};

const statusClass = () => {
    if (props.twoFactorEnabled) {
        return 'badge-success';
    }

    return props.twoFactorPending ? 'badge-warning' : 'badge-ghost';
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                        <ShieldCheck class="size-5" />
                    </div>
                    <div>
                        <h2 class="card-title text-base">Autentikasi Dua Faktor (2FA)</h2>
                        <p class="text-sm text-base-content/60">
                            Minta kode verifikasi tambahan dari aplikasi autentikator (Google Authenticator, Authy,
                            dan sejenisnya) setiap kali masuk.
                        </p>
                    </div>
                </div>

                <span class="badge" :class="statusClass()">{{ statusLabel() }}</span>
            </div>

            <div v-if="!twoFactorEnabled && !twoFactorPending">
                <button type="button" class="btn btn-primary btn-sm" @click="openEnable">
                    <ShieldCheck class="size-4" />
                    Aktifkan 2FA
                </button>
            </div>

            <!-- Secret sudah dibuat, menunggu kode verifikasi pertama. -->
            <div v-if="twoFactorPending" class="grid gap-4 lg:grid-cols-[auto_1fr]">
                <div class="rounded-box bg-white p-3">
                    <div class="size-48 [&>svg]:size-full" v-html="qrCodeSvg"></div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm">
                        Pindai QR code di samping, lalu masukkan 6 digit kode yang muncul di aplikasi autentikator.
                    </p>

                    <div>
                        <p class="mb-1 text-sm font-medium">Kunci manual</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="block break-all rounded-box bg-base-200 p-2 text-xs">{{ secretKey }}</code>
                            <button
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :aria-label="copied === 'secret' ? 'Kunci manual tersalin' : 'Salin kunci manual'"
                                @click="copy(secretKey, 'secret')"
                            >
                                <Check v-if="copied === 'secret'" class="size-4 text-success" />
                                <Copy v-else class="size-4" />
                                {{ copied === 'secret' ? 'Tersalin' : 'Salin' }}
                            </button>
                        </div>
                    </div>

                    <form id="confirm-2fa-form" class="flex flex-wrap items-end gap-2" @submit.prevent="submitConfirm">
                        <div class="form-control">
                            <label for="confirm-code" class="mb-1 text-sm">Kode verifikasi</label>
                            <input
                                id="confirm-code"
                                v-model="confirmForm.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                class="input input-bordered input-sm w-40 tracking-[0.3em]"
                                :class="{ 'input-error': confirmForm.errors.code }"
                                required
                            />
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="confirmForm.processing">
                            <span v-if="confirmForm.processing" class="loading loading-spinner loading-sm"></span>
                            Konfirmasi
                        </button>
                    </form>

                    <p v-if="confirmForm.errors.code" class="text-sm text-error">{{ confirmForm.errors.code }}</p>
                </div>
            </div>

            <!-- 2FA aktif: kode pemulihan. -->
            <div v-if="twoFactorEnabled" class="space-y-3 border-t border-base-300 pt-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-medium">Kode Pemulihan</h3>
                        <p class="text-sm text-base-content/60">
                            Simpan di tempat aman. Setiap kode hanya bisa dipakai sekali saat aplikasi autentikator
                            tidak tersedia.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm"
                            @click="recoveryVisible = !recoveryVisible"
                        >
                            {{ recoveryVisible ? 'Sembunyikan' : 'Tampilkan' }}
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" :disabled="regenerating" @click="regenerate">
                            <span v-if="regenerating" class="loading loading-spinner loading-sm"></span>
                            <RefreshCw v-else class="size-4" />
                            Buat Ulang
                        </button>
                    </div>
                </div>

                <div v-if="recoveryVisible" class="grid gap-2 rounded-box bg-base-200 p-3 sm:grid-cols-2">
                    <div
                        v-for="code in recoveryCodes"
                        :key="code"
                        class="flex items-center justify-between gap-2"
                    >
                        <code class="block text-sm">{{ code }}</code>
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs"
                            :aria-label="`Salin kode pemulihan ${code}`"
                            @click="copy(code, code)"
                        >
                            <Check v-if="copied === code" class="size-4 text-success" />
                            <Copy v-else class="size-4" />
                        </button>
                    </div>
                </div>
                <p v-else class="text-sm text-base-content/60">Kode pemulihan disembunyikan.</p>

                <div class="border-t border-base-300 pt-4">
                    <button type="button" class="btn btn-error btn-sm" @click="openDisable">
                        <ShieldOff class="size-4" />
                        Nonaktifkan 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Konfirmasi password sebelum mengaktifkan -->
    <Modal v-model="enableOpen" title="Aktifkan Autentikasi Dua Faktor">
        <form id="enable-2fa-form" class="space-y-3" @submit.prevent="submitEnable">
            <p class="text-sm">Masukkan password akun Anda untuk melanjutkan.</p>
            <div class="form-control">
                <label for="enable-password" class="mb-1 text-sm">Password</label>
                <input
                    id="enable-password"
                    v-model="enableForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': enableForm.errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="enableForm.errors.password" class="mt-1 text-sm text-error">
                    {{ enableForm.errors.password }}
                </span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="enableOpen = false">Batal</button>
            <button type="submit" form="enable-2fa-form" class="btn btn-primary" :disabled="enableForm.processing">
                <span v-if="enableForm.processing" class="loading loading-spinner loading-sm"></span>
                Aktifkan
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi password sebelum menonaktifkan -->
    <Modal v-model="disableOpen" title="Nonaktifkan Autentikasi Dua Faktor">
        <form id="disable-2fa-form" class="space-y-3" @submit.prevent="submitDisable">
            <p class="text-sm">Autentikasi dua faktor akan dimatikan dan kode pemulihan dihapus.</p>
            <div class="form-control">
                <label for="disable-password" class="mb-1 text-sm">Password</label>
                <input
                    id="disable-password"
                    v-model="disableForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': disableForm.errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="disableForm.errors.password" class="mt-1 text-sm text-error">
                    {{ disableForm.errors.password }}
                </span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="disableOpen = false">Batal</button>
            <button type="submit" form="disable-2fa-form" class="btn btn-error" :disabled="disableForm.processing">
                <span v-if="disableForm.processing" class="loading loading-spinner loading-sm"></span>
                Nonaktifkan
            </button>
        </template>
    </Modal>
</template>
