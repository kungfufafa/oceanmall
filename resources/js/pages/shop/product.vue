<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Minus, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import PriceDisplay from '@/components/shop/price-display.vue';
import ProductCard from '@/components/shop/product-card.vue';
import RatingStars from '@/components/shop/rating-stars.vue';
import { useCart } from '@/composables/useCart';
import * as shop from '@/routes/shop';
import * as productReviews from '@/routes/shop/product/reviews';
import type { Product, VariantOptions } from '@/types/shop';

type ReviewItem = {
    id: number;
    rating: number;
    title: string | null;
    content: string | null;
    is_recommended: boolean;
    created_at: string | null;
    author_name: string;
};

const props = defineProps<{
    product: Product;
    variantOptions: VariantOptions | null;
    reviews: {
        items: ReviewItem[];
        averageRating: number;
        totalCount: number;
        breakdown: Record<number, number>;
    };
    canReview: boolean;
}>();

const page = usePage();
const cart = useCart();

const selectedOptions = ref<Record<number, number>>({});
const quantity = ref<number>(1);
const adding = ref<boolean>(false);

const reviewForm = useForm({
    rating: 5,
    title: '',
    content: '',
    is_recommended: true,
});

const reviewError = computed(
    () => (page.props.errors as Record<string, string>)?.review,
);

const reviewSort = ref<'highest' | 'newest' | 'lowest'>('highest');

const sortedReviews = computed(() => {
    const items = [...props.reviews.items];

    if (reviewSort.value === 'newest') {
        return items.sort((a, b) => {
            const aTime = a.created_at ? Date.parse(a.created_at) : 0;
            const bTime = b.created_at ? Date.parse(b.created_at) : 0;
            return bTime - aTime;
        });
    }

    if (reviewSort.value === 'lowest') {
        return items.sort((a, b) => {
            if (a.rating !== b.rating) return a.rating - b.rating;
            const aTime = a.created_at ? Date.parse(a.created_at) : 0;
            const bTime = b.created_at ? Date.parse(b.created_at) : 0;
            return bTime - aTime;
        });
    }

    return items.sort((a, b) => {
        if (a.rating !== b.rating) return b.rating - a.rating;
        const aTime = a.created_at ? Date.parse(a.created_at) : 0;
        const bTime = b.created_at ? Date.parse(b.created_at) : 0;
        return bTime - aTime;
    });
});

const reviewSortOptions = [
    { value: 'highest' as const, label: 'Tertinggi' },
    { value: 'newest' as const, label: 'Terbaru' },
    { value: 'lowest' as const, label: 'Terendah' },
];

const ratingLevels = [5, 4, 3, 2, 1] as const;

function breakdownPercent(rating: number): number {
    if (props.reviews.totalCount <= 0) return 0;
    const count = props.reviews.breakdown?.[rating] ?? 0;
    return Math.round((count / props.reviews.totalCount) * 100);
}

function authorInitials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'P';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

function submitReview(): void {
    reviewForm.post(
        productReviews.store.url({ product: props.product.slug }),
        {
            preserveScroll: true,
            onSuccess: () => {
                reviewForm.reset('title', 'content');
                reviewForm.rating = 5;
            },
        },
    );
}

