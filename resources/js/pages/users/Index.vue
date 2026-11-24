<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { LogIn, Plus } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import UserForm from '@/components/users/UserForm.vue';
import UsersFilters from '@/components/users/UsersFilters.vue';
import UsersTable from '@/components/users/UsersTable.vue';
import UsersBulkActions from '@/components/users/UsersBulkActions.vue';
import GeneratedPasswordDialog from '@/components/users/GeneratedPasswordDialog.vue';
import Modal from '@/components/ui/Modal.vue';
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
const { error: toastError, success: toastSuccess } = useToast();
const page = usePage();

const generatedPassword = ref(null);
const generatedFor = ref(null);
const magicLinkSent = ref(false);
const passwordAcknowledged = ref(false);
const copied = ref(false);

const magicLinkEnabled = computed(() => page.props.features?.magic_link === true);

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

const resetPasswordProcessing = ref(null);

const resetPassword = (user) => {
    if (resetPasswordProcessing.value) {
        return;
    }

    resetPasswordProcessing.value = user.id;

    router.post(appUrl(`${routes.users}/${user.id}/reset-password`), {}, {
        preserveScroll: true,
        onSuccess: () => {},
        onError: () => toastError(t('Password gagal direset. Silakan coba lagi.')),
        onFinish: () => (resetPasswordProcessing.value = null),
    });
};

const resendPasswordLink = (user) => {
    router.post(appUrl(`${routes.users}/${user.id}/resend-password-link`), {}, {
        preserveScroll: true,
        onSuccess: () => toastSuccess(t('Tautan aman berhasil dikirim.')),
        onError: () => toastError(t('Gagal mengirim tautan aman.')),
    });
};

const verifyEmail = (user) => {
    router.post(appUrl(`${routes.users}/${user.id}/verify-email`), {}, {
        preserveScroll: true,
        onSuccess: () => toastSuccess(t('Email pengguna berhasil ditandai terverifikasi.')),
        onError: () => toastError(t('Gagal menandai email terverifikasi.')),
    });
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.generated_password) {
            generatedPassword.value = flash.generated_password;
            generatedFor.value = flash.generated_for ?? null;
            magicLinkSent.value = Boolean(flash.magic_link_sent);
            passwordAcknowledged.value = false;
            copied.value = false;
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
    copied.value = false;
};

const copyGeneratedPassword = async () => {
    if (!generatedPassword.value || !navigator?.clipboard?.writeText) {
        return;
    }

    await navigator.clipboard.writeText(generatedPassword.value);
    copied.value = true;
    toastSuccess(t('Password berhasil disalin.'));

    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const formTitle = computed(() => (editing.value ? t('Ubah Pengguna') : t('Tambah Pengguna')));

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

watch(
    () => props.users.data,
    () => {
        selectedIds.value = [];
    },
);

const bulkStatus = ref(null);
const bulkProcessing = ref(false);
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

const hasFilters = computed(() => Boolean(props.filters.search || props.filters.status));

const deleting = ref(null);
const deleteProcessing = ref(false);
const deleteOpen = computed({
    get: () => deleting.value !== null,
    set: (open) => {
        if (!open) {
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
            <Plus class="mr-2 size-4" /> {{ t('Tambah Pengguna') }}
        </button>
    </div>

    <UsersFilters v-model:search="filter.search" v-model:status="filter.status" />

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

    <UsersTable
        :users="users"
        :selected-ids="selectedIds"
        :selectable-ids="selectableIds"
        :all-selected="allSelected"
        :some-selected="someSelected"
        :is-selectable="isSelectable"
        :reset-password-processing="resetPasswordProcessing"
        :magic-link-enabled="magicLinkEnabled"
        :has-filters="hasFilters"
        :can-create="can('user.manage')"
        @toggle-all="toggleAll"
        @update:selected-ids="selectedIds = $event"
        @edit="openEdit"
        @reset-password="resetPassword"
        @resend-link="resendPasswordLink"
        @verify-email="verifyEmail"
        @impersonate="impersonating = $event"
        @delete="deleting = $event"
        @create="openCreate"
    />

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

    <GeneratedPasswordDialog
        :open="generatedPassword !== null"
        :password="generatedPassword"
        :for-user="generatedFor"
        :magic-link-sent="magicLinkSent"
        :magic-link-enabled="magicLinkEnabled"
        :acknowledged="passwordAcknowledged"
        :copied="copied"
        @update:acknowledged="passwordAcknowledged = $event"
        @close="closeGeneratedPassword"
        @copy="copyGeneratedPassword"
    />

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

    <UsersBulkActions
        :open="bulkStatus !== null"
        :selected-count="selectedIds.length"
        :is-active="bulkStatus === true"
        :processing="bulkProcessing"
        @confirm="confirmBulkStatus"
        @cancel="bulkStatus = null"
    />

    <Modal v-model="deleteOpen" :title="t('Hapus Pengguna')">
        <p v-if="deleting">
            {{ t('Yakin ingin menghapus pengguna') }} <strong>{{ deleting.name }}</strong> ({{ deleting.username }})?
        </p>

        <template #footer>
            <button type="button" class="btn btn-ghost" @click="deleteOpen = false">{{ t('Batal') }}</button>
            <button type="button" class="btn btn-error" :disabled="deleteProcessing" @click="confirmDelete">
                <span v-if="deleteProcessing" class="loading loading-spinner loading-sm"></span>
                {{ deleteProcessing ? t('Menghapus...') : t('Hapus') }}
            </button>
        </template>
    </Modal>
</template>
