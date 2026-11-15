<script setup>
import { onMounted, ref, useId, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue']);

const dialog = ref(null);
const titleId = useId();

const syncDialog = (open) => {
    const element = dialog.value;

    if (!element) {
        return;
    }

    if (open && !element.open) {
        element.showModal();
    }

    if (!open && element.open) {
        element.close();
    }
};

const close = () => emit('update:modelValue', false);

// `closedby="none"` menahan Escape di browser modern (event cancel di sana
// tidak bisa di-preventDefault), preventDefault jadi cadangan browser lain.
const onCancel = (event) => {
    if (props.closeable) {
        return;
    }

    event.preventDefault();
};

// Klik backdrop punya padanan keyboard: Escape. Dialog native sudah menutup
// sendiri lewat event `cancel`; handler ini menjaga perilaku yang sama saat
// `closedby` belum didukung browser.
const onEscape = () => {
    if (props.closeable) {
        close();
    }
};

const onClose = () => {
    if (props.modelValue) {
        close();
    }
};

// Klik pada area backdrop menghasilkan event dengan target dialog itu sendiri.
const onBackdropClick = (event) => {
    if (props.closeable && event.target === dialog.value) {
        close();
    }
};

watch(() => props.modelValue, syncDialog);
onMounted(() => syncDialog(props.modelValue));
</script>

<template>
    <dialog
        ref="dialog"
        class="modal"
        :closedby="closeable ? null : 'none'"
        :aria-labelledby="title ? titleId : undefined"
        @cancel="onCancel"
        @close="onClose"
        @click="onBackdropClick"
        @keydown.esc="onEscape"
    >
        <div class="modal-box" :class="{ 'modal-box-wide': size === 'lg' }">
            <button
                v-if="closeable"
                type="button"
                class="btn btn-ghost btn-sm btn-circle absolute top-2 right-2"
                aria-label="Tutup dialog"
                title="Tutup dialog"
                @click="close"
            >
                <X class="size-4" />
            </button>

            <h2 v-if="title" :id="titleId" class="mb-4 pr-8 text-lg font-semibold">{{ title }}</h2>

            <slot />

            <div v-if="$slots.footer" class="modal-action">
                <slot name="footer" />
            </div>
        </div>
    </dialog>
</template>

<style scoped>
/* Form dua kolom butuh ruang lebih dari max-width bawaan modal-box. */
.modal-box-wide {
    max-width: 48rem;
}

/* Animasi buka/tutup berasal dari transisi .modal dan .modal-box daisyUI. */
@media (prefers-reduced-motion: reduce) {
    .modal,
    .modal-box {
        transition: none;
    }
}
</style>
