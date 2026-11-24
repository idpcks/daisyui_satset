<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Fingerprint, Lock, LockOpen } from 'lucide-vue-next';
import { RequestError, requestJson } from '@/lib/http';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import {
    getPasskeyCredential,
    isPasskeySupported,
    serializeAssertionCredential,
    toRequestOptions,
} from '@/lib/webauthn';
import { useI18n } from '@/lib/i18n';

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
const magicLinkEnabled = computed(() => page.props.features?.magic_link === true);

const showPassword = ref(false);
const magicLinkMode = ref(false);
const magicLinkEmail = ref('');
const magicLinkProcessing = ref(false);
const magicLinkMessage = ref('');
const magicLinkError = ref('');
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

    conditionalAutofillController?.abort();
    conditionalAutofillController = null;
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
        void initializeConditionalAutofill();
    }
};

let conditionalAutofillController = null;

const initializeConditionalAutofill = async () => {
    if (!passkeysEnabled.value || !passkeysSupported) {
        return;
    }

    const PublicKeyCredential = window.PublicKeyCredential;

    if (typeof PublicKeyCredential?.getClientCapabilities !== 'function') {
        return;
    }

    const capabilities = await PublicKeyCredential.getClientCapabilities();

    if (capabilities?.conditionalGet !== true) {
        return;
    }

    conditionalAutofillController?.abort();
    conditionalAutofillController = new AbortController();

    try {
        const options = await requestJson(routes.passkeyLoginOptions);
        const credential = await navigator.credentials.get({
            publicKey: toRequestOptions(options),
            mediation: 'conditional',
            signal: conditionalAutofillController.signal,
        });

        const result = await requestJson(routes.passkeyLogin, {
            method: 'POST',
            body: {
                credential: serializeAssertionCredential(credential),
                remember: form.remember,
            },
        });

        router.visit(result?.redirect ?? appUrl(routes.dashboard));
    } catch (error) {
        // NotAllowedError/AbortError adalah perilaku normal (user membatalkan
        // atau explicit flow menggantikan conditional flow).
        if (!['NotAllowedError', 'AbortError'].includes(error?.name)) {
            console.error(error);
        }
    }
};

const openMagicLinkMode = () => {
    magicLinkMode.value = true;
    magicLinkEmail.value = form.email;
    magicLinkMessage.value = '';
    magicLinkError.value = '';
};

const closeMagicLinkMode = () => {
    magicLinkMode.value = false;
    magicLinkMessage.value = '';
    magicLinkError.value = '';
};

const requestMagicLink = async () => {
    if (magicLinkProcessing.value) {
        return;
    }

    magicLinkError.value = '';
    magicLinkMessage.value = '';
    magicLinkProcessing.value = true;

    try {
        await requestJson(routes.magicLinkRequest, {
            method: 'POST',
            body: { email: magicLinkEmail.value },
        });

        magicLinkMessage.value = t('Permintaan Anda telah diproses.');
    } catch (error) {
        magicLinkError.value = error instanceof RequestError
            ? (error.fieldError('email') ?? error.message)
            : t('Gagal mengirim tautan masuk. Coba lagi.');
    } finally {
        magicLinkProcessing.value = false;
    }
};

onMounted(() => {
    void initializeConditionalAutofill();
});

onUnmounted(() => {
    conditionalAutofillController?.abort();
});
</script>

