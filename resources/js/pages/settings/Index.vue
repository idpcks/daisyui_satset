<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { History, ShieldCheck, UserRound } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import AccountPanel from '@/components/settings/AccountPanel.vue';
import LoginHistoryPanel from '@/components/settings/LoginHistoryPanel.vue';
import OtherSessionsPanel from '@/components/settings/OtherSessionsPanel.vue';
import PasswordPanel from '@/components/settings/PasswordPanel.vue';
import SecurityPanel from '@/components/settings/SecurityPanel.vue';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

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
});

const tabs = [
    { key: 'akun', label: 'Akun', icon: UserRound },
    { key: 'keamanan', label: 'Keamanan', icon: ShieldCheck },
    { key: 'riwayat', label: 'Riwayat Login', icon: History },
];

const activeTab = ref(props.tab);

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
    <Head title="Pengaturan" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">Pengaturan</h1>
        <p class="text-sm text-base-content/60">Kelola data akun dan keamanan akun Anda sendiri.</p>
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
            <OtherSessionsPanel :sessions="sessions" />
        </div>

        <LoginHistoryPanel v-else :entries="loginHistory" />
    </div>
</template>
