<script setup>
import { PanelLeft, PanelTop } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { useNavigationLayout } from '@/lib/useNavigationLayout';

const { layout, setLayout } = useNavigationLayout();
const { t } = useI18n();
</script>

<template>
    <div class="join hidden lg:inline-flex" role="group" :aria-label="t('Tampilan menu')">
        <button
            type="button"
            class="btn btn-sm join-item"
            :class="{ 'btn-active': layout === 'sidebar' }"
            :aria-pressed="layout === 'sidebar'"
            :aria-label="t('Gunakan menu vertikal')"
            :title="t('Menu vertikal')"
            @click="setLayout('sidebar')"
        >
            <PanelLeft class="size-4" aria-hidden="true" />
        </button>
        <button
            type="button"
            class="btn btn-sm join-item"
            :class="{ 'btn-active': layout === 'topbar' }"
            :aria-pressed="layout === 'topbar'"
            :aria-label="t('Gunakan menu horizontal')"
            :title="t('Menu horizontal')"
            @click="setLayout('topbar')"
        >
            <PanelTop class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
