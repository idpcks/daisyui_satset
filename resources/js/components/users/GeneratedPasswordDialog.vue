<script setup>
import { computed } from 'vue';
import Modal from '@/components/ui/Modal.vue';
import { Check, Copy } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    open: { type: Boolean, default: false },
    password: { type: String, default: null },
    forUser: { type: String, default: null },
    magicLinkSent: { type: Boolean, default: false },
    magicLinkEnabled: { type: Boolean, default: false },
    acknowledged: { type: Boolean, default: false },
    copied: { type: Boolean, default: false },
});

const emit = defineEmits(['update:acknowledged', 'close', 'copy']);

const { t } = useI18n();

const closeable = computed(() => props.acknowledged);
</script>

<template>
    <Modal :model-value="open" :title="t('Password Sementara')" size="lg" :closeable="closeable" @update:model-value="emit('close')">
        <p v-if="forUser" class="mb-3 text-sm">
            {{ t('Password untuk') }} <strong>{{ forUser }}</strong>:
        </p>
        <div class="flex items-center gap-2">
            <code class="flex-1 rounded-box bg-base-200 px-3 py-2 font-mono text-sm">{{ password }}</code>
            <button type="button" class="btn btn-outline btn-sm" @click="emit('copy')">
                <Check v-if="copied" class="size-4" />
                <Copy v-else class="size-4" />
                {{ copied ? t('Tersalin') : t('Salin') }}
            </button>
        </div>
        <p class="mt-3 text-sm text-warning">
            {{ t('Password ini hanya ditampilkan sekali. Simpan di tempat aman dan serahkan ke pengguna.') }}
        </p>
        <p class="mt-2 text-sm" :class="magicLinkSent ? 'text-success' : (magicLinkEnabled ? 'text-error' : 'text-base-content/60')">
            {{
                magicLinkSent
                    ? t('Tautan login (magic link) juga telah dikirim ke email pengguna.')
                    : (magicLinkEnabled
                        ? t('Tautan login (magic link) gagal dikirim. Serahkan password ini secara manual.')
                        : t('Fitur magic link nonaktif. Serahkan password ini secara manual.'))
            }}
        </p>
        <p class="mt-2 text-sm text-info">
            {{ t('Email verifikasi tetap dikirim ke email pengguna.') }}
        </p>
        <label class="label cursor-pointer justify-start gap-2 mt-4">
            <input
                :checked="acknowledged"
                type="checkbox"
                class="checkbox checkbox-sm"
                @change="emit('update:acknowledged', $event.target.checked)"
            />
            <span class="text-sm">{{ t('Saya telah menyimpan password ini') }}</span>
        </label>

        <template #footer>
            <button type="button" class="btn btn-primary" :disabled="!acknowledged" @click="emit('close')">
                {{ t('Tutup') }}
            </button>
        </template>
    </Modal>
</template>
