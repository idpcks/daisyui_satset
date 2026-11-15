<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Lock, Plus } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PermissionMatrix from '@/components/rbac/PermissionMatrix.vue';
import { roleLabel } from '@/lib/labels';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

defineOptions({ layout: AppLayout, inheritAttrs: false });

defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const form = useForm({ name: '' });

const submit = () => {
    form.post(appUrl(routes.rbac), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="RBAC" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">RBAC</h1>
        <p class="text-sm text-base-content/60">
            Kelola peran dan atur permission-nya. Perubahan berlaku untuk semua pengguna dengan peran tersebut.
        </p>
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <div class="card bg-base-100 shadow">
            <div class="card-body gap-3">
                <h2 class="card-title text-base">Peran</h2>

                <ul class="space-y-1">
                    <li v-for="role in roles" :key="role.id" class="rounded-box px-2 py-1.5 hover:bg-base-200">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium">{{ roleLabel(role.name) }}</span>
                            <span v-if="role.is_superadmin" class="badge badge-ghost badge-sm gap-1">
                                <Lock class="size-3" />
                                Tetap
                            </span>
                        </div>
                        <p class="text-xs text-base-content/60">
                            {{ role.users_count }} pengguna · {{ role.permissions.length }} permission
                        </p>
                    </li>
                </ul>

                <p v-if="roles.length === 0" class="text-sm text-base-content/60">Belum ada peran.</p>

                <div class="divider my-1"></div>

                <form class="space-y-2" @submit.prevent="submit">
                    <label for="role-name" class="text-sm font-medium">Tambah peran</label>
                    <input
                        id="role-name"
                        v-model="form.name"
                        type="text"
                        placeholder="mis. auditor"
                        class="input input-bordered input-sm w-full"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <p v-if="form.errors.name" class="text-xs text-error">{{ form.errors.name }}</p>
                    <p v-else class="text-xs text-base-content/60">
                        Huruf kecil, angka, titik, garis bawah, atau strip. Contoh: auditor.
                    </p>
                    <button
                        type="submit"
                        class="btn btn-primary btn-sm w-full"
                        :disabled="form.processing || form.name.trim() === ''"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        <Plus v-else class="size-4" />
                        Tambah Peran
                    </button>
                </form>
            </div>
        </div>

        <PermissionMatrix :roles="roles" :permissions="permissions" />
    </div>
</template>
