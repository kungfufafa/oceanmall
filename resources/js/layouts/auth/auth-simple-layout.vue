<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import { setForceLightMode } from '@/composables/useAppearance';
import StoreBottomNav from '@/layouts/storefront/store-bottom-nav.vue';

defineProps<{
    title?: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
}>();

onMounted(() => {
    setForceLightMode(true);
});

onUnmounted(() => {
    setForceLightMode(false);
});
</script>

<template>
    <div
        class="storefront flex min-h-dvh flex-col bg-white font-sans text-foreground antialiased"
        style="color-scheme: light"
    >
        <AppPageHeader
            :title="title ?? ''"
            :end-label="actionLabel"
            :end-href="actionHref"
            end-tone="primary"
        />

        <main
            class="mx-auto w-full max-w-lg flex-1 px-4 pt-4 pb-[calc(var(--om-bottom-nav-height)+env(safe-area-inset-bottom,0px))]"
        >
            <p v-if="description" class="mb-4 text-sm leading-snug text-muted-foreground">
                {{ description }}
            </p>

            <slot />
        </main>

        <StoreBottomNav />
    </div>
</template>
