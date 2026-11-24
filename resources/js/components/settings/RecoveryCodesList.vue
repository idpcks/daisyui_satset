<script setup>
import { Check, Copy } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';

defineProps({
    codes: { type: Array, default: () => [] },
    visible: { type: Boolean, default: false },
    copied: { type: [String, null], default: null },
});

const emit = defineEmits(['toggle', 'copy']);

const { t } = useI18n();
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h3 class="font-medium">{{ t('Kode Pemulihan') }}</h3>
                <p class="text-sm text-base-content/60">
                    {{
                        t(
                            'Simpan di tempat aman. Setiap kode hanya bisa dipakai sekali saat aplikasi autentikator tidak tersedia.',
                        )
                    }}
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-ghost btn-sm" @click="emit('toggle')">
                    {{ visible ? t('Sembunyikan') : t('Tampilkan') }}
                </button>
            </div>
        </div>

        <div v-if="visible" class="grid gap-2 rounded-box bg-base-200 p-3 sm:grid-cols-2">
            <div v-for="code in codes" :key="code" class="flex items-center justify-between gap-2">
                <code class="block text-sm">{{ code }}</code>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs"
                    :aria-label="t('Salin kode pemulihan :code', { code })"
                    @click="emit('copy', code)"
                >
                    <Check v-if="copied === code" class="size-4 text-success" />
                    <Copy v-else class="size-4" />
                </button>
            </div>
        </div>
        <p v-else class="text-sm text-base-content/60">{{ t('Kode pemulihan disembunyikan.') }}</p>
    </div>
</template>
