<script setup>
import { Link } from '@inertiajs/vue3';
import {
    KeyRound,
    LogIn,
    Mail,
    MailCheck,
    MoreVertical,
    PencilLine,
    Plus,
    SearchX,
    Trash2,
    UsersRound,
} from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import { roleLabel } from '@/lib/labels';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    users: { type: Object, required: true },
    selectedIds: { type: Array, default: () => [] },
    selectableIds: { type: Array, default: () => [] },
    allSelected: { type: Boolean, default: false },
    someSelected: { type: Boolean, default: false },
    isSelectable: { type: Function, required: true },
    resetPasswordProcessing: { type: [String, Number, null], default: null },
    magicLinkEnabled: { type: Boolean, default: false },
    hasFilters: { type: Boolean, default: false },
    canCreate: { type: Boolean, default: false },
});

const emit = defineEmits([
    'toggle-all',
    'update:selected-ids',
    'edit',
    'reset-password',
    'resend-link',
    'verify-email',
    'impersonate',
    'delete',
    'create',
]);

const { t } = useI18n();

const toggleSelection = (id, checked) => {
    const next = checked
        ? [...props.selectedIds, id]
        : props.selectedIds.filter((selectedId) => selectedId !== id);

    emit('update:selected-ids', next);
};
</script>

<template>
    <div class="overflow-x-auto rounded-box bg-base-100 shadow md:overflow-visible">
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
                            @change="emit('toggle-all', $event.target.checked)"
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
                            type="checkbox"
                            class="checkbox checkbox-sm"
                            :value="user.id"
                            :checked="selectedIds.includes(user.id)"
                            :disabled="!isSelectable(user)"
                            :aria-label="`${t('Pilih')} ${user.name}`"
                            @change="toggleSelection(user.id, $event.target.checked)"
                        />
                    </td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <div class="dropdown dropdown-end dropdown-bottom">
                                <button
                                    tabindex="0"
                                    type="button"
                                    class="btn btn-ghost btn-xs"
                                    :aria-label="t('Aksi lainnya')"
                                    :title="t('Aksi lainnya')"
                                >
                                    <MoreVertical class="size-4" />
                                </button>
                                <ul
                                    tabindex="0"
                                    class="dropdown-content menu bg-base-100 rounded-box z-50 w-52 p-2 shadow"
                                >
                                    <li v-if="user.can_update">
                                        <button
                                            type="button"
                                            :aria-label="t('Reset password')"
                                            :disabled="resetPasswordProcessing === user.id"
                                            @click="emit('reset-password', user)"
                                        >
                                            <KeyRound class="size-4" />
                                            {{ t('Reset password') }}
                                        </button>
                                    </li>
                                    <li v-if="user.must_change_password && magicLinkEnabled">
                                        <button
                                            type="button"
                                            :aria-label="t('Kirim ulang tautan')"
                                            @click="emit('resend-link', user)"
                                        >
                                            <Mail class="size-4" />
                                            {{ t('Kirim ulang tautan') }}
                                        </button>
                                    </li>
                                    <li v-if="!user.email_verified">
                                        <button
                                            type="button"
                                            :aria-label="t('Verifikasi email')"
                                            @click="emit('verify-email', user)"
                                        >
                                            <MailCheck class="size-4" />
                                            {{ t('Verifikasi email') }}
                                        </button>
                                    </li>
                                    <li v-if="user.can_impersonate">
                                        <button
                                            type="button"
                                            :aria-label="`${t('Masuk sebagai')} ${user.name}`"
                                            @click="emit('impersonate', user)"
                                        >
                                            <LogIn class="size-4" />
                                            {{ t('Masuk sebagai') }} {{ user.name }}
                                        </button>
                                    </li>
                                    <li v-if="user.can_delete">
                                        <div class="divider my-1"></div>
                                    </li>
                                    <li v-if="user.can_delete">
                                        <button
                                            type="button"
                                            class="text-error"
                                            :aria-label="t('Hapus pengguna')"
                                            @click="emit('delete', user)"
                                        >
                                            <Trash2 class="size-4" />
                                            {{ t('Hapus pengguna') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <button
                                v-if="user.can_update"
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :aria-label="t('Ubah pengguna')"
                                :title="t('Ubah pengguna')"
                                @click="emit('edit', user)"
                            >
                                <PencilLine class="size-4" />
                            </button>
                        </div>
                    </td>
                    <td>{{ user.username }}</td>
                    <td>
                        <p>{{ user.name }}</p>
                        <p class="mt-0.5 max-w-40 truncate text-xs text-base-content/60 md:hidden">
                            {{ user.email }}
                        </p>
                        <span class="badge badge-outline badge-xs mt-1 md:hidden">{{
                            roleLabel(user.role, t)
                        }}</span>
                    </td>
                    <td class="hidden md:table-cell">
                        <span class="block max-w-56 truncate" :title="user.email">{{
                            user.email
                        }}</span>
                    </td>
                    <td class="hidden md:table-cell">
                        <span class="badge badge-outline">{{ roleLabel(user.role, t) }}</span>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <span
                                class="badge"
                                :class="user.is_active ? 'badge-success' : 'badge-ghost'"
                            >
                                {{ user.is_active ? t('Aktif') : t('Nonaktif') }}
                            </span>
                            <span
                                v-if="user.must_change_password"
                                class="badge badge-warning badge-xs"
                            >
                                {{ t('Harus ganti password') }}
                            </span>
                            <span v-if="!user.email_verified" class="badge badge-info badge-xs">
                                {{ t('Email belum terverifikasi') }}
                            </span>
                        </div>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td colspan="7" class="p-0">
                        <EmptyState
                            :icon="hasFilters ? SearchX : UsersRound"
                            :title="
                                hasFilters
                                    ? t('Tidak ada pengguna yang cocok.')
                                    : t('Belum ada pengguna.')
                            "
                            :description="
                                hasFilters
                                    ? t('Coba ubah kata kunci pencarian atau filter status.')
                                    : t('Tambahkan pengguna pertama untuk mulai mengelola akses.')
                            "
                            :bordered="false"
                        >
                            <template v-if="!hasFilters && canCreate" #action>
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                    @click="emit('create')"
                                >
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
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="btn join-item btn-sm"
                    :class="{ 'btn-active': link.active }"
                    preserve-scroll
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label"></span>
                </Link>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <span v-else class="btn join-item btn-sm btn-disabled" v-html="link.label"></span>
            </template>
        </div>
    </div>
</template>