function formatReviewDate(value: string | null): string {
    if (!value) return '';
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatAverageRating(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(value);
}

const gallery = computed<string[]>(() => {
    const urls = [
        props.product.thumbnail,
        ...(props.product.images ?? []).map((image) => image.url),
    ].filter((url): url is string => Boolean(url) && !url.includes('placeholder'));

    return [...new Set(urls)];
});

const activeImage = ref<string | null>(gallery.value[0] ?? null);

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

const canAdd = computed(
    () =>
        !adding.value &&
        !outOfStock.value &&
        !(hasVariants.value && !selectedVariantId.value),
);

const ctaLabel = computed(() => {
    if (outOfStock.value) return 'Stok habis';
    if (adding.value) return 'Menambahkan…';
    if (hasVariants.value && !selectedVariantId.value) return 'Pilih opsi dulu';
    return 'Tambah ke keranjang';
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
    if (!canAdd.value) return;
    adding.value = true;
    cart.add({
        product_id: props.product.id,
        variant_id: selectedVariantId.value,
        quantity: quantity.value,
    });
    setTimeout(() => (adding.value = false), 600);
}
</script>

<template>
    <Head :title="product.name" />

    <AppPageHeader
        class="lg:hidden"
        title="Detail produk"
        :back-href="shop.index.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="pt-3 pb-4 lg:pt-6">
        <h1 class="om-page-title mb-4 hidden !text-lg lg:block">
            Detail produk
        </h1>

            <div class="lg:grid lg:grid-cols-2 lg:gap-x-10">
                <div>
                    <div
                        class="aspect-square overflow-hidden rounded-md bg-zinc-100"
                    >
                        <img
                            v-if="activeImage"
                            :src="activeImage"
                            :alt="product.name"
                            class="size-full object-contain object-center p-3"
                        />
                    </div>

                    <div
                        v-if="gallery.length > 1"
                        class="mt-2.5 grid grid-cols-4 gap-2"
                    >
                        <button
                            v-for="url in gallery"
                            :key="url"
                            type="button"
                            :class="[
                                'aspect-square overflow-hidden rounded-md bg-zinc-100 ring-2 ring-transparent',
                                activeImage === url &&
                                    'ring-[var(--om-navy)]',
                            ]"
                            @click="activeImage = url"
                        >
                            <img
                                :src="url"
                                alt=""
                                class="size-full object-contain object-center p-1"
                            />
                        </button>
                    </div>
                </div>

                <div class="mt-4 lg:mt-0">
                    <p
                        v-if="product.brand"
                        class="om-meta mb-1 !text-[11px] uppercase tracking-wide"
                    >
                        {{ product.brand.name }}
                    </p>
                    <h1 class="om-page-title !text-[17px] leading-snug">
                        {{ product.name }}
                    </h1>

                    <p
                        v-if="outOfStock"
                        class="mt-2 inline-flex rounded bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600"
                    >
                        Stok habis
                    </p>

                    <div class="mt-3">
                        <PriceDisplay :price="displayPrice ?? null" size="md" />
                    </div>

                    <div
                        v-if="hasVariants && variantOptions"
                        class="mt-5 space-y-4"
                    >
                        <div
                            v-for="option in variantOptions.productOptions"
                            :key="option.id"
                        >
                            <h3 class="om-label mb-2">{{ option.name }}</h3>
                            <div class="flex flex-wrap gap-2">
                                <template
                                    v-for="value in option.values"
                                    :key="value.id"
                                >
                                    <button
                                        v-if="option.type === 'colorpicker'"
                                        type="button"
                                        :class="[
                                            'size-8 rounded-full border-2',
                                            selectedOptions[option.id] ===
                                            value.id
                                                ? 'border-[var(--om-navy)]'
                                                : isOptionAvailable(
                                                        option.id,
                                                        value.id,
                                                    )
                                                  ? 'border-zinc-300'
                                                  : 'cursor-not-allowed border-zinc-200 opacity-30',
                                        ]"
                                        :style="
                                            value.key
                                                ? {
                                                      backgroundColor:
                                                          value.key,
                                                  }
                                                : undefined
                                        "
                                        :disabled="
                                            !isOptionAvailable(
                                                option.id,
                                                value.id,
                                            )
                                        "
                                        :title="value.value"
                                        @click="
                                            selectOption(option.id, value.id)
                                        "
                                    >
                                        <span class="sr-only">{{
                                            value.value
                                        }}</span>
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        :class="[
                                            'rounded-md border px-3 py-1.5 text-[13px] font-medium',
                                            selectedOptions[option.id] ===
                                            value.id
                                                ? 'border-[var(--om-navy)] bg-[var(--om-navy)] text-white'
                                                : isOptionAvailable(
                                                        option.id,
                                                        value.id,
                                                    )
                                                  ? 'border-zinc-300 text-zinc-900'
                                                  : 'cursor-not-allowed border-zinc-200 text-zinc-300',
                                        ]"
                                        :disabled="
                                            !isOptionAvailable(
                                                option.id,
                                                value.id,
                                            )
                                        "
                                        @click="
                                            selectOption(option.id, value.id)
                                        "
                                    >
                                        {{ value.value }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 hidden items-center gap-3 lg:flex">
                        <div
                            class="inline-flex h-11 items-center rounded-md border border-zinc-200"
                        >
                            <button
                                type="button"
                                class="flex size-11 items-center justify-center text-zinc-500 disabled:opacity-30"
                                :disabled="quantity <= 1"
                                aria-label="Kurangi"
                                @click="quantity = Math.max(1, quantity - 1)"
                            >
                                <Minus class="size-3.5" />
                            </button>
                            <span
                                class="min-w-8 text-center text-[13px] font-semibold"
                                >{{ quantity }}</span
                            >
                            <button
                                type="button"
                                class="flex size-11 items-center justify-center text-zinc-500"
                                aria-label="Tambah"
                                @click="quantity = Math.min(10, quantity + 1)"
                            >
                                <Plus class="size-3.5" />
                            </button>
                        </div>

                        <button
                            type="button"
                            class="om-btn-primary flex flex-1 items-center justify-center"
                            :disabled="!canAdd"
                            @click="addToCart"
                        >
                            {{ ctaLabel }}
                        </button>
                    </div>

                    <div
                        v-if="product.description"
                        class="mt-6 border-t border-zinc-100 pt-4"
                    >
                        <h3 class="om-label mb-2">Deskripsi</h3>
                        <div
                            class="prose prose-sm max-w-none prose-zinc text-[13px] leading-relaxed"
                            v-html="product.description"
                        />
                    </div>
                </div>
            </div>

            <section class="mt-8 border-t border-zinc-100 pt-5">
                <h2 class="om-page-title">Ulasan pembeli</h2>

                <div
                    v-if="reviews.totalCount > 0"
                    class="mt-3 grid gap-4 rounded-md border border-zinc-200 bg-zinc-50/70 p-3.5 sm:grid-cols-[7.5rem_1fr] sm:gap-5 sm:p-4"
                >
                    <div class="flex flex-col items-center justify-center text-center">
                        <p
                            class="text-[2rem] leading-none font-bold tracking-tight text-[var(--om-navy)]"
                        >
                            {{ formatAverageRating(reviews.averageRating) }}
                        </p>
                        <RatingStars
                            class="mt-2"
                            :value="reviews.averageRating"
                            size="md"
                        />
                        <p class="om-meta mt-1.5 !text-[11px]">
                            {{ reviews.totalCount }} ulasan
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <div
                            v-for="level in ratingLevels"
                            :key="level"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="w-3 shrink-0 text-right text-[11px] font-medium text-zinc-600"
                            >
                                {{ level }}
                            </span>
                            <div
                                class="h-1.5 flex-1 overflow-hidden rounded-sm bg-zinc-200"
                            >
                                <div
                                    class="h-full rounded-sm bg-[var(--om-navy)]"
                                    :style="{
                                        width: `${breakdownPercent(level)}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="w-8 shrink-0 text-right text-[11px] tabular-nums text-zinc-500"
                            >
                                {{ reviews.breakdown?.[level] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <p v-else class="om-meta mt-2">
                    Belum ada ulasan yang disetujui
                </p>

                <div
                    v-if="reviews.items.length"
                    class="mt-4 flex items-center justify-between gap-3 border-t border-zinc-100 pt-3"
                >
                    <p class="text-[12px] font-semibold text-zinc-800">
                        Semua ulasan
                    </p>
                    <div class="flex gap-1">
                        <button
                            v-for="option in reviewSortOptions"
                            :key="option.value"
                            type="button"
                            class="rounded-md px-2 py-1 text-[11px] font-semibold transition"
                            :class="
                                reviewSort === option.value
                                    ? 'bg-[var(--om-navy)] text-white'
                                    : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'
                            "
                            @click="reviewSort = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <ul
                    v-if="sortedReviews.length"
                    role="list"
                    class="mt-1 divide-y divide-zinc-100"
                >
                    <li
                        v-for="review in sortedReviews"
                        :key="review.id"
                        class="py-4"
                    >
                        <div class="flex gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-[#0a2a6b]/10 text-[11px] font-bold text-[var(--om-navy)]"
                                aria-hidden="true"
                            >
                                {{ authorInitials(review.author_name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                >
                                    <p
                                        class="text-[13px] font-semibold text-zinc-900"
                                    >
                                        {{ review.author_name }}
                                    </p>
                                    <span
                                        v-if="review.is_recommended"
                                        class="inline-flex items-center gap-0.5 text-[10px] font-medium text-emerald-700"
                                    >
                                        <BadgeCheck
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        Direkomendasikan
                                    </span>
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-2"
                                >
                                    <RatingStars :value="review.rating" />
                                    <span class="text-[11px] text-zinc-400">
                                        {{
                                            formatReviewDate(review.created_at)
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="review.title"
                                    class="mt-2 text-[13px] font-medium text-zinc-800"
                                >
                                    {{ review.title }}
                                </p>
                                <p
                                    v-if="review.content"
                                    class="mt-1 text-[13px] leading-relaxed text-zinc-600"
                                >
                                    {{ review.content }}
                                </p>
                            </div>
                        </div>
                    </li>
                </ul>

                <p
                    v-if="reviews.totalCount > reviews.items.length"
                    class="mt-2 text-center text-[11px] text-zinc-500"
                >
                    Menampilkan {{ reviews.items.length }} dari
                    {{ reviews.totalCount }} ulasan
                </p>

                <form
                    v-if="canReview"
                    class="mt-5 space-y-3 rounded-md border border-zinc-200 p-3.5 sm:p-4"
                    @submit.prevent="submitReview"
                >
                    <h3 class="text-[14px] font-semibold text-[var(--om-navy)]">
                        Tulis ulasan
                    </h3>
                    <p v-if="reviewError" class="text-[12px] text-red-600">
                        {{ reviewError }}
                    </p>
                    <div>
                        <p class="om-label mb-1.5">Rating</p>
                        <RatingStars
                            :value="reviewForm.rating"
                            size="lg"
                            interactive
                            @change="reviewForm.rating = $event"
                        />
                    </div>
                    <AuthTextField
                        id="review-title"
                        v-model="reviewForm.title"
                        label="Judul (opsional)"
                        :error="reviewForm.errors.title"
                    />
                    <div>
                        <label class="om-label" for="review-content"
                            >Ulasan</label
                        >
                        <textarea
                            id="review-content"
                            v-model="reviewForm.content"
                            rows="3"
                            class="om-control mt-1 w-full border border-zinc-200 bg-white px-3 py-2 text-[13px] outline-none focus:border-[var(--om-navy)]"
                            placeholder="Bagaimana pengalamanmu dengan produk ini?"
                        />
                        <p
                            v-if="reviewForm.errors.content"
                            class="mt-1 text-[12px] text-red-600"
                        >
                            {{ reviewForm.errors.content }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        class="om-btn-primary inline-flex items-center justify-center px-5 disabled:opacity-50"
                        :disabled="reviewForm.processing"
                    >
                        Kirim ulasan
                    </button>
                </form>
            </section>

            <section
                v-if="product.related_products?.length"
                class="mt-8 border-t border-zinc-100 pt-5"
            >
                <h2 class="om-page-title mb-3">Produk terkait</h2>
                <div
                    class="grid grid-cols-2 gap-x-2.5 gap-y-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                >
                    <ProductCard
                        v-for="related in product.related_products"
                        :key="related.id"
                        :product="related"
                    />
                </div>
            </section>
        </Container>
        <div
            class="fixed inset-x-0 z-40 border-t border-zinc-200 bg-white px-4 py-2.5 sm:px-6 lg:hidden"
            style="
                bottom: calc(
                    var(--om-bottom-nav-height) +
                        env(safe-area-inset-bottom, 0px)
                )
            "
        >
            <div class="mx-auto flex max-w-7xl items-center gap-2.5">
                <div
                    class="inline-flex h-11 items-center rounded-md border border-zinc-200"
                >
                    <button
                        type="button"
                        class="flex size-11 items-center justify-center text-zinc-500 disabled:opacity-30"
                        :disabled="quantity <= 1"
                        aria-label="Kurangi"
                        @click="quantity = Math.max(1, quantity - 1)"
                    >
                        <Minus class="size-3.5" />
                    </button>
                    <span
                        class="min-w-7 text-center text-[13px] font-semibold"
                        >{{ quantity }}</span
                    >
                    <button
                        type="button"
                        class="flex size-11 items-center justify-center text-zinc-500"
                        aria-label="Tambah"
                        @click="quantity = Math.min(10, quantity + 1)"
                    >
                        <Plus class="size-3.5" />
                    </button>
                </div>
                <button
                    type="button"
                    class="om-btn-primary flex min-w-0 flex-1 items-center justify-center px-3"
                    :disabled="!canAdd"
                    @click="addToCart"
                >
                    {{ ctaLabel }}
                </button>
            </div>
        </div>

        <div
            class="h-[calc(var(--om-control-height)+1.25rem)] lg:hidden"
            aria-hidden="true"
        />
</template>
