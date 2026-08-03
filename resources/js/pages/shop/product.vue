<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Minus, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Container from '@/components/shop/container.vue';
import PriceDisplay from '@/components/shop/price-display.vue';
import ProductCard from '@/components/shop/product-card.vue';
import { Button } from '@/components/ui/button';
import { useCart } from '@/composables/useCart';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type { Product, VariantOptions } from '@/types/shop';

const props = defineProps<{
    product: Product;
    variantOptions: VariantOptions | null;
}>();

const cart = useCart();

const selectedOptions = ref<Record<number, number>>({});
const quantity = ref<number>(1);
const adding = ref<boolean>(false);

const gallery = computed<string[]>(() =>
    (props.product.images ?? []).map((image) => image.url),
);

const activeImage = ref<string | null>(
    props.product.thumbnail ?? gallery.value[0] ?? null,
);

const hasVariants = computed<boolean>(() =>
    Boolean(props.variantOptions?.hasStructuredAttributes),
);

const selectedVariantId = computed<number | null>(() => {
    if (!props.variantOptions || !hasVariants.value) return null;
    const required = props.variantOptions.productOptions.length;
    if (Object.keys(selectedOptions.value).length !== required) return null;
    const key = Object.values(selectedOptions.value)
        .sort((a, b) => a - b)
        .join('-');
    return props.variantOptions.variantIndex[key] ?? null;
});

const selectedVariant = computed(() =>
    selectedVariantId.value && props.product.variants
        ? (props.product.variants.find(
              (v) => v.id === selectedVariantId.value,
          ) ?? null)
        : null,
);

const displayPrice = computed(
    () => selectedVariant.value?.prices?.[0] ?? props.product.prices?.[0],
);

const outOfStock = computed<boolean>(() => {
    if (hasVariants.value) {
        if (selectedVariant.value) {
            return (
                selectedVariant.value.stock <= 0 &&
                !selectedVariant.value.allow_backorder
            );
        }
        return (props.product.variants ?? []).every(
            (variant) => variant.stock <= 0 && !variant.allow_backorder,
        );
    }
    const stock = (props.product as { stock?: number }).stock ?? 0;
    return stock <= 0 && !props.product.allow_backorder;
});

function isOptionAvailable(attributeId: number, valueId: number): boolean {
    return (
        props.variantOptions?.availabilityMatrix[attributeId]?.[valueId] ?? true
    );
}

function selectOption(optionId: number, valueId: number): void {
    selectedOptions.value = { ...selectedOptions.value, [optionId]: valueId };
}

function addToCart(): void {
    if (adding.value || outOfStock.value) return;
    adding.value = true;
    cart.add({
        product_id: props.product.id,
        variant_id: selectedVariantId.value,
        quantity: quantity.value,
    });
    setTimeout(() => (adding.value = false), 800);
}
</script>

