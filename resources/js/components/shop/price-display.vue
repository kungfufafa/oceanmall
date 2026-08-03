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
        lg: 'text-2xl',
        md: 'text-lg',
        sm: 'text-sm',
    }[props.size];
});

const percentage = computed<number | null>(() => {
    if (
        !props.price?.compare_amount ||
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
        <template v-if="price">
            <p class="flex items-center gap-2">
                <span class="font-semibold text-zinc-900 dark:text-white">{{
                    formatMoney(price.amount, currency)
                }}</span>
                <span v-if="taxLabel" class="text-xs text-zinc-500">{{
                    taxLabel
                }}</span>
            </p>

            <p
                v-if="percentage"
                class="mt-0.5 flex items-center gap-1.5 sm:mt-0 sm:inline-flex"
            >
                <span class="sr-only">Original:</span>
                <span class="text-zinc-400 line-through">{{
                    formatMoney(price.compare_amount ?? 0, currency)
                }}</span>
                <span
                    class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                >
                    -{{ percentage }}%
                </span>
            </p>
        </template>
        <p v-else class="font-semibold text-zinc-900 dark:text-white">
            Price unavailable
        </p>
    </div>
</template>
