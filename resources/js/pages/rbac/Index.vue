<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import RoleCard from '@/components/rbac/RoleCard.vue';

defineOptions({ layout: AppLayout, inheritAttrs: false });

defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="RBAC" />

    <div class="mb-4">
        <h1 class="text-xl font-semibold">RBAC</h1>
        <p class="text-sm text-base-content/60">
            Atur permission yang dimiliki setiap peran. Perubahan berlaku untuk semua pengguna dengan peran tersebut.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <RoleCard v-for="role in roles" :key="role.id" :role="role" :permissions="permissions" />
    </div>
</template>
