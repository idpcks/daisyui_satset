<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Lock, LockOpen } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

defineOptions({ layout: GuestLayout });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(appUrl(routes.login), {
        onFinish: () => {
            form.reset('password');
            showPassword.value = false;
        },
    });
};
</script>

<template>
    <Head title="Masuk" />

    <div class="card bg-base-100 shadow">
        <form class="card-body gap-3" @submit.prevent="submit">
            <h2 class="card-title">Masuk</h2>

            <label class="form-control">
                <span class="mb-1 text-sm">Email</span>
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
                <label for="password" class="mb-1 text-sm">Password</label>
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
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
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
                <span class="text-sm">Ingat saya</span>
            </label>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                Masuk
            </button>
        </form>
    </div>
</template>
