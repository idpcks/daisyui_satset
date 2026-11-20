<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { LogIn, PencilLine, Plus, SearchX, Trash2, UsersRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserForm from '@/components/users/UserForm.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Modal from '@/components/ui/Modal.vue';
import { roleLabel } from '@/lib/labels';
import { useAuth } from '@/lib/useAuth';
import { useI18n } from '@/lib/i18n';
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
const { t } = useI18n();
const { error: toastError } = useToast();
const page = usePage();

const generatedPassword = ref(null);
const generatedFor = ref(null);
const magicLinkSent = ref(false);
const passwordAcknowledged = ref(false);

const generatedPasswordOpen = computed({
    get: () => generatedPassword.value !== null,
    set: (open) => {
        if (!open) {
            closeGeneratedPassword();
        }
    },
});

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

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.generated_password) {
            generatedPassword.value = flash.generated_password;
            generatedFor.value = flash.generated_for ?? null;
            magicLinkSent.value = Boolean(flash.magic_link_sent);
            passwordAcknowledged.value = false;
        }
    },
    { immediate: true },
);

const closeGeneratedPassword = () => {
    if (!passwordAcknowledged.value) {
        return;
    }

    generatedPassword.value = null;
    generatedFor.value = null;
    magicLinkSent.value = false;
};

const copyGeneratedPassword = async () => {
    if (!generatedPassword.value || !navigator?.clipboard?.writeText) {
        return;
    }

    await navigator.clipboard.writeText(generatedPassword.value);
};

const formTitle = computed(() => (editing.value ? t('Ubah Pengguna') : t('Tambah Pengguna')));

// Seleksi hanya mencakup baris di halaman ini; akun sendiri dan baris yang
// tidak boleh diubah server (mis. akun superadmin bagi non-superadmin) tidak
// bisa dipilih agar tidak ikut berubah statusnya.
const selectedIds = ref([]);
const isSelectable = (user) => user.can_update && user.id !== authUser.value?.id;
const selectableIds = computed(() => props.users.data.filter(isSelectable).map((user) => user.id));
const allSelected = computed(
    () => selectableIds.value.length > 0 && selectedIds.value.length === selectableIds.value.length,
);
const someSelected = computed(() => selectedIds.value.length > 0 && !allSelected.value);

const toggleAll = (checked) => {
    selectedIds.value = checked ? [...selectableIds.value] : [];
};

// Pindah halaman atau filter mengganti isi tabel, sehingga pilihan lama tidak
// lagi terlihat dan harus dibersihkan.
watch(
    () => props.users.data,
    () => {
        selectedIds.value = [];
    },
);

const bulkStatus = ref(null);
const bulkProcessing = ref(false);
const bulkOpen = computed({
    get: () => bulkStatus.value !== null,
    set: (open) => {
        if (!open) {
            bulkStatus.value = null;
            bulkProcessing.value = false;
        }
    },
});
const bulkTitle = computed(() => (bulkStatus.value ? t('Aktifkan Pengguna') : t('Nonaktifkan Pengguna')));

const openBulkStatus = (isActive) => {
    bulkStatus.value = isActive;
};

const confirmBulkStatus = () => {
    if (bulkStatus.value === null || bulkProcessing.value || selectedIds.value.length === 0) {
        return;
    }

    bulkProcessing.value = true;

    router.patch(appUrl(routes.usersBulkStatus), {
        ids: [...selectedIds.value],
        is_active: bulkStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            bulkStatus.value = null;
            selectedIds.value = [];
        },
        onError: () => toastError(t('Status pengguna gagal diubah. Silakan coba lagi.')),
        onFinish: () => (bulkProcessing.value = false),
    });
};

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
        onError: () => toastError(t('Pengguna gagal dihapus. Silakan coba lagi.')),
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
        onError: () => toastError(t('Gagal masuk sebagai pengguna. Silakan coba lagi.')),
        onFinish: () => (impersonationProcessing.value = false),
    });
};

</script>

