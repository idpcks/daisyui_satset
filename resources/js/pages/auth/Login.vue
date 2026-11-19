<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Fingerprint, Lock, LockOpen } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { RequestError, requestJson } from '@/lib/http';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { getPasskeyCredential, isPasskeySupported } from '@/lib/webauthn';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: GuestLayout });

const page = usePage();
const { t } = useI18n();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Tombol hanya tampil bila fitur Fortify aktif dan browser mendukung WebAuthn.
const passkeysEnabled = computed(() => page.props.features?.passkeys === true);
const passkeysSupported = isPasskeySupported();

const showPassword = ref(false);
const passkeyProcessing = ref(false);
const passkeyError = ref('');

const submit = () => {
    form.post(appUrl(routes.login), {
        onFinish: () => {
            form.reset('password');
            showPassword.value = false;
        },
    });
};

/**
 * @param {unknown} error
 * @returns {string}
 */
const passkeyMessage = (error) => {
    if (error instanceof RequestError) {
        return error.fieldError('credential') ?? error.message;
    }

    if (error?.name === 'NotAllowedError') {
        return t('Masuk dengan passkey dibatalkan.');
    }

    return error?.message ?? t('Gagal masuk dengan passkey. Coba lagi.');
};

const loginWithPasskey = async () => {
    if (passkeyProcessing.value) {
        return;
    }

    passkeyError.value = '';
    passkeyProcessing.value = true;

    try {
        const { options } = await requestJson(routes.passkeyLoginOptions);
        const credential = await getPasskeyCredential(options);
        const result = await requestJson(routes.passkeyLogin, {
            method: 'POST',
            body: { credential, remember: form.remember },
        });

        router.visit(result?.redirect ?? appUrl(routes.dashboard));
    } catch (error) {
        passkeyError.value = passkeyMessage(error);
    } finally {
        passkeyProcessing.value = false;
    }
};
</script>

<template>
    <Head :title="t('Masuk')" />

    <div class="card bg-base-100 shadow">
        <form class="card-body gap-3" @submit.prevent="submit">
            <h2 class="card-title">{{ t('Masuk') }}</h2>

            <label class="form-control">
                <span class="mb-1 text-sm">{{ t('Email') }}</span>
                <input
                    v-model="form.email"
                    type="email"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.email }"
                    autocomplete="username"
                    required
                />
                <span v-if="form.errors.email" class="mt-1 text-sm text-error">{{ form.errors.email }}</span>
            </label>

            <div class="form-control">
                <label for="password" class="mb-1 text-sm">{{ t('Password') }}</label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="input input-bordered w-full pr-11"
                        :class="{ 'input-error': form.errors.password }"
                        autocomplete="current-password"
                        required
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-base-content/60 hover:text-base-content"
                        :aria-label="showPassword ? t('Sembunyikan password') : t('Tampilkan password')"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <LockOpen v-if="showPassword" class="size-5" />
                        <Lock v-else class="size-5" />
                    </button>
                </div>
                <span v-if="form.errors.password" class="mt-1 text-sm text-error">{{ form.errors.password }}</span>
            </div>

            <label class="label cursor-pointer justify-start gap-2">
                <input v-model="form.remember" type="checkbox" class="checkbox checkbox-sm" />
                <span class="text-sm">{{ t('Ingat saya') }}</span>
            </label>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                {{ t('Masuk') }}
            </button>

            <template v-if="passkeysEnabled && passkeysSupported">
                <div class="divider my-1 text-xs text-base-content/50">{{ t('atau') }}</div>

                <button
                    type="button"
                    class="btn btn-outline"
                    :disabled="passkeyProcessing || form.processing"
                    @click="loginWithPasskey"
                >
                    <span v-if="passkeyProcessing" class="loading loading-spinner loading-sm"></span>
                    <Fingerprint v-else class="size-4" />
                    {{ t('Masuk dengan passkey') }}
                </button>

                <p v-if="passkeyError" role="alert" class="text-center text-sm text-error">{{ passkeyError }}</p>
            </template>
        </form>
    </div>
</template>
