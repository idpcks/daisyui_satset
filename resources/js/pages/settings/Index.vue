<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Cog, History, ShieldCheck, UserRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import AccountPanel from '@/components/settings/AccountPanel.vue';
import LoginHistoryPanel from '@/components/settings/LoginHistoryPanel.vue';
import OtherSessionsPanel from '@/components/settings/OtherSessionsPanel.vue';
import PasskeysPanel from '@/components/settings/PasskeysPanel.vue';
import PasswordPanel from '@/components/settings/PasswordPanel.vue';
import SecurityPanel from '@/components/settings/SecurityPanel.vue';
import SystemPanel from '@/components/settings/SystemPanel.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useAuth } from '@/lib/useAuth';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: AppLayout, inheritAttrs: false });

const props = defineProps({
    tab: { type: String, default: 'akun' },
    account: { type: Object, required: true },
    twoFactorEnabled: { type: Boolean, default: false },
    twoFactorPending: { type: Boolean, default: false },
    qrCodeSvg: { type: String, default: null },
    secretKey: { type: String, default: null },
    recoveryCodes: { type: Array, default: () => [] },
    loginHistory: { type: Array, default: () => [] },
    sessions: { type: Array, default: () => [] },
    passkeys: { type: Array, default: () => [] },
    system: { type: Object, default: null },
    locales: { type: Array, default: () => [] },
    timezones: { type: Array, default: () => [] },
});

const { can } = useAuth();
const { t } = useI18n();

// Tab Sistem hanya untuk pemilik permission system.manage; server juga
// memvalidasi tab yang diminta sehingga URL tidak bisa memaksa akses.
const tabs = computed(() => [
    { key: 'akun', label: t('Akun'), icon: UserRound },
    { key: 'keamanan', label: t('Keamanan'), icon: ShieldCheck },
    { key: 'riwayat', label: t('Riwayat Login'), icon: History },
    ...(can('system.manage') ? [{ key: 'sistem', label: t('Sistem'), icon: Cog }] : []),
]);

const activeTab = ref(props.tab);

// Panel passkey hanya tampil bila flag aktif; rutenya juga digating di backend.
const passkeysEnabled = computed(() => usePage().props.features?.passkeys ?? false);

watch(
    () => props.tab,
    (tab) => (activeTab.value = tab),
);

// Tab disimpan di query string agar bisa di-bookmark dan tombol kembali
// browser tetap bekerja. preserveState menjaga isian form yang sedang diketik.
const selectTab = (key) => {
    if (key === activeTab.value) {
        return;
    }

    activeTab.value = key;

    router.get(
        appUrl(routes.settings),
        { tab: key },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head :title="t('Pengaturan')" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ t('Pengaturan') }}</h1>
        <p class="text-sm text-base-content/60">{{ t('Kelola data akun dan keamanan akun Anda sendiri.') }}</p>
    </div>

    <div role="tablist" class="tabs tabs-boxed w-fit">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            class="tab gap-2"
            :class="{ 'tab-active': activeTab === tab.key }"
            :aria-selected="activeTab === tab.key"
            @click="selectTab(tab.key)"
        >
            <component :is="tab.icon" class="size-4" />
            {{ tab.label }}
        </button>
    </div>

    <div class="mt-4">
        <div v-if="activeTab === 'akun'" class="space-y-4">
            <AccountPanel :account="account" />
            <PasswordPanel />
        </div>

        <div v-else-if="activeTab === 'keamanan'" class="space-y-4">
            <SecurityPanel
                :two-factor-enabled="twoFactorEnabled"
                :two-factor-pending="twoFactorPending"
                :qr-code-svg="qrCodeSvg"
                :secret-key="secretKey"
                :recovery-codes="recoveryCodes"
            />
            <PasskeysPanel v-if="passkeysEnabled" :passkeys="passkeys" />
            <OtherSessionsPanel :sessions="sessions" />
        </div>

        <LoginHistoryPanel v-else-if="activeTab === 'riwayat'" :entries="loginHistory" />

        <SystemPanel
            v-else-if="activeTab === 'sistem' && system"
            :settings="system"
            :locales="locales"
            :timezones="timezones"
        />
    </div>
</template>