<template>
    <Head :title="product.name" />

    <Container class="py-8 sm:py-12">
        <nav
            class="mb-8 flex items-center gap-2 text-sm text-zinc-500"
            aria-label="Breadcrumb"
        >
            <Link
                :href="home.url()"
                class="transition hover:text-zinc-900 dark:hover:text-white"
                >Home</Link
            >
            <span>/</span>
            <Link
                :href="shop.index.url()"
                class="transition hover:text-zinc-900 dark:hover:text-white"
                >Shop</Link
            >
            <span>/</span>
            <span class="text-zinc-900 dark:text-white">{{
                product.name
            }}</span>
        </nav>

        <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
            <div>
                <div
                    class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800"
                >
                    <img
                        v-if="activeImage"
                        :src="activeImage"
                        :alt="product.name"
                        class="size-full object-cover object-center"
                    />
                </div>

                <div
                    v-if="gallery.length"
                    class="mt-4 grid grid-cols-4 gap-3"
                >
                    <button
                        v-for="url in gallery"
                        :key="url"
                        type="button"
                        :class="[
                            'aspect-square overflow-hidden rounded-lg bg-zinc-100 ring-2 ring-transparent focus:ring-zinc-900 dark:bg-zinc-800 dark:focus:ring-white',
                            activeImage === url &&
                                'ring-zinc-900 dark:ring-white',
                        ]"
                        @click="activeImage = url"
                    >
                        <img
                            :src="url"
                            alt=""
                            class="size-full object-cover object-center"
                        />
                    </button>
                </div>
            </div>

            <div class="mt-8 lg:mt-0">
                <h1
                    class="font-heading text-3xl font-bold text-zinc-900 dark:text-white"
                >
                    {{ product.name }}
                </h1>

                <p
                    v-if="outOfStock"
                    class="mt-2 inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20"
                >
                    Out of stock
                </p>

                <div class="mt-4">
                    <PriceDisplay :price="displayPrice ?? null" size="lg" />
                </div>

                <div
                    v-if="hasVariants && variantOptions"
                    class="mt-6 space-y-5"
                >
                    <div
                        v-for="option in variantOptions.productOptions"
                        :key="option.id"
                    >
                        <h3
                            class="text-sm font-medium text-zinc-900 dark:text-white"
                        >
                            {{ option.name }}
                        </h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template
                                v-for="value in option.values"
                                :key="value.id"
                            >
                                <button
                                    v-if="option.type === 'colorpicker'"
                                    type="button"
                                    :class="[
                                        'size-8 rounded-full border-2 transition',
                                        selectedOptions[option.id] === value.id
                                            ? 'border-zinc-900 ring-2 ring-zinc-900 ring-offset-2 dark:border-white dark:ring-white'
                                            : isOptionAvailable(
                                                    option.id,
                                                    value.id,
                                                )
                                              ? 'border-zinc-300 hover:border-zinc-500 dark:border-zinc-600'
                                              : 'cursor-not-allowed border-zinc-200 opacity-30 dark:border-zinc-700',
                                    ]"
                                    :style="
                                        value.key
                                            ? { backgroundColor: value.key }
                                            : undefined
                                    "
                                    :disabled="
                                        !isOptionAvailable(option.id, value.id)
                                    "
                                    :title="value.value"
                                    @click="selectOption(option.id, value.id)"
                                >
                                    <span class="sr-only">{{
                                        value.value
                                    }}</span>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    :class="[
                                        'rounded-lg border px-4 py-2 text-sm font-medium transition',
                                        selectedOptions[option.id] === value.id
                                            ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900'
                                            : isOptionAvailable(
                                                    option.id,
                                                    value.id,
                                                )
                                              ? 'border-zinc-300 text-zinc-900 hover:border-zinc-500 dark:border-zinc-600 dark:text-white dark:hover:border-zinc-400'
                                              : 'cursor-not-allowed border-zinc-200 text-zinc-300 dark:border-zinc-700 dark:text-zinc-600',
                                    ]"
                                    :disabled="
                                        !isOptionAvailable(option.id, value.id)
                                    "
                                    @click="selectOption(option.id, value.id)"
                                >
                                    {{ value.value }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-4">
                    <div
                        class="flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600"
                    >
                        <button
                            type="button"
                            class="px-3 py-2 text-zinc-500 transition hover:text-zinc-900 disabled:opacity-40 dark:hover:text-white"
                            :disabled="quantity <= 1"
                            aria-label="Decrease"
                            @click="quantity = Math.max(1, quantity - 1)"
                        >
                            <Minus class="size-4" aria-hidden="true" />
                        </button>
                        <span
                            class="min-w-8 text-center text-sm font-medium text-zinc-900 dark:text-white"
                            >{{ quantity }}</span
                        >
                        <button
                            type="button"
                            class="px-3 py-2 text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                            aria-label="Increase"
                            @click="quantity = Math.min(10, quantity + 1)"
                        >
                            <Plus class="size-4" aria-hidden="true" />
                        </button>
                    </div>

                    <Button
                        type="button"
                        class="flex-1"
                        :disabled="
                            adding ||
                            outOfStock ||
                            (hasVariants && !selectedVariantId)
                        "
                        @click="addToCart"
                    >
                        {{
                            outOfStock
                                ? 'Out of stock'
                                : adding
                                  ? 'Adding...'
                                  : 'Add to Cart'
                        }}
                    </Button>
                </div>

                <div
                    v-if="product.description"
                    class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700"
                >
                    <h3
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        Description
                    </h3>
                    <div
                        class="prose prose-sm mt-3 max-w-none prose-zinc dark:prose-invert"
                        v-html="product.description"
                    />
                </div>
            </div>
        </div>

        <section
            v-if="product.related_products?.length"
            class="mt-16 border-t border-zinc-200 pt-12 dark:border-zinc-700"
        >
            <h2
                class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
            >
                Related Products
            </h2>
            <div
                class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6"
            >
                <ProductCard
                    v-for="related in product.related_products"
                    :key="related.id"
                    :product="related"
                />
            </div>
        </section>
    </Container>
</template>
