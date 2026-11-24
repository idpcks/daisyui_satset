<script setup>
import { Search } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';

defineProps({
    query: { type: String, default: '' },
    shown: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
});

const emit = defineEmits(['update:query']);

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="relative w-full sm:w-72">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-base-content/50"
                aria-hidden="true"
            />
            <input
                id="permission-search"
                :value="query"
                type="search"
                class="input input-bordered input-sm w-full pl-9"
                :placeholder="t('Cari permission...')"
                :aria-label="t('Cari permission')"
                @input="emit('update:query', $event.target.value)"
            />
        </div>
        <p class="text-xs text-base-content/60" aria-live="polite">
            {{ t(':shown dari :total permission', { shown, total }) }}
        </p>
    </div>
</template>
