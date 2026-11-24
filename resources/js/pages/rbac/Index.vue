<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Lock, PencilLine, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PermissionMatrix from '@/components/rbac/PermissionMatrix.vue';
import Modal from '@/components/ui/Modal.vue';
import { roleLabel } from '@/lib/labels';
import { rolePath, routes } from '@/lib/routes';
import { useI18n } from '@/lib/i18n';
import { appUrl } from '@/lib/url';
import { useToast } from '@/lib/useToast';

defineOptions({ layout: AppLayout, inheritAttrs: false });

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const { error: toastError } = useToast();
const { t } = useI18n();

const createForm = useForm({ name: '' });

const roleSearch = ref('');

const filteredRoles = computed(() =>
    props.roles.filter((role) =>
        roleLabel(role.name, t).toLowerCase().includes(roleSearch.value.trim().toLowerCase()),
    ),
);

const submitCreate = () => {
    createForm.post(appUrl(routes.rbacRoles), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const renamingRole = ref(null);
const renameOpen = ref(false);
const renameForm = useForm({ name: '' });

const openRename = (role) => {
    renamingRole.value = role;
    renameForm.name = role.name;
    renameForm.clearErrors();
    renameOpen.value = true;
};

const submitRename = () => {
    if (!renamingRole.value) {
        return;
    }

    renameForm.patch(appUrl(rolePath(renamingRole.value.id)), {
        preserveScroll: true,
        onSuccess: () => {
            renameOpen.value = false;
        },
    });
};

const deletingRole = ref(null);
const deleteProcessing = ref(false);

const deleteOpen = computed({
    get: () => deletingRole.value !== null,
    set: (open) => {
        if (!open) {
            deletingRole.value = null;
            deleteProcessing.value = false;
        }
    },
});

const confirmDelete = () => {
    if (!deletingRole.value || deleteProcessing.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(appUrl(rolePath(deletingRole.value.id)), {
        // Inertia mengirim body {} secara default; DELETE berbody menggantung di PHP dev server.
        data: undefined,
        preserveScroll: true,
        onSuccess: () => (deletingRole.value = null),
        onError: () => toastError(t('Peran gagal dihapus. Silakan coba lagi.')),
        onFinish: () => (deleteProcessing.value = false),
    });
};
</script>

<template>
    <Head :title="t('RBAC')" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">RBAC</h1>
        <p class="text-sm text-base-content/60">
            {{
                t(
                    'Kelola peran dan atur permission-nya. Perubahan berlaku untuk semua pengguna dengan peran tersebut.',
                )
            }}
        </p>
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <div class="card bg-base-100 shadow">
            <div class="card-body gap-3">
                <h2 class="card-title text-base">{{ t('Peran') }}</h2>

                <input
                    v-model="roleSearch"
                    type="search"
                    :placeholder="t('Cari peran...')"
                    class="input input-bordered input-sm w-full"
                    :aria-label="t('Cari peran')"
                />

                <ul class="max-h-72 space-y-1 overflow-y-auto pr-1">
                    <li
                        v-for="role in filteredRoles"
                        :key="role.id"
                        class="rounded-box px-2 py-1.5 hover:bg-base-200"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium">{{
                                roleLabel(role.name, t)
                            }}</span>

                            <span
                                v-if="role.is_superadmin"
                                class="badge badge-ghost badge-sm gap-1"
                            >
                                <Lock class="size-3" />
                                {{ t('Tetap') }}
                            </span>
                            <div
                                v-if="role.can_rename || role.can_delete"
                                class="flex items-center gap-0.5"
                            >
                                <button
                                    v-if="role.can_rename"
                                    type="button"
                                    class="btn btn-ghost btn-xs"
                                    :aria-label="
                                        t('Ubah nama :role', { role: roleLabel(role.name, t) })
                                    "
                                    :title="t('Ubah nama :role', { role: roleLabel(role.name, t) })"
                                    @click="openRename(role)"
                                >
                                    <PencilLine class="size-3.5" />
                                </button>
                                <button
                                    v-if="role.can_delete"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    :aria-label="
                                        t('Hapus peran :role', { role: roleLabel(role.name, t) })
                                    "
                                    :title="
                                        t('Hapus peran :role', { role: roleLabel(role.name, t) })
                                    "
                                    @click="deletingRole = role"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-base-content/60">
                            {{ t(':count pengguna', { count: role.users_count }) }} ·
                            {{ t(':count permission', { count: role.permissions.length }) }}
                        </p>
                    </li>
                </ul>

                <p v-if="roles.length === 0" class="text-sm text-base-content/60">
                    {{ t('Belum ada peran.') }}
                </p>
                <p v-else-if="filteredRoles.length === 0" class="text-sm text-base-content/60">
                    {{ t('Peran tidak ditemukan.') }}
                </p>

                <div class="divider my-1"></div>

                <form id="create-role-form" class="space-y-2" @submit.prevent="submitCreate">
                    <label for="role-name" class="text-sm font-medium">{{
                        t('Tambah peran')
                    }}</label>
                    <input
                        id="role-name"
                        v-model="createForm.name"
                        type="text"
                        :placeholder="t('mis. auditor')"
                        class="input input-bordered input-sm w-full"
                        :aria-invalid="Boolean(createForm.errors.name)"
                    />
                    <p v-if="createForm.errors.name" class="text-xs text-error">
                        {{ createForm.errors.name }}
                    </p>
                    <p v-else class="text-xs text-base-content/60">
                        {{
                            t(
                                'Huruf kecil, angka, titik, garis bawah, atau strip. Contoh: auditor.',
                            )
                        }}
                    </p>
                    <button
                        type="submit"
                        class="btn btn-primary btn-sm w-full"
                        :disabled="createForm.processing || createForm.name.trim() === ''"
                    >
                        <span
                            v-if="createForm.processing"
                            class="loading loading-spinner loading-sm"
                        ></span>
                        <Plus v-else class="size-4" />
                        {{ t('Tambah Peran') }}
                    </button>
                </form>
            </div>
        </div>

        <PermissionMatrix :roles="roles" :permissions="permissions" />
    </div>

    <Modal v-model="renameOpen" :title="t('Ubah Nama Peran')">
        <form id="rename-role-form" class="space-y-2" @submit.prevent="submitRename">
            <label for="rename-role-name" class="text-sm font-medium">{{ t('Nama peran') }}</label>
            <input
                id="rename-role-name"
                v-model="renameForm.name"
                type="text"
                class="input input-bordered w-full"
                :aria-invalid="Boolean(renameForm.errors.name)"
            />
            <p v-if="renameForm.errors.name" class="text-xs text-error">
                {{ renameForm.errors.name }}
            </p>
            <p v-else class="text-xs text-base-content/60">
                {{ t('Permission dan pengguna peran ini tetap sama setelah namanya diubah.') }}
            </p>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-ghost" @click="renameOpen = false">
                    {{ t('Batal') }}
                </button>
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="renameForm.processing || renameForm.name.trim() === ''"
                >
                    <span
                        v-if="renameForm.processing"
                        class="loading loading-spinner loading-sm"
                    ></span>
                    {{ t('Simpan') }}
                </button>
            </div>
        </form>
    </Modal>

    <Modal v-model="deleteOpen" :title="t('Hapus Peran')">
        <p v-if="deletingRole">
            {{ t('Yakin ingin menghapus peran') }}
            <strong>{{ roleLabel(deletingRole.name, t) }}</strong>?
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            {{
                t(
                    'Peran hanya bisa dihapus jika tidak dipakai pengguna. Tindakan ini tidak bisa dibatalkan.',
                )
            }}
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="deleteOpen = false">
                {{ t('Batal') }}
            </button>
            <button
                type="button"
                class="btn btn-error"
                :disabled="deleteProcessing"
                @click="confirmDelete"
            >
                <span v-if="deleteProcessing" class="loading loading-spinner loading-sm"></span>
                <Trash2 v-else class="size-4" />
                {{ deleteProcessing ? t('Menghapus...') : t('Hapus') }}
            </button>
        </template>
    </Modal>
</template>
