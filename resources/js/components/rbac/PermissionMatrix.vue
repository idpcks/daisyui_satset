<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronDown, Lock, Search } from 'lucide-vue-next';
import { permissionGroupLabel, permissionLabel, roleLabel } from '@/lib/labels';
import { rolePath } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const emptySet = new Set();

// `saved` adalah kondisi terakhir dari server, `draft` adalah perubahan lokal.
// Perubahan baru dikirim saat tombol Simpan pada kolom peran ditekan.
const saved = reactive({});
const draft = reactive({});
const pending = reactive({});

const query = ref('');
const collapsedGroups = ref(new Set());

const hasDifferences = (roleId) => {
    const current = draft[roleId] ?? emptySet;
    const baseline = saved[roleId] ?? emptySet;

    if (current.size !== baseline.size) {
        return true;
    }

    for (const permission of current) {
        if (!baseline.has(permission)) {
            return true;
        }
    }

    return false;
};

const syncRoles = () => {
    const roleIds = new Set(props.roles.map((role) => role.id));

    Object.keys(saved).forEach((roleId) => {
        if (!roleIds.has(roleId)) {
            delete saved[roleId];
            delete draft[roleId];
            delete pending[roleId];
        }
    });

    props.roles.forEach((role) => {
        if (!(role.id in draft)) {
            saved[role.id] = new Set(role.permissions);
            draft[role.id] = new Set(role.permissions);

            return;
        }

        // Jangan timpa draft peran yang sedang disimpan atau belum disimpan,
        // misalnya saat peran lain selesai disimpan dan props dimuat ulang.
        if (pending[role.id] || hasDifferences(role.id)) {
            return;
        }

        saved[role.id] = new Set(role.permissions);
        draft[role.id] = new Set(role.permissions);
    });
};

watch(() => props.roles, syncRoles, { immediate: true });

const dirtyRoleIds = computed(() => {
    const ids = new Set();

    props.roles.forEach((role) => {
        if (hasDifferences(role.id)) {
            ids.add(role.id);
        }
    });

    return ids;
});

const roleHasChanges = (roleId) => dirtyRoleIds.value.has(roleId);

const hasPermission = (roleId, permission) => (draft[roleId] ?? emptySet).has(permission);

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filteredPermissions = computed(() => {
    const needle = normalizedQuery.value;

    if (!needle) {
        return props.permissions;
    }

    return props.permissions.filter(
        (permission) =>
            permission.toLowerCase().includes(needle) ||
            permissionLabel(permission).toLowerCase().includes(needle),
    );
});

const permissionGroup = (permission) => {
    const [prefix] = permission.split('.');

    return prefix && prefix !== permission ? prefix : 'lainnya';
};

const groups = computed(() => {
    const grouped = new Map();

    filteredPermissions.value.forEach((permission) => {
        const name = permissionGroup(permission);

        if (!grouped.has(name)) {
            grouped.set(name, []);
        }

        grouped.get(name).push(permission);
    });

    return Array.from(grouped, ([name, permissions]) => ({
        name,
        label: permissionGroupLabel(name),
        permissions,
    }));
});

const countSelected = (roleId, permissions) => {
    const current = draft[roleId] ?? emptySet;
    let selected = 0;

    for (const permission of permissions) {
        if (current.has(permission)) {
            selected += 1;
        }
    }

    return selected;
};

const allSelected = (roleId, permissions) =>
    permissions.length > 0 && countSelected(roleId, permissions) === permissions.length;

const partiallySelected = (roleId, permissions) => {
    const selected = countSelected(roleId, permissions);

    return selected > 0 && selected < permissions.length;
};

const setPermissions = (role, permissions, shouldSelect) => {
    if (role.is_superadmin || pending[role.id]) {
        return;
    }

    const next = new Set(draft[role.id] ?? []);

    permissions.forEach((permission) => {
        if (shouldSelect) {
            next.add(permission);
        } else {
            next.delete(permission);
        }
    });

    draft[role.id] = next;
};

const toggle = (role, permission) => {
    if (role.is_superadmin || pending[role.id]) {
        return;
    }

    const next = new Set(draft[role.id] ?? []);

    if (next.has(permission)) {
        next.delete(permission);
    } else {
        next.add(permission);
    }

    draft[role.id] = next;
};

