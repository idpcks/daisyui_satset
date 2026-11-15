<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from 'lucide-vue-next';
import { useToast } from '@/lib/useToast';

const page = usePage();
const { toasts, success, error, dismiss } = useToast();

// Flash dari Laravel (session) otomatis diteruskan ke toast.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            success(flash.success);
        }

        if (flash?.error) {
            error(flash.error);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="toast toast-top toast-end z-50 pointer-events-none" aria-live="polite">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="alert pointer-events-auto max-w-sm shadow-lg"
                :class="toast.type === 'success' ? 'alert-success' : 'alert-error'"
                :role="toast.type === 'error' ? 'alert' : 'status'"
            >
                <CircleCheck v-if="toast.type === 'success'" class="size-5 shrink-0" />
                <CircleAlert v-else class="size-5 shrink-0" />

                <span class="text-sm">{{ toast.message }}</span>

                <button
                    type="button"
                    class="btn btn-ghost btn-xs btn-square"
                    aria-label="Tutup notifikasi"
                    @click="dismiss(toast.id)"
                >
                    <X class="size-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(1rem);
}
</style>
