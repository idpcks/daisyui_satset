<script setup>
import { useForm } from '@inertiajs/vue3';
import { UserRound } from 'lucide-vue-next';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    account: { type: Object, required: true },
});

const form = useForm({
    name: props.account.name ?? '',
    username: props.account.username ?? '',
    email: props.account.email ?? '',
});

const submit = () => {
    form.put(appUrl(routes.settingsAccount), {
        preserveScroll: true,
        onSuccess: () => form.defaults(form.data()),
    });
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <UserRound class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">Data Akun</h2>
                    <p class="text-sm text-base-content/60">
                        Nama dipakai di sidebar, sedangkan email dipakai untuk masuk ke aplikasi.
                    </p>
                </div>
            </div>

            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                <label class="flex flex-col gap-1">
                    <span class="text-sm">
                        Nama lengkap <span class="text-error" aria-hidden="true">*</span>
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
                        Username <span class="text-error" aria-hidden="true">*</span>
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
                        Email <span class="text-error" aria-hidden="true">*</span>
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

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
