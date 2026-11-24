<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Check, Copy, RefreshCw, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import Modal from '@/components/ui/Modal.vue';
import RecoveryCodesList from '@/components/settings/RecoveryCodesList.vue';
import { routes } from '@/lib/routes';
import { useToast } from '@/lib/useToast';
import { appUrl } from '@/lib/url';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorPending: { type: Boolean, default: false },
    qrCodeSvg: { type: String, default: null },
    secretKey: { type: String, default: null },
    recoveryCodes: { type: Array, default: () => [] },
});

const { error: toastError } = useToast();
const { t } = useI18n();

const enableOpen = ref(false);
const disableOpen = ref(false);
const regenerateOpen = ref(false);
const recoveryVisible = ref(false);
const copied = ref(null);

// Fortify meminta konfirmasi password sebelum 2FA diaktifkan/dinonaktifkan,
// begitu juga saat kode pemulihan dibuat ulang.
const enableForm = useForm({ password: '' });
const disableForm = useForm({ password: '' });
const regenerateForm = useForm({ password: '' });
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

const openRegenerate = () => {
    regenerateForm.reset();
    regenerateForm.clearErrors();
    regenerateOpen.value = true;
};

const submitRegenerate = () => {
    regenerateForm.post(appUrl(routes.settingsRecoveryCodes), {
        preserveScroll: true,
        onSuccess: () => {
            regenerateOpen.value = false;
            regenerateForm.reset();
            recoveryVisible.value = true;
        },
    });
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
        toastError(t('Gagal menyalin ke clipboard.'));
    }
};

