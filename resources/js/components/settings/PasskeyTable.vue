<script setup>
import { KeyRound, Trash2 } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';

defineProps({
    passkeys: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
});

const emit = defineEmits(['delete']);

const { t } = useI18n();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>{{ t('Nama') }}</th>
                    <th class="hidden sm:table-cell">{{ t('Authenticator') }}</th>
                    <th>{{ t('Dibuat') }}</th>
                    <th>{{ t('Terakhir Dipakai') }}</th>
                    <th
                        class="sticky right-0 z-20 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                    >
                        {{ t('Aksi') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="passkey in passkeys" :key="passkey.id">
                    <td>
                        <div class="flex items-center gap-2">
                            <KeyRound class="size-4 text-base-content/60" />
                            <span class="font-medium">{{ passkey.name }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-base-content/60 sm:hidden">
                            {{ passkey.authenticator ?? '—' }}
                        </p>
                    </td>
                    <td class="hidden sm:table-cell">{{ passkey.authenticator ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ formatDate(passkey.created_at) }}</td>
                    <td class="whitespace-nowrap">
                        {{
                            passkey.last_used_at
                                ? formatDate(passkey.last_used_at)
                                : t('Belum pernah')
                        }}
                    </td>
                    <td
                        class="sticky right-0 z-10 border-l border-base-300 bg-base-100 text-right md:border-l-0"
                    >
                        <button
                            type="button"
                            class="btn btn-ghost btn-xs text-error"
                            @click="emit('delete', passkey)"
                        >
                            <Trash2 class="size-3.5" />
                            {{ t('Hapus') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