// Pilih semua hanya berlaku untuk permission yang sedang tampil, sehingga
// hasil pencarian tidak diam-diam memberi akses ke permission yang tersembunyi.
const toggleAllVisible = (role) => {
    const permissions = filteredPermissions.value;

    setPermissions(role, permissions, !allSelected(role.id, permissions));
};

const toggleGroupPermissions = (role, group) => {
    setPermissions(role, group.permissions, !allSelected(role.id, group.permissions));
};

const saveRole = (role) => {
    if (role.is_superadmin || pending[role.id] || !hasDifferences(role.id)) {
        return;
    }

    const permissions = [...(draft[role.id] ?? [])].sort();

    pending[role.id] = true;

    router.put(
        appUrl(rolePath(role.id)),
        { permissions },
        {
            only: ['roles', 'flash'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                saved[role.id] = new Set(permissions);
                draft[role.id] = new Set(permissions);
            },
            onError: () => {
                // Kembalikan ke kondisi terakhir yang tersimpan di server.
                draft[role.id] = new Set(saved[role.id] ?? []);
            },
            onFinish: () => {
                pending[role.id] = false;
            },
        },
    );
};

const cancelRole = (roleId) => {
    if (pending[roleId]) {
        return;
    }

    draft[roleId] = new Set(saved[roleId] ?? []);
};

const isGroupCollapsed = (group) =>
    normalizedQuery.value === '' && collapsedGroups.value.has(group.name);