const statusLabel = () => {
    if (props.twoFactorEnabled) {
        return t('Aktif');
    }

    return props.twoFactorPending ? t('Menunggu konfirmasi') : t('Nonaktif');
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
                        <h2 class="card-title text-base">
                            {{ t('Autentikasi Dua Faktor (2FA)') }}
                        </h2>
                        <p class="text-sm text-base-content/60">
                            {{
                                t(
                                    'Minta kode verifikasi tambahan dari aplikasi autentikator (Google Authenticator, Authy, dan sejenisnya) setiap kali masuk.',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <span class="badge" :class="statusClass()">{{ statusLabel() }}</span>
            </div>

            <div v-if="!twoFactorEnabled && !twoFactorPending">
                <button type="button" class="btn btn-primary btn-sm" @click="openEnable">
                    <ShieldCheck class="size-4" />
                    {{ t('Aktifkan 2FA') }}
                </button>
            </div>

            <!-- Secret sudah dibuat, menunggu kode verifikasi pertama. -->
            <div v-if="twoFactorPending" class="grid gap-4 lg:grid-cols-[auto_1fr]">
                <div class="rounded-box bg-white p-3">
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div class="size-48 [&>svg]:size-full" v-html="qrCodeSvg"></div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm">
                        {{
                            t(
                                'Pindai QR code di samping, lalu masukkan 6 digit kode yang muncul di aplikasi autentikator.',
                            )
                        }}
                    </p>

                    <div>
                        <p class="mb-1 text-sm font-medium">{{ t('Kunci manual') }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="block break-all rounded-box bg-base-200 p-2 text-xs">{{
                                secretKey
                            }}</code>
                            <button
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :aria-label="
                                    copied === 'secret'
                                        ? t('Kunci manual tersalin')
                                        : t('Salin kunci manual')
                                "
                                @click="copy(secretKey, 'secret')"
                            >
                                <Check v-if="copied === 'secret'" class="size-4 text-success" />
                                <Copy v-else class="size-4" />
                                {{ copied === 'secret' ? t('Tersalin') : t('Salin') }}
                            </button>
                        </div>
                    </div>

                    <form
                        id="confirm-2fa-form"
                        class="flex flex-wrap items-end gap-2"
                        @submit.prevent="submitConfirm"
                    >
                        <div class="form-control">
                            <label for="confirm-code" class="mb-1 text-sm">{{
                                t('Kode verifikasi')
                            }}</label>
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
                        <button
                            type="submit"
                            class="btn btn-primary btn-sm"
                            :disabled="confirmForm.processing"
                        >
                            <span
                                v-if="confirmForm.processing"
                                class="loading loading-spinner loading-sm"
                            ></span>
                            {{ t('Konfirmasi') }}
                        </button>
                    </form>

                    <p v-if="confirmForm.errors.code" class="text-sm text-error">
                        {{ confirmForm.errors.code }}
                    </p>
                </div>
            </div>

            <!-- 2FA aktif: kode pemulihan. -->
            <div v-if="twoFactorEnabled" class="space-y-3 border-t border-base-300 pt-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-medium">{{ t('Kode Pemulihan') }}</h3>
                        <p class="text-sm text-base-content/60">
                            {{
                                t(
                                    'Simpan di tempat aman. Setiap kode hanya bisa dipakai sekali saat aplikasi autentikator tidak tersedia.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            :disabled="regenerateForm.processing"
                            @click="openRegenerate"
                        >
                            <span
                                v-if="regenerateForm.processing"
                                class="loading loading-spinner loading-sm"
                            ></span>
                            <RefreshCw v-else class="size-4" />
                            {{ t('Buat Ulang') }}
                        </button>
                    </div>
                </div>

                <RecoveryCodesList
                    :codes="recoveryCodes"
                    :visible="recoveryVisible"
                    :copied="copied"
                    @toggle="recoveryVisible = !recoveryVisible"
                    @copy="(code) => copy(code, code)"
                />

                <div class="border-t border-base-300 pt-4">
                    <button type="button" class="btn btn-error btn-sm" @click="openDisable">
                        <ShieldOff class="size-4" />
                        {{ t('Nonaktifkan 2FA') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Konfirmasi password sebelum mengaktifkan -->
    <Modal v-model="enableOpen" :title="t('Aktifkan Autentikasi Dua Faktor')">
        <form id="enable-2fa-form" class="space-y-3" @submit.prevent="submitEnable">
            <p class="text-sm">{{ t('Masukkan password akun Anda untuk melanjutkan.') }}</p>
            <div class="form-control">
                <label for="enable-password" class="mb-1 text-sm">{{ t('Password') }}</label>
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
            <button type="button" class="btn btn-ghost" @click="enableOpen = false">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="enable-2fa-form"
                class="btn btn-primary"
                :disabled="enableForm.processing"
            >
                <span
                    v-if="enableForm.processing"
                    class="loading loading-spinner loading-sm"
                ></span>
                {{ t('Aktifkan') }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi password sebelum membuat ulang kode pemulihan -->
    <Modal v-model="regenerateOpen" :title="t('Buat Ulang Kode Pemulihan')">
        <form id="regenerate-recovery-form" class="space-y-3" @submit.prevent="submitRegenerate">
            <p class="text-sm">
                {{
                    t(
                        'Kode pemulihan lama akan langsung tidak berlaku. Masukkan password akun Anda untuk melanjutkan.',
                    )
                }}
            </p>
            <div class="form-control">
                <label for="regenerate-password" class="mb-1 text-sm">{{ t('Password') }}</label>
                <input
                    id="regenerate-password"
                    v-model="regenerateForm.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': regenerateForm.errors.password }"
                    autocomplete="current-password"
                    required
                />
                <span v-if="regenerateForm.errors.password" class="mt-1 text-sm text-error">
                    {{ regenerateForm.errors.password }}
                </span>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="regenerateOpen = false">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="regenerate-recovery-form"
                class="btn btn-primary"
                :disabled="regenerateForm.processing"
            >
                <span
                    v-if="regenerateForm.processing"
                    class="loading loading-spinner loading-sm"
                ></span>
                {{ t('Buat Ulang') }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi password sebelum menonaktifkan -->
    <Modal v-model="disableOpen" :title="t('Nonaktifkan Autentikasi Dua Faktor')">
        <form id="disable-2fa-form" class="space-y-3" @submit.prevent="submitDisable">
            <p class="text-sm">
                {{ t('Autentikasi dua faktor akan dimatikan dan kode pemulihan dihapus.') }}
            </p>
            <div class="form-control">
                <label for="disable-password" class="mb-1 text-sm">{{ t('Password') }}</label>
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
            <button type="button" class="btn btn-ghost" @click="disableOpen = false">
                {{ t('Batal') }}
            </button>
            <button
                type="submit"
                form="disable-2fa-form"
                class="btn btn-error"
                :disabled="disableForm.processing"
            >
                <span
                    v-if="disableForm.processing"
                    class="loading loading-spinner loading-sm"
                ></span>
                {{ t('Nonaktifkan') }}
            </button>
        </template>
    </Modal>
</template>
