<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: GuestLayout });

// Fortify menerima `code` (TOTP) atau `recovery_code`; hanya satu yang diisi.
const useRecoveryCode = ref(false);
const { t } = useI18n();

const form = useForm({
    code: '',
    recovery_code: '',
});

const toggleMode = () => {
    useRecoveryCode.value = !useRecoveryCode.value;
    form.reset('code', 'recovery_code');
    form.clearErrors();
};

const submit = () => {
    form.post(appUrl(routes.twoFactorChallenge), {
        onFinish: () => form.reset('code', 'recovery_code'),
    });
};
</script>

<template>
    <Head :title="t('Verifikasi Dua Faktor')" />

    <div class="card bg-base-100 shadow">
        <form class="card-body gap-3" @submit.prevent="submit">
            <h2 class="card-title">{{ t('Verifikasi Dua Faktor') }}</h2>

            <p class="text-sm text-base-content/70">
                <template v-if="useRecoveryCode">
                    {{
                        t(
                            'Masukkan salah satu kode pemulihan yang Anda simpan saat mengaktifkan autentikasi dua faktor.',
                        )
                    }}
                </template>
                <template v-else>
                    {{
                        t(
                            'Masukkan 6 digit kode dari aplikasi autentikator Anda untuk melanjutkan.',
                        )
                    }}
                </template>
            </p>

            <div v-if="!useRecoveryCode" class="form-control">
                <label for="two-factor-code" class="mb-1 text-sm">{{ t('Kode verifikasi') }}</label>
                <input
                    id="two-factor-code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    class="input input-bordered w-full tracking-[0.3em]"
                    :class="{ 'input-error': form.errors.code }"
                    autofocus
                    required
                />
                <span v-if="form.errors.code" class="mt-1 text-sm text-error">{{
                    form.errors.code
                }}</span>
            </div>

            <div v-else class="form-control">
                <label for="two-factor-recovery" class="mb-1 text-sm">{{
                    t('Kode Pemulihan')
                }}</label>
                <input
                    id="two-factor-recovery"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.recovery_code }"
                    autofocus
                    required
                />
                <span v-if="form.errors.recovery_code" class="mt-1 text-sm text-error">
                    {{ form.errors.recovery_code }}
                </span>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                {{ t('Verifikasi') }}
            </button>

            <button type="button" class="btn btn-ghost btn-sm" @click="toggleMode">
                <KeyRound class="size-4" />
                {{ useRecoveryCode ? t('Gunakan kode autentikator') : t('Gunakan kode pemulihan') }}
            </button>
        </form>
    </div>
</template>
