<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: GuestLayout });

// Fortify menerima password dan menulis auth.password_confirmed_at di sesi.
// Endpoint ini sudah dibatasi limiter password-confirm di sisi server.
const form = useForm({ password: '' });
const showPassword = ref(false);
const { t } = useI18n();

const submit = () => {
    form.post(appUrl(routes.confirmPassword), {
        onFinish: () => {
            form.reset('password');
            showPassword.value = false;
        },
    });
};
</script>

<template>
    <Head :title="t('Konfirmasi Password')" />

    <div class="card bg-base-100 shadow">
        <form class="card-body gap-3" @submit.prevent="submit">
            <h2 class="card-title">{{ t('Konfirmasi Password') }}</h2>

            <p class="text-sm text-base-content/70">
                {{ t('Untuk melanjutkan, masukkan password akun Anda. Konfirmasi ini berlaku untuk beberapa aksi sensitif dan akan kedaluwarsa setelah beberapa waktu.') }}
            </p>

            <div class="form-control">
                <label for="confirm-password" class="mb-1 text-sm">{{ t('Password') }}</label>
                <div class="relative">
                    <input
                        id="confirm-password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="input input-bordered w-full pr-11"
                        :class="{ 'input-error': form.errors.password }"
                        autocomplete="current-password"
                        autofocus
                        required
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-base-content/60 hover:text-base-content"
                        :aria-label="showPassword ? t('Sembunyikan password') : t('Tampilkan password')"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <span class="text-xs"> {{ showPassword ? t('Sembunyikan') : t('Lihat') }} </span>
                    </button>
                </div>
                <span v-if="form.errors.password" class="mt-1 text-sm text-error">
                    {{ form.errors.password }}
                </span>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                <KeyRound v-else class="size-4" />
                {{ t('Konfirmasi') }}
            </button>
        </form>
    </div>
</template>
