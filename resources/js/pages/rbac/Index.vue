<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Lock, PencilLine, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import PermissionMatrix from '@/components/rbac/PermissionMatrix.vue';
import Modal from '@/components/ui/Modal.vue';
import { roleLabel } from '@/lib/labels';
import { rolePath, routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useToast } from '@/lib/useToast';

defineOptions({ layout: AppLayout, inheritAttrs: false });

defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const { error: toastError } = useToast();

const createForm = useForm({ name: '' });

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
        onError: () => toastError('Peran gagal dihapus. Silakan coba lagi.'),
        onFinish: () => (deleteProcessing.value = false),
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
                            <span class="truncate text-sm font-medium">{{ roleLabel(role.name) }}</span>

                            <span v-if="role.is_superadmin" class="badge badge-ghost badge-sm gap-1">
                                <Lock class="size-3" />
                                Tetap
                            </span>
                            <div
                                v-if="role.can_rename || role.can_delete"
                                class="flex items-center gap-0.5"
                            >
                                <button
                                    v-if="role.can_rename"
                                    type="button"
                                    class="btn btn-ghost btn-xs"
                                    :aria-label="`Ubah nama ${roleLabel(role.name)}`"
                                    :title="`Ubah nama ${roleLabel(role.name)}`"
                                    @click="openRename(role)"
                                >
                                    <PencilLine class="size-3.5" />
                                </button>
                                <button
                                    v-if="role.can_delete"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    :aria-label="`Hapus peran ${roleLabel(role.name)}`"
                                    :title="`Hapus peran ${roleLabel(role.name)}`"
                                    @click="deletingRole = role"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-base-content/60">
                            {{ role.users_count }} pengguna · {{ role.permissions.length }} permission
                        </p>
                    </li>
                </ul>

                <p v-if="roles.length === 0" class="text-sm text-base-content/60">Belum ada peran.</p>

                <div class="divider my-1"></div>

                <form id="create-role-form" class="space-y-2" @submit.prevent="submitCreate">
                    <label for="role-name" class="text-sm font-medium">Tambah peran</label>
                    <input
                        id="role-name"
                        v-model="createForm.name"
                        type="text"
                        placeholder="mis. auditor"
                        class="input input-bordered input-sm w-full"
                        :aria-invalid="Boolean(createForm.errors.name)"
                    />
                    <p v-if="createForm.errors.name" class="text-xs text-error">{{ createForm.errors.name }}</p>
                    <p v-else class="text-xs text-base-content/60">
                        Huruf kecil, angka, titik, garis bawah, atau strip. Contoh: auditor.
                    </p>
                    <button
                        type="submit"
                        class="btn btn-primary btn-sm w-full"
                        :disabled="createForm.processing || createForm.name.trim() === ''"
                    >
                        <span v-if="createForm.processing" class="loading loading-spinner loading-sm"></span>
                        <Plus v-else class="size-4" />
                        Tambah Peran
                    </button>
                </form>
            </div>
        </div>

        <PermissionMatrix :roles="roles" :permissions="permissions" />
    </div>

    <Modal v-model="renameOpen" title="Ubah Nama Peran">
        <form id="rename-role-form" class="space-y-2" @submit.prevent="submitRename">
            <label for="rename-role-name" class="text-sm font-medium">Nama peran</label>
            <input
                id="rename-role-name"
                v-model="renameForm.name"
                type="text"
                class="input input-bordered w-full"
                :aria-invalid="Boolean(renameForm.errors.name)"
            />
            <p v-if="renameForm.errors.name" class="text-xs text-error">{{ renameForm.errors.name }}</p>
            <p v-else class="text-xs text-base-content/60">
                Permission dan pengguna peran ini tetap sama setelah namanya diubah.
            </p>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-ghost" @click="renameOpen = false">Batal</button>
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="renameForm.processing || renameForm.name.trim() === ''"
                >
                    <span v-if="renameForm.processing" class="loading loading-spinner loading-sm"></span>
                    Simpan
                </button>
            </div>
        </form>
    </Modal>

    <Modal v-model="deleteOpen" title="Hapus Peran">
        <p v-if="deletingRole">
            Yakin ingin menghapus peran <strong>{{ roleLabel(deletingRole.name) }}</strong>?
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            Peran hanya bisa dihapus jika tidak dipakai pengguna. Tindakan ini tidak bisa dibatalkan.
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="deleteOpen = false">Batal</button>
            <button type="button" class="btn btn-error" :disabled="deleteProcessing" @click="confirmDelete">
                <span v-if="deleteProcessing" class="loading loading-spinner loading-sm"></span>
                <Trash2 v-else class="size-4" />
                {{ deleteProcessing ? 'Menghapus...' : 'Hapus' }}
            </button>
        </template>
    </Modal>
</template>
