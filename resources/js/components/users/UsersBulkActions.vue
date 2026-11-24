<script setup>
import { computed } from 'vue';
import Modal from '@/components/ui/Modal.vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    open: { type: Boolean, default: false },
    selectedCount: { type: Number, default: 0 },
    isActive: { type: Boolean, default: true },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const { t } = useI18n();

const title = computed(() => (props.isActive ? t('Aktifkan Pengguna') : t('Nonaktifkan Pengguna')));
</script>

<template>
    <Modal :model-value="open" :title="title" @update:model-value="emit('cancel')">
        <p>
            <strong>{{ selectedCount }}</strong> {{ t('pengguna akan') }}
            {{ isActive ? t('diaktifkan') : t('dinonaktifkan') }}.
        </p>
        <p class="mt-2 text-sm text-base-content/60">
            {{ t('Perubahan status berlaku untuk semua pengguna yang dipilih pada halaman ini.') }}
        </p>

        <template #footer>
            <button
                type="button"
                class="btn btn-ghost"
                :disabled="processing"
                @click="emit('cancel')"
            >
                {{ t('Batal') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="isActive ? 'btn-success' : 'btn-error'"
                :disabled="processing"
                @click="emit('confirm')"
            >
                <span v-if="processing" class="loading loading-spinner loading-sm"></span>
                {{ processing ? t('Memproses...') : title }}
            </button>
        </template>
    </Modal>
</template>
