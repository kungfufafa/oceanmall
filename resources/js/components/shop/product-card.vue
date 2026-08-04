<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PriceDisplay from '@/components/shop/price-display.vue';
import * as shop from '@/routes/shop';
import type { Product } from '@/types/shop';

const props = defineProps<{ product: Product }>();

const thumbnail = computed<string | null>(
    () => props.product.thumbnail ?? props.product.images?.[0]?.url ?? null,
);

const price = computed(() => props.product.prices?.[0] ?? null);

const percentage = computed<number | null>(() => {
    if (
        price.value?.amount == null ||
        !price.value?.compare_amount ||
        price.value.compare_amount <= price.value.amount
    ) {
        return null;
    }
    return Math.round(
        ((price.value.compare_amount - price.value.amount) /
            price.value.compare_amount) *
            100,
    );
});
</script>

<template>
    <div class="relative">
        <div class="relative aspect-square overflow-hidden rounded-md bg-zinc-100">
            <img
                v-if="thumbnail"
                :src="thumbnail"
                :alt="product.name"
                loading="lazy"
                class="size-full object-cover object-center"
            />
            <span
                v-if="percentage"
                class="absolute top-1.5 left-1.5 rounded bg-[#E11D48] px-1.5 py-0.5 text-[10px] font-bold text-white"
            >
                -{{ percentage }}%
            </span>
        </div>

        <h3 class="mt-2 line-clamp-2 text-[12px] leading-snug font-medium text-zinc-800">
            <Link :href="shop.product.url({ product: product.slug })">
                <span class="absolute inset-0" />
                {{ product.name }}
            </Link>
        </h3>

        <p v-if="product.brand" class="mt-0.5 text-[10px] text-zinc-500">
            {{ product.brand.name }}
        </p>

        <div class="mt-1">
            <PriceDisplay :price="price ?? null" size="sm" />
        </div>
    </div>
</template>
