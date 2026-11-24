<script setup>
import { LayoutPanelLeft, LayoutPanelTop, PanelLeft, PanelTop } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { useNavigationLayout } from '@/lib/useNavigationLayout';

const { layout, setLayout } = useNavigationLayout();
const { t } = useI18n();
</script>

<template>
    <!-- FAB DaisyUI bawaan: flower speed dial di sudut kanan bawah. Tooltip
         dipakai karena susunan quarter-circle tidak menyediakan ruang label. -->
    <div
        class="fab fab-flower bottom-20 right-4 z-[999] lg:bottom-4"
        role="group"
        :aria-label="t('Tampilan menu')"
    >
        <div
            tabindex="0"
            role="button"
            class="btn btn-lg btn-circle btn-primary"
            :aria-label="t('Tampilan menu')"
            :title="t('Tampilan menu')"
        >
            <LayoutPanelLeft v-if="layout === 'sidebar'" class="size-6" aria-hidden="true" />
            <LayoutPanelTop v-else class="size-6" aria-hidden="true" />
        </div>

        <div class="tooltip tooltip-left" :data-tip="t('Menu vertikal')">
            <button
                type="button"
                class="btn btn-lg btn-circle"
                :class="{ 'btn-primary': layout === 'sidebar' }"
                :aria-pressed="layout === 'sidebar'"
                :aria-label="t('Gunakan menu vertikal')"
                :title="t('Menu vertikal')"
                @click="setLayout('sidebar')"
            >
                <PanelLeft class="size-5" aria-hidden="true" />
            </button>
        </div>

        <div class="tooltip tooltip-left" :data-tip="t('Menu horizontal')">
            <button
                type="button"
                class="btn btn-lg btn-circle"
                :class="{ 'btn-primary': layout === 'topbar' }"
                :aria-pressed="layout === 'topbar'"
                :aria-label="t('Gunakan menu horizontal')"
                :title="t('Menu horizontal')"
                @click="setLayout('topbar')"
            >
                <PanelTop class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
