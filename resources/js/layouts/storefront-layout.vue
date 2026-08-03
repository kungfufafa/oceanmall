<script setup lang="ts">
import StoreFooter from '@/layouts/storefront/store-footer.vue';
import StoreHeader from '@/layouts/storefront/store-header.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-white font-sans antialiased dark:bg-zinc-900"
    >
        <StoreHeader />

        <main class="flex-1">
            <slot />
        </main>

        <StoreFooter />
    </div>
</template>
