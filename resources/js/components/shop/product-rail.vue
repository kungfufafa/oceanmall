<script setup lang="ts">
import ProductCard from '@/components/shop/product-card.vue';
import SectionHeader from '@/components/shop/section-header.vue';
import type { Product } from '@/types/shop';

withDefaults(
    defineProps<{
        title: string;
        href: string;
        products: Product[];
        linkLabel?: string;
        tone?: 'plain' | 'soft';
    }>(),
    {
        linkLabel: 'Semua',
        tone: 'plain',
    },
);
</script>

<template>
    <section
        v-if="products.length"
        :class="[
            'mt-5',
            tone === 'soft' && 'bg-muted/50 py-4',
        ]"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <SectionHeader
                class="mb-3"
                :title="title"
                :action-href="href"
                :action-label="linkLabel"
            />

            <div
                class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none] snap-x snap-mandatory sm:-mx-6 sm:px-6 [&::-webkit-scrollbar]:hidden"
            >
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="w-[9.5rem] shrink-0 snap-start sm:w-[10.5rem]"
                >
                    <ProductCard :product="product" />
                </div>
            </div>
        </div>
    </section>
</template>
