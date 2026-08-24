<script setup lang="ts">
import AuthLayout from '@/layouts/auth/auth-simple-layout.vue';

const {
    title = '',
    description = '',
    actionLabel = '',
    actionHref = '',
} = defineProps<{
    title?: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
}>();
</script>

<template>
    <AuthLayout
        :title="title"
        :description="description"
        :action-label="actionLabel"
        :action-href="actionHref"
    >
        <slot />
    </AuthLayout>
</template>
