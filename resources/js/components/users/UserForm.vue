<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { roleLabel } from '@/lib/labels';
import { routes, userPath } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, default: () => [] },
});

const { t } = useI18n();

const emit = defineEmits(['success', 'cancel']);

const form = useForm({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
    email: props.user?.email ?? '',
    role: props.user?.role ?? props.roles[0] ?? '',
    password: '',
    password_confirmation: '',
    is_active: props.user?.is_active ?? true,
});

// Password hanya wajib saat membuat akun baru, atau saat diisi pada mode ubah.
const passwordRequired = computed(() => !props.user || form.password !== '');

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('success') };

    if (props.user) {
        form.put(appUrl(userPath(props.user.id)), options);
    } else {
        form.post(appUrl(routes.users), options);
    }
};
</script>

<template>
    <form @submit.prevent="submit">
        <p class="mb-3 text-xs text-base-content/60">
            <span class="text-error" aria-hidden="true">*</span> {{ t('Wajib diisi') }}
        </p>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex flex-col gap-1">
                <span class="text-sm">
                    {{ t('Nama lengkap') }} <span class="text-error" aria-hidden="true">*</span>
                </span>
                <input
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    aria-required="true"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.name }"
                />
                <span v-if="form.errors.name" class="text-sm text-error">{{ form.errors.name }}</span>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm">
                    {{ t('Username') }} <span class="text-error" aria-hidden="true">*</span>
                </span>
                <input
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    aria-required="true"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.username }"
                />
                <span v-if="form.errors.username" class="text-sm text-error">{{ form.errors.username }}</span>
            </label>

            <label class="flex flex-col gap-1 md:col-span-2">
                <span class="text-sm">
                    {{ t('Email') }} <span class="text-error" aria-hidden="true">*</span>
                </span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    aria-required="true"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.email }"
                />
                <span v-if="form.errors.email" class="text-sm text-error">{{ form.errors.email }}</span>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm">
                    {{ t('Peran') }} <span class="text-error" aria-hidden="true">*</span>
                </span>
                <select
                    v-model="form.role"
                    aria-required="true"
                    class="select select-bordered w-full"
                    :class="{ 'select-error': form.errors.role }"
                >
                    <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role, t) }}</option>
                </select>
                <span v-if="form.errors.role" class="text-sm text-error">{{ form.errors.role }}</span>
            </label>

            <label class="label cursor-pointer justify-start gap-2 self-end pb-3">
                <input v-model="form.is_active" type="checkbox" class="toggle toggle-primary" />
                <span class="text-sm">{{ t('Akun aktif') }}</span>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm">
                    {{ t('Password') }} <span v-if="!user" class="text-error" aria-hidden="true">*</span>
                </span>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :aria-required="!user"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': form.errors.password }"
                />
                <span v-if="form.errors.password" class="text-sm text-error">{{ form.errors.password }}</span>
                <span v-else-if="user" class="text-xs text-base-content/60">{{ t('Kosongkan jika tidak ingin mengubah password.') }}</span>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm">
                    {{ t('Konfirmasi password') }}
                    <span v-if="passwordRequired" class="text-error" aria-hidden="true">*</span>
                </span>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :aria-required="passwordRequired"
                    class="input input-bordered w-full"
                />
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" @click="emit('cancel')">{{ t('Batal') }}</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                {{ t('Simpan') }}
            </button>
        </div>
    </form>
</template>