const toggleGroupCollapsed = (name) => {
    const next = new Set(collapsedGroups.value);

    if (next.has(name)) {
        next.delete(name);
    } else {
        next.add(name);
    }

    collapsedGroups.value = next;
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="card-title text-base">Matriks Permission</h2>
                <p class="text-xs text-base-content/60">
                    Centang permission, lalu klik Simpan pada kolom peran.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="relative w-full sm:w-72">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-base-content/50"
                    />
                    <input
                        id="permission-search"
                        v-model="query"
                        type="search"
                        class="input input-bordered input-sm w-full pl-9"
                        placeholder="Cari permission..."
                        aria-label="Cari permission"
                    />
                </div>
                <p class="text-xs text-base-content/60" aria-live="polite">
                    {{ filteredPermissions.length }} dari {{ permissions.length }} permission
                </p>
            </div>

            <div
                class="max-h-[65vh] overflow-auto rounded-box border border-base-300"
                tabindex="0"
                role="region"
                aria-label="Matriks permission"
            >
                <table class="table table-sm">
                    <caption class="sr-only">
                        Permission yang dimiliki setiap peran
                    </caption>
                    <thead>
                        <tr>
                            <th
                                scope="col"
                                class="sticky top-0 left-0 z-30 min-w-48 border-r border-base-300 bg-base-100"
                            >
                                Permission
                            </th>
                            <th
                                v-for="role in roles"
                                :key="role.id"
                                scope="col"
                                class="sticky top-0 z-20 min-w-36 bg-base-100 text-center align-top"
                            >
                                <div class="flex flex-col items-center gap-1">
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
                                    <label
                                        v-if="!role.is_superadmin"
                                        class="flex items-center gap-1 text-xs font-normal whitespace-nowrap"
                                        :title="
                                            normalizedQuery === ''
                                                ? undefined
                                                : 'Hanya permission yang tampil'
                                        "
                                    >
                                        <input
                                            type="checkbox"
                                            data-role-checkbox
                                            class="checkbox checkbox-primary checkbox-xs"
                                            :checked="allSelected(role.id, filteredPermissions)"
                                            :indeterminate.prop="
                                                partiallySelected(role.id, filteredPermissions)
                                            "
                                            :disabled="
                                                pending[role.id] || filteredPermissions.length === 0
                                            "
                                            :aria-label="`Pilih semua permission yang tampil untuk ${roleLabel(role.name)}`"
                                            @change="toggleAllVisible(role)"
                                        />
                                        Pilih semua
                                    </label>
                                    <span v-if="roleHasChanges(role.id)" class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-xs"
                                            :disabled="pending[role.id]"
                                            :aria-label="`Simpan permission ${roleLabel(role.name)}`"
                                            @click="saveRole(role)"
                                        >
                                            {{ pending[role.id] ? 'Menyimpan...' : 'Simpan' }}
                                        </button>
                                        <button
                                            v-if="!pending[role.id]"
                                            type="button"
                                            class="btn btn-ghost btn-xs"
                                            :aria-label="`Batalkan perubahan ${roleLabel(role.name)}`"
                                            @click="cancelRole(role.id)"
                                        >
                                            Batal
                                        </button>
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in groups" :key="`group-${group.name}`">
                            <tr class="bg-base-200/70">
                                <th
                                    scope="row"
                                    class="sticky left-0 z-10 min-w-48 border-r border-base-300 bg-base-200 py-1 font-normal"
                                >
                                    <button
                                        v-if="normalizedQuery === ''"
                                        type="button"
                                        class="flex w-full items-center gap-2 text-left text-xs font-semibold tracking-wide uppercase"
                                        :aria-expanded="!isGroupCollapsed(group)"
                                        :aria-label="`${isGroupCollapsed(group) ? 'Tampilkan' : 'Sembunyikan'} kelompok ${group.label}`"
                                        @click="toggleGroupCollapsed(group.name)"
                                    >
                                        <ChevronDown
                                            class="size-4 transition-transform"
                                            :class="{ '-rotate-90': isGroupCollapsed(group) }"
                                        />
                                        {{ group.label }}
                                        <span class="badge badge-ghost badge-sm">
                                            {{ group.permissions.length }}
                                        </span>
                                    </button>
                                    <span
                                        v-else
                                        class="flex items-center gap-2 text-xs font-semibold tracking-wide uppercase"
                                    >
                                        {{ group.label }}
                                        <span class="badge badge-ghost badge-sm">
                                            {{ group.permissions.length }}
                                        </span>
                                    </span>
                                </th>
                                <td v-for="role in roles" :key="role.id" class="py-1 text-center">
                                    <input
                                        v-if="!role.is_superadmin"
                                        type="checkbox"
                                        data-group-checkbox
                                        class="checkbox checkbox-primary checkbox-xs"
                                        :checked="allSelected(role.id, group.permissions)"
                                        :indeterminate.prop="
                                            partiallySelected(role.id, group.permissions)
                                        "
                                        :disabled="pending[role.id]"
                                        :title="
                                            normalizedQuery === ''
                                                ? undefined
                                                : 'Hanya permission yang tampil'
                                        "
                                        :aria-label="`Pilih semua permission ${group.label} yang tampil untuk ${roleLabel(role.name)}`"
                                        @change="toggleGroupPermissions(role, group)"
                                    />
                                </td>
                            </tr>

                            <template v-if="!isGroupCollapsed(group)">
                                <tr
                                    v-for="permission in group.permissions"
                                    :key="permission"
                                    class="hover:bg-base-200/50"
                                >
                                    <th
                                        scope="row"
                                        class="sticky left-0 z-10 min-w-48 border-r border-base-300 bg-base-100 font-normal"
                                    >
                                        <span class="block text-sm">{{ permissionLabel(permission) }}</span>
                                        <code class="text-xs text-base-content/50">{{ permission }}</code>
                                    </th>
                                    <td v-for="role in roles" :key="role.id" class="text-center">
                                        <input
                                            type="checkbox"
                                            data-permission-checkbox
                                            class="checkbox checkbox-sm checkbox-primary"
                                            :checked="hasPermission(role.id, permission)"
                                            :disabled="role.is_superadmin || pending[role.id]"
                                            :aria-label="`${permissionLabel(permission)} untuk ${roleLabel(role.name)}`"
                                            @change="toggle(role, permission)"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <tr v-if="filteredPermissions.length === 0">
                            <td :colspan="roles.length + 1" class="text-sm text-base-content/60">
                                {{
                                    permissions.length === 0
                                        ? 'Belum ada permission yang bisa diatur.'
                                        : 'Tidak ada permission yang cocok dengan pencarian.'
                                }}
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