<template>
    <div class="card bg-base-100 shadow">
        <form class="card-body gap-4" aria-labelledby="login-title" @submit.prevent="submit">
            <h2 id="login-title" class="card-title">{{ t('Masuk') }}</h2>

            <template v-if="!magicLinkMode">
                <div class="form-control">
                    <label for="email" class="mb-1 text-sm">{{ t('Email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        class="input input-bordered w-full min-h-12"
                        :class="{ 'input-error': form.errors.email }"
                        autocomplete="username webauthn"
                        autocorrect="off"
                        autocapitalize="none"
                        spellcheck="false"
                        enterkeyhint="next"
                        :aria-describedby="form.errors.email ? 'email-hint email-error' : 'email-hint'"
                        :aria-invalid="Boolean(form.errors.email)"
                        autofocus
                        required
                    />
                    <span id="email-hint" class="sr-only">{{ t('Masukkan email akun Anda.') }}</span>
                    <span
                        v-if="form.errors.email"
                        id="email-error"
                        role="alert"
                        class="mt-1 text-sm text-error"
                    >
                        {{ form.errors.email }}
                    </span>
                </div>

                <div class="form-control">
                    <label for="password" class="mb-1 text-sm">{{ t('Password') }}</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="input input-bordered w-full min-h-12 pr-11"
                            :class="{ 'input-error': form.errors.password }"
                            autocomplete="current-password"
                            enterkeyhint="done"
                            :aria-describedby="form.errors.password ? 'password-error' : undefined"
                            :aria-invalid="Boolean(form.errors.password)"
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
                    <span
                        v-if="form.errors.password"
                        id="password-error"
                        role="alert"
                        class="mt-1 text-sm text-error"
                    >
                        {{ form.errors.password }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="label cursor-pointer justify-start gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            name="remember"
                            type="checkbox"
                            class="checkbox checkbox-sm"
                        />
                        <span class="text-sm">{{ t('Ingat saya') }}</span>
                    </label>

                    <button
                        v-if="magicLinkEnabled"
                        type="button"
                        class="link link-hover inline-flex min-h-6 items-center text-sm"
                        @click="openMagicLinkMode"
                    >
                        {{ t('Masuk dengan tautan email') }}
                    </button>
                </div>

                <button type="submit" class="btn btn-primary min-h-12" :disabled="form.processing">
                    <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                    {{ t('Masuk') }}
                </button>

                <template v-if="passkeysEnabled && passkeysSupported">
                    <div class="divider my-1 text-xs text-base-content/50">{{ t('atau') }}</div>

                    <button
                        type="button"
                        class="btn btn-outline min-h-12"
                        :disabled="passkeyProcessing || form.processing"
                        @click="loginWithPasskey"
                    >
                        <span v-if="passkeyProcessing" class="loading loading-spinner loading-sm"></span>
                        <Fingerprint v-else class="size-4" />
                        {{ t('Masuk dengan passkey') }}
                    </button>

                    <p v-if="passkeyError" role="alert" aria-live="polite" class="text-center text-sm text-error">
                        {{ passkeyError }}
                    </p>
                </template>
            </template>

            <template v-else>
                <div class="space-y-4">
                    <div class="form-control">
                        <label for="magic-email" class="mb-1 text-sm">{{ t('Email') }}</label>
                        <input
                            id="magic-email"
                            v-model="magicLinkEmail"
                            name="email"
                            type="email"
                            class="input input-bordered w-full min-h-12"
                            autocomplete="username"
                            autocorrect="off"
                            autocapitalize="none"
                            spellcheck="false"
                            enterkeyhint="done"
                            required
                        />
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary w-full min-h-12"
                        :disabled="magicLinkProcessing"
                        @click="requestMagicLink"
                    >
                        <span v-if="magicLinkProcessing" class="loading loading-spinner loading-sm"></span>
                        {{ t('Kirim tautan masuk') }}
                    </button>

                    <p
                        v-if="magicLinkMessage"
                        role="status"
                        aria-live="polite"
                        class="text-center text-sm text-success"
                    >
                        {{ magicLinkMessage }}
                    </p>
                    <p
                        v-if="magicLinkError"
                        role="alert"
                        aria-live="polite"
                        class="text-center text-sm text-error"
                    >
                        {{ magicLinkError }}
                    </p>

                    <button type="button" class="btn btn-ghost btn-sm w-full" @click="closeMagicLinkMode">
                        {{ t('Kembali ke login') }}
                    </button>
                </div>
            </template>
        </form>
    </div>
</template>
