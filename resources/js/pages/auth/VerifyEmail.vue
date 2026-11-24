<script setup>
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useI18n } from '@/lib/i18n';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

defineOptions({ layout: GuestLayout });

const { t } = useI18n();
const form = useForm({});

const resend = () => {
    form.post(appUrl(routes.emailVerificationSend));
};
</script>

<template>
    <Head :title="t('Verifikasi Email')" />

    <div class="mx-auto max-w-md space-y-4 text-center">
        <h1 class="text-xl font-semibold">{{ t('Verifikasi Email Anda') }}</h1>
        <p class="text-sm text-base-content/70">
            {{
                t(
                    'Kami telah mengirim tautan verifikasi ke email Anda. Klik tautan tersebut untuk mengaktifkan akun.',
                )
            }}
        </p>

        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="resend">
            <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
            {{ t('Kirim ulang tautan verifikasi') }}
        </button>
    </div>
</template>
