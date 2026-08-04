<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import { setForceLightMode } from '@/composables/useAppearance';
import { Toaster } from '@/components/ui/sonner';
import StoreBottomNav from '@/layouts/storefront/store-bottom-nav.vue';
import StoreFooter from '@/layouts/storefront/store-footer.vue';
import StoreHeader from '@/layouts/storefront/store-header.vue';

const page = usePage();

/**
 * Mobile app pages use AppPageHeader (back + title) instead of search chrome.
 * Desktop keeps the store search header so search never disappears mid-browse.
 */
const useAppPageChrome = computed(() => {
    const url = (page.url ?? '').split('?')[0];
    return (
        url.startsWith('/cart') ||
        url.startsWith('/checkout') ||
        url.startsWith('/dashboard') ||
        url.startsWith('/account') ||
        url.startsWith('/settings') ||
        url === '/shop' ||
        url.startsWith('/shop/') ||
        url.startsWith('/categories') ||
        url.startsWith('/category') ||
        url.startsWith('/collections') ||
        url.startsWith('/search')
    );
});

onMounted(() => {
    setForceLightMode(true);
});

onUnmounted(() => {
    setForceLightMode(false);
});
</script>

<template>
    <div
        class="storefront flex min-h-dvh flex-col bg-white font-sans text-zinc-900 antialiased"
        style="color-scheme: light"
    >
        <StoreHeader :class="useAppPageChrome ? 'hidden lg:block' : undefined" />

        <main
            class="flex-1 pb-[calc(var(--om-bottom-nav-height)+env(safe-area-inset-bottom,0px))] lg:pb-0"
        >
            <slot />
        </main>

        <div class="hidden lg:block">
            <StoreFooter />
        </div>

        <StoreBottomNav />

        <Toaster position="top-center" />
    </div>
</template>
