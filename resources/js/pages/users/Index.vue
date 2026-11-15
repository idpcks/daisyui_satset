<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { LogIn, PencilLine, Plus, SearchX, Trash2, UsersRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserForm from '@/components/users/UserForm.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Modal from '@/components/ui/Modal.vue';
import { roleLabel } from '@/lib/labels';
import { useAuth } from '@/lib/useAuth';
import { useToast } from '@/lib/useToast';
import { routes, userImpersonatePath, userPath } from '@/lib/routes';
import { appUrl } from '@/lib/url';

defineOptions({ layout: AppLayout, inheritAttrs: false });

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
});

const { user: authUser, can } = useAuth();
const { error: toastError } = useToast();

const filter = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

let timer;
watch(filter, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const query = Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== ''));
        router.get(appUrl(routes.users), query, { preserveState: true, replace: true });
    }, 300);
});

// Satu modal form dipakai untuk tambah (editing null) dan ubah (editing berisi pengguna).
const editing = ref(null);
const formOpen = ref(false);

const openCreate = () => {
    editing.value = null;
    formOpen.value = true;
};

const openEdit = (user) => {
    editing.value = user;
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const formTitle = computed(() => (editing.value ? 'Ubah Pengguna' : 'Tambah Pengguna'));

// Hasil kosong bisa berarti datanya memang belum ada, atau filter tidak
// menemukan apa pun — pesan empty state dibedakan.
const hasFilters = computed(() => Boolean(props.filters.search || props.filters.status));

const deleting = ref(null);
const deleteProcessing = ref(false);
const deleteOpen = computed({
    get: () => deleting.value !== null,
    set: (open) => {
        if (!open) {
            // Modal sengaja tetap bisa ditutup saat request berjalan supaya UI
            // tidak terkunci bila request menggantung. Request yang sudah
            // terlanjur dikirim tetap diselesaikan oleh server.
            deleting.value = null;
            deleteProcessing.value = false;
        }
    },
});

const confirmDelete = () => {
    if (!deleting.value || deleteProcessing.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(appUrl(userPath(deleting.value.id)), {
        // Inertia mengirim body {} secara default; DELETE berbody menggantung di PHP dev server.
        data: undefined,
        preserveScroll: true,
        onSuccess: () => (deleting.value = null),
        onError: () => toastError('Pengguna gagal dihapus. Silakan coba lagi.'),
        onFinish: () => (deleteProcessing.value = false),
    });
};

const impersonating = ref(null);
const impersonationProcessing = ref(false);
const impersonationOpen = computed({
    get: () => impersonating.value !== null,
    set: (open) => {
        if (!open) {
            impersonating.value = null;
            impersonationProcessing.value = false;
        }
    },
});

const confirmImpersonation = () => {
    if (!impersonating.value || impersonationProcessing.value) {
        return;
    }

    impersonationProcessing.value = true;

    router.post(appUrl(userImpersonatePath(impersonating.value.id)), {}, {
        preserveScroll: true,
        onSuccess: () => (impersonating.value = null),
        onError: () => toastError('Gagal masuk sebagai pengguna. Silakan coba lagi.'),
        onFinish: () => (impersonationProcessing.value = false),
    });
};

</script>

<template>
    <Head title="Pengguna" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold">Pengguna</h1>
        <button v-if="can('user.manage')" type="button" class="btn btn-primary btn-sm" @click="openCreate">
           <Plus class="mr-2" /> Tambah Pengguna
        </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <label for="user-search" class="sr-only">Cari pengguna</label>
        <input
            id="user-search"
            v-model="filter.search"
            type="search"
            placeholder="Cari nama, username, atau email..."
            class="input input-bordered input-sm"
        />
        <label for="user-status" class="sr-only">Status pengguna</label>
        <select id="user-status" v-model="filter.status" class="select select-bordered select-sm">
            <option value="">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-box bg-base-100 shadow">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users.data" :key="user.id">
                    <td class="flex justify-start gap-1">
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs"
                            aria-label="Ubah pengguna"
                            title="Ubah pengguna"
                            @click="openEdit(user)"
                        >
                            <PencilLine class="size-4" />
                        </button>
                        <button
                            v-if="user.can_impersonate"
                            type="button"
                            class="btn btn-ghost btn-xs"
                            :aria-label="`Masuk sebagai ${user.name}`"
                            :title="`Masuk sebagai ${user.name}`"
                            @click="impersonating = user"
                        >
                            <LogIn class="size-4" />
                        </button>
                        <button
                            v-if="user.id !== authUser?.id"
                            type="button"
                            class="btn btn-ghost btn-xs text-error"
                            aria-label="Hapus pengguna"
                            title="Hapus pengguna"
                            @click="deleting = user"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </td>
                    <td>{{ user.username }}</td>
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                        <span class="badge badge-outline">{{ roleLabel(user.role) }}</span>
                    </td>
                    <td>
                        <span class="badge" :class="user.is_active ? 'badge-success' : 'badge-ghost'">
                            {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td colspan="6" class="p-0">
                        <EmptyState
                            :icon="hasFilters ? SearchX : UsersRound"
                            :title="hasFilters ? 'Tidak ada pengguna yang cocok.' : 'Belum ada pengguna.'"
                            :description="
                                hasFilters
                                    ? 'Coba ubah kata kunci pencarian atau filter status.'
                                    : 'Tambahkan pengguna pertama untuk mulai mengelola akses.'
                            "
                            :bordered="false"
                        >
                            <template v-if="!hasFilters && can('user.manage')" #action>
                                <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                                    <Plus class="size-4" />
                                    Tambah Pengguna
                                </button>
                            </template>
                        </EmptyState>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="users.last_page > 1" class="join mt-4">
        <template v-for="link in users.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" class="btn join-item btn-sm" :class="{ 'btn-active': link.active }" preserve-scroll>
                <span v-html="link.label"></span>
            </Link>
            <span v-else class="btn join-item btn-sm btn-disabled" v-html="link.label"></span>
        </template>
    </div>

    <!-- Form tambah/ubah pengguna -->
    <Modal v-model="formOpen" :title="formTitle" size="lg">
        <UserForm
            v-if="formOpen"
            :key="editing?.id ?? 'create'"
            :user="editing"
            :roles="roles"
            @success="closeForm"
            @cancel="closeForm"
        />
    </Modal>

    <!-- Konfirmasi impersonation -->
    <Modal v-model="impersonationOpen" title="Masuk sebagai Pengguna">
        <p v-if="impersonating">
            Anda akan masuk sebagai <strong>{{ impersonating.name }}</strong> ({{ impersonating.username }}).
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            Mulai dan berhentinya sesi impersonation ini tercatat di Audit Log. Kembali ke akun Anda lewat banner di
            bagian atas halaman.
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="impersonationOpen = false">Batal</button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="impersonationProcessing"
                @click="confirmImpersonation"
            >
                <span v-if="impersonationProcessing" class="loading loading-spinner loading-sm"></span>
                <LogIn v-else class="size-4" />
                {{ impersonationProcessing ? 'Masuk...' : 'Masuk sebagai' }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi hapus -->
    <Modal v-model="deleteOpen" title="Hapus Pengguna">
        <p v-if="deleting">
            Yakin ingin menghapus pengguna <strong>{{ deleting.name }}</strong> ({{ deleting.username }})?
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
