<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Lock, Save, ShieldCheck } from 'lucide-vue-next';
import { permissionLabel, roleLabel } from '@/lib/labels';
import { rolePath } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    role: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
});

const form = useForm({ permissions: [...props.role.permissions] });

// Superadmin selalu lolos lewat Gate::before, jadi daftarnya hanya ditampilkan.
const locked = computed(() => props.role.is_superadmin);

const submit = () => {
    if (locked.value) {
        return;
    }

    form.put(appUrl(rolePath(props.role.id)), { preserveScroll: true });
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                    <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                        <ShieldCheck class="size-5" />
                    </div>
                    <div>
                        <h2 class="card-title text-base">{{ roleLabel(role.name) }}</h2>
                        <p class="text-xs text-base-content/60">
                            {{ role.users_count }} pengguna · {{ role.permissions.length }} permission
                        </p>
                    </div>
                </div>

                <span v-if="locked" class="badge badge-ghost gap-1">
                    <Lock class="size-3" />
                    Tetap
                </span>
            </div>

            <form class="space-y-1" @submit.prevent="submit">
                <label
                    v-for="permission in permissions"
                    :key="permission"
                    class="label cursor-pointer justify-start gap-3 rounded-box px-2 py-1.5 hover:bg-base-200"
                >
                    <input
                        v-model="form.permissions"
                        type="checkbox"
                        :value="permission"
                        class="checkbox checkbox-sm checkbox-primary"
                        :disabled="locked"
                    />
                    <span class="flex min-w-0 flex-col">
                        <span class="text-sm">{{ permissionLabel(permission) }}</span>
                        <code class="truncate text-xs text-base-content/50">{{ permission }}</code>
                    </span>
                </label>

                <p v-if="locked" class="px-2 text-xs text-base-content/60">
                    Superadmin selalu memiliki semua akses, jadi daftar ini tidak dapat diubah.
                </p>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="locked || form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        <Save v-else class="size-4" />
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
