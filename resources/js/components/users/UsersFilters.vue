<script setup>
import { useI18n } from '@/lib/i18n';

defineProps({
    search: { type: String, default: '' },
    status: { type: String, default: '' },
});

const emit = defineEmits(['update:search', 'update:status']);

const { t } = useI18n();
</script>

<template>
    <div class="mb-4 flex flex-wrap gap-2">
        <label for="user-search" class="sr-only">{{ t('Cari pengguna') }}</label>
        <input
            id="user-search"
            :value="search"
            type="search"
            :placeholder="t('Cari nama, email, atau username...')"
            class="input input-bordered input-sm"
            @input="emit('update:search', $event.target.value)"
        />
        <label for="user-status" class="sr-only">{{ t('Status pengguna') }}</label>
        <select
            id="user-status"
            :value="status"
            class="select select-bordered select-sm"
            @change="emit('update:status', $event.target.value)"
        >
            <option value="">{{ t('Semua status') }}</option>
            <option value="active">{{ t('Aktif') }}</option>
            <option value="inactive">{{ t('Nonaktif') }}</option>
        </select>
    </div>
</template>
