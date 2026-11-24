<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import LoginForm from '@/components/auth/LoginForm.vue';
import { useI18n } from '@/lib/i18n';

defineOptions({ layout: GuestLayout });

const { t } = useI18n();
</script>

<template>
    <Head :title="t('Masuk')" />

    <LoginForm />
</template>
