<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Lock } from 'lucide-vue-next';
import { permissionLabel, roleLabel } from '@/lib/labels';
import { rolePath } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

// Salinan lokal agar checkbox langsung berubah sebelum respons server datang.
const selections = reactive({});
const pending = reactive({});

const syncSelections = () => {
    props.roles.forEach((role) => {
        if (!pending[role.id]) {
            selections[role.id] = [...role.permissions];
        }
    });
};

watch(() => props.roles, syncSelections, { immediate: true, deep: true });

const isGranted = (role, permission) => (selections[role.id] ?? []).includes(permission);

const toggle = (role, permission) => {
    if (role.is_superadmin || pending[role.id]) {
        return;
    }

    const current = selections[role.id] ?? [];
    const next = current.includes(permission)
        ? current.filter((item) => item !== permission)
        : [...current, permission];

    selections[role.id] = next;
    pending[role.id] = true;

    router.put(
        appUrl(rolePath(role.id)),
        { permissions: next },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Kembalikan ke kondisi terakhir yang tersimpan di server.
                selections[role.id] = [...role.permissions];
            },
            onFinish: () => {
                pending[role.id] = false;
            },
        },
    );
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="card-title text-base">Matriks Permission</h2>
                <p class="text-xs text-base-content/60">
                    Centang untuk memberi akses. Perubahan langsung disimpan.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <caption class="sr-only">
                        Permission yang dimiliki setiap peran
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Permission</th>
                            <th v-for="role in roles" :key="role.id" scope="col" class="text-center">
                                <span class="inline-flex items-center gap-1">
                                    {{ roleLabel(role.name) }}
                                    <Lock
                                        v-if="role.is_superadmin"
                                        class="size-3"
                                        aria-label="Permission tidak dapat diubah"
                                    />
                                    <span
                                        v-if="pending[role.id]"
                                        class="loading loading-spinner loading-xs"
                                        aria-label="Menyimpan"
                                    ></span>
                                </span>
                                <span class="block text-xs font-normal text-base-content/50">
                                    {{ role.users_count }} pengguna
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="permission in permissions" :key="permission">
                            <th scope="row" class="font-normal">
                                <span class="block text-sm">{{ permissionLabel(permission) }}</span>
                                <code class="text-xs text-base-content/50">{{ permission }}</code>
                            </th>
                            <td v-for="role in roles" :key="role.id" class="text-center">
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm checkbox-primary"
                                    :checked="isGranted(role, permission)"
                                    :disabled="role.is_superadmin || pending[role.id]"
                                    :aria-label="`${permissionLabel(permission)} untuk ${roleLabel(role.name)}`"
                                    @change="toggle(role, permission)"
                                />
                            </td>
                        </tr>
                        <tr v-if="permissions.length === 0">
                            <td :colspan="roles.length + 1" class="text-sm text-base-content/60">
                                Belum ada permission yang bisa diatur.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-base-content/60">
                Superadmin selalu memiliki semua akses lewat Gate, jadi kolomnya dikunci.
            </p>
        </div>
    </div>
</template>
