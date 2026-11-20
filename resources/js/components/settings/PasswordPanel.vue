<script setup>
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();

const props = defineProps({
    mustChangePassword: { type: Boolean, default: false },
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(appUrl(routes.settingsPassword), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <KeyRound class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">{{ t('Ubah Password') }}</h2>
                    <p class="text-sm text-base-content/60">
                        {{
                            props.mustChangePassword
                                ? t('Tentukan password baru Anda. Password saat ini tidak diperlukan.')
                                : t('Masukkan password saat ini untuk mengonfirmasi, lalu tentukan password baru.')
                        }}
                    </p>
                </div>
            </div>

            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                <label v-if="!props.mustChangePassword" class="flex flex-col gap-1 md:col-span-2">
                    <span class="text-sm">
                        {{ t('Password saat ini') }} <span class="text-error" aria-hidden="true">*</span>
                    </span>
                    <input
                        v-model="form.current_password"
                        type="password"
                        autocomplete="current-password"
                        aria-required="true"
                        class="input input-bordered w-full"
                        :class="{ 'input-error': form.errors.current_password }"
                    />
                    <span v-if="form.errors.current_password" class="text-sm text-error">
                        {{ form.errors.current_password }}
                    </span>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm">
                        {{ t('Password baru') }} <span class="text-error" aria-hidden="true">*</span>
                    </span>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        aria-required="true"
                        class="input input-bordered w-full"
                        :class="{ 'input-error': form.errors.password }"
                    />
                    <span v-if="form.errors.password" class="text-sm text-error">{{ form.errors.password }}</span>
                    <span v-else class="text-xs text-base-content/60">{{ t('Minimal 8 karakter.') }}</span>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm">
                        {{ t('Konfirmasi password baru') }} <span class="text-error" aria-hidden="true">*</span>
                    </span>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        aria-required="true"
                        class="input input-bordered w-full"
                    />
                </label>

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        {{ t('Ubah Password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
