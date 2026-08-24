<script setup lang="ts">
import { computed } from 'vue';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';
import type { ProductPrice } from '@/types/shop';

type Size = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        price: ProductPrice | null;
        size?: Size;
    }>(),
    { size: 'sm' },
);

const { currency, taxLabel } = useShop();

const textSize = computed<string>(() => {
    return {
        lg: 'text-xl',
        md: 'text-base',
        sm: 'text-[13px]',
    }[props.size];
});

const percentage = computed<number | null>(() => {
    if (
        !props.price ||
        props.price.amount === null ||
        !props.price.compare_amount ||
        props.price.compare_amount <= props.price.amount
    ) {
        return null;
    }

    return Math.round(
        ((props.price.compare_amount - props.price.amount) /
            props.price.compare_amount) *
            100,
    );
});
</script>

<template>
    <div :class="textSize">
        <template v-if="price && price.amount !== null">
            <p class="flex items-center gap-2">
                <span class="storefront-price">{{
                    formatMoney(price.amount, currency)
                }}</span>
                <span v-if="taxLabel" class="text-xs text-muted-foreground">{{
                    taxLabel
                }}</span>
            </p>

            <p
                v-if="percentage"
                class="mt-0.5 text-[11px] text-muted-foreground line-through"
            >
                <span class="sr-only">Harga asli:</span>
                {{ formatMoney(price.compare_amount ?? 0, currency) }}
            </p>
        </template>
        <p v-else class="text-[13px] font-semibold text-muted-foreground">
            Harga belum tersedia
        </p>
    </div>
</template>