<template>
    <Head :title="t('Pengguna')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-xl font-semibold">{{ t('Pengguna') }}</h1>
        <button v-if="can('user.manage')" type="button" class="btn btn-primary btn-sm" @click="openCreate">
           <Plus class="mr-2" /> {{ t('Tambah Pengguna') }}
        </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <label for="user-search" class="sr-only">{{ t('Cari pengguna') }}</label>
        <input
            id="user-search"
            v-model="filter.search"
            type="search"
            :placeholder="t('Cari nama, email, atau username...')"
            class="input input-bordered input-sm"
        />
        <label for="user-status" class="sr-only">{{ t('Status pengguna') }}</label>
        <select id="user-status" v-model="filter.status" class="select select-bordered select-sm">
            <option value="">{{ t('Semua status') }}</option>
            <option value="active">{{ t('Aktif') }}</option>
            <option value="inactive">{{ t('Nonaktif') }}</option>
        </select>
    </div>

    <div
        v-if="selectedIds.length > 0"
        class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-box bg-base-200 px-3 py-2"
    >
        <span class="text-sm font-medium">{{ selectedIds.length }} {{ t('pengguna dipilih') }}</span>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-success btn-sm" @click="openBulkStatus(true)">
                {{ t('Aktifkan') }}
            </button>
            <button type="button" class="btn btn-error btn-sm" @click="openBulkStatus(false)">
                {{ t('Nonaktifkan') }}
            </button>
            <button type="button" class="btn btn-ghost btn-sm" @click="selectedIds = []">
                {{ t('Batal pilih') }}
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-box bg-base-100 shadow">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-10">
                        <input
                            type="checkbox"
                            class="checkbox checkbox-sm"
                            :aria-label="t('Pilih semua pengguna')"
                            :checked="allSelected"
                            :indeterminate="someSelected"
                            :disabled="selectableIds.length === 0"
                            @change="toggleAll($event.target.checked)"
                        />
                    </th>
                    <th></th>
                    <th>{{ t('Username') }}</th>
                    <th>{{ t('Nama') }}</th>
                    <th class="hidden md:table-cell">{{ t('Email') }}</th>
                    <th class="hidden md:table-cell">{{ t('Peran') }}</th>
                    <th>{{ t('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users.data" :key="user.id">
                    <td>
                        <input
                            v-model="selectedIds"
                            type="checkbox"
                            class="checkbox checkbox-sm"
                            :value="user.id"
                            :disabled="!isSelectable(user)"
                            :aria-label="`${t('Pilih')} ${user.name}`"
                        />
                    </td>
                    <td>
                        <div class="flex justify-start gap-1">
                            <button
                                v-if="user.can_update"
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :aria-label="t('Ubah pengguna')"
                                :title="t('Ubah pengguna')"
                                @click="openEdit(user)"
                            >
                                <PencilLine class="size-4" />
                            </button>
                            <button
                                v-if="user.can_impersonate"
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :aria-label="`${t('Masuk sebagai')} ${user.name}`"
                                :title="`${t('Masuk sebagai')} ${user.name}`"
                                @click="impersonating = user"
                            >
                                <LogIn class="size-4" />
                            </button>
                            <button
                                v-if="user.can_delete"
                                type="button"
                                class="btn btn-ghost btn-xs text-error"
                                :aria-label="t('Hapus pengguna')"
                                :title="t('Hapus pengguna')"
                                @click="deleting = user"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </td>
                    <td>{{ user.username }}</td>
                    <td>
                        <p>{{ user.name }}</p>
                        <p class="mt-0.5 max-w-40 truncate text-xs text-base-content/60 md:hidden">
                            {{ user.email }}
                        </p>
                        <span class="badge badge-outline badge-xs mt-1 md:hidden">{{ roleLabel(user.role, t) }}</span>
                    </td>
                    <td class="hidden md:table-cell">
                        <span class="block max-w-56 truncate" :title="user.email">{{ user.email }}</span>
                    </td>
                    <td class="hidden md:table-cell">
                        <span class="badge badge-outline">{{ roleLabel(user.role, t) }}</span>
                    </td>
                    <td>
                        <span class="badge" :class="user.is_active ? 'badge-success' : 'badge-ghost'">
                            {{ user.is_active ? t('Aktif') : t('Nonaktif') }}
                        </span>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td colspan="7" class="p-0">
                        <EmptyState
                            :icon="hasFilters ? SearchX : UsersRound"
                            :title="hasFilters ? t('Tidak ada pengguna yang cocok.') : t('Belum ada pengguna.')"
                            :description="
                                hasFilters
                                    ? t('Coba ubah kata kunci pencarian atau filter status.')
                                    : t('Tambahkan pengguna pertama untuk mulai mengelola akses.')
                            "
                            :bordered="false"
                        >
                            <template v-if="!hasFilters && can('user.manage')" #action>
                                <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                                    <Plus class="size-4" />
                                    {{ t('Tambah Pengguna') }}
                                </button>
                            </template>
                        </EmptyState>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="users.last_page > 1" class="mt-4 overflow-x-auto pb-1">
        <div class="join">
            <template v-for="link in users.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="btn join-item btn-sm" :class="{ 'btn-active': link.active }" preserve-scroll>
                    <span v-html="link.label"></span>
                </Link>
                <span v-else class="btn join-item btn-sm btn-disabled" v-html="link.label"></span>
            </template>
        </div>
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

    <!-- Password sementara (ditampilkan sekali) -->
    <Modal v-model="generatedPasswordOpen" :title="t('Password Sementara')" size="lg" :closeable="passwordAcknowledged">
        <p v-if="generatedFor" class="mb-3 text-sm">
            {{ t('Password untuk') }} <strong>{{ generatedFor }}</strong>:
        </p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-box bg-base-200 px-3 py-2 font-mono text-sm">{{ generatedPassword }}</code>
            <button type="button" class="btn btn-outline btn-sm" @click="copyGeneratedPassword">
                {{ t('Salin') }}
            </button>
        </div>
        <p class="mt-3 text-sm text-warning">
            {{ t('Password ini hanya ditampilkan sekali. Simpan di tempat aman dan serahkan ke pengguna.') }}
        </p>
        <p class="mt-2 text-sm" :class="magicLinkSent ? 'text-success' : 'text-error'">
            {{
                magicLinkSent
                    ? t('Tautan aman juga telah dikirim ke email pengguna.')
                    : t('Tautan email gagal dikirim. Serahkan password ini secara manual.')
            }}
        </p>
        <label class="label cursor-pointer justify-start gap-2 mt-4">
            <input v-model="passwordAcknowledged" type="checkbox" class="checkbox checkbox-sm" />
            <span class="text-sm">{{ t('Saya telah menyimpan password ini') }}</span>
        </label>

        <template #footer>
            <button type="button" class="btn btn-primary" :disabled="!passwordAcknowledged" @click="closeGeneratedPassword">
                {{ t('Tutup') }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi impersonation -->
    <Modal v-model="impersonationOpen" :title="t('Masuk sebagai Pengguna')">
        <p v-if="impersonating">
            {{ t('Anda akan masuk sebagai') }} <strong>{{ impersonating.name }}</strong> ({{ impersonating.username }}).
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            {{ t('Mulai dan berhentinya sesi impersonation ini tercatat di Audit Log. Kembali ke akun Anda lewat banner di bagian atas halaman.') }}
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="impersonationOpen = false">{{ t('Batal') }}</button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="impersonationProcessing"
                @click="confirmImpersonation"
            >
                <span v-if="impersonationProcessing" class="loading loading-spinner loading-sm"></span>
                <LogIn v-else class="size-4" />
                {{ impersonationProcessing ? t('Masuk...') : t('Masuk sebagai') }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi aktif/nonaktif massal -->
    <Modal v-model="bulkOpen" :title="bulkTitle">
        <p>
            <strong>{{ selectedIds.length }}</strong> {{ t('pengguna akan') }}
            {{ bulkStatus ? t('diaktifkan') : t('dinonaktifkan') }}.
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            {{ t('Perubahan status berlaku untuk semua pengguna yang dipilih pada halaman ini.') }}
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="bulkOpen = false">{{ t('Batal') }}</button>
            <button
                type="button"
                class="btn"
                :class="bulkStatus ? 'btn-success' : 'btn-error'"
                :disabled="bulkProcessing"
                @click="confirmBulkStatus"
            >
                <span v-if="bulkProcessing" class="loading loading-spinner loading-sm"></span>
                {{ bulkProcessing ? t('Memproses...') : bulkTitle }}
            </button>
        </template>
    </Modal>

    <!-- Konfirmasi hapus -->
    <Modal v-model="deleteOpen" :title="t('Hapus Pengguna')">
        <p v-if="deleting">
            {{ t('Yakin ingin menghapus pengguna') }} <strong>{{ deleting.name }}</strong> ({{ deleting.username }})?
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="deleteOpen = false">{{ t('Batal') }}</button>
            <button type="button" class="btn btn-error" :disabled="deleteProcessing" @click="confirmDelete">
                <span v-if="deleteProcessing" class="loading loading-spinner loading-sm"></span>
                <Trash2 v-else class="size-4" />
                {{ deleteProcessing ? t('Menghapus...') : t('Hapus') }}
            </button>
        </template>
    </Modal>
</template>
