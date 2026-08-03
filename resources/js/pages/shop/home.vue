<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import CategoryCard from '@/components/shop/category-card.vue';
import CollectionBanner from '@/components/shop/collection-banner.vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
import TrustBadges from '@/components/shop/trust-badges.vue';
import * as shop from '@/routes/shop';
import type { Category, Collection, Product } from '@/types/shop';

defineProps<{
    featuredProducts: Product[];
    featuredCollections: Collection[];
    categories: Category[];
}>();
</script>

<template>
    <Head title="Home" />

    <section class="relative overflow-hidden bg-zinc-100 dark:bg-zinc-950">
        <Container>
            <div class="relative py-20 sm:py-28 lg:py-36">
                <div class="max-w-xl">
                    <h1
                        class="font-heading text-5xl font-extrabold tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl dark:text-white"
                    >
                        Explore Premium Products
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        Discover our curated collection of high-quality
                        products, handpicked just for you.
                    </p>
                    <div class="mt-8 flex items-center gap-4">
                        <Link
                            :href="shop.index.url()"
                            class="inline-flex items-center gap-1.5 rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                        >
                            Shop Now
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                        <Link
                            :href="shop.categories.url()"
                            class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-transparent dark:text-white dark:hover:bg-zinc-800"
                        >
                            Categories
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </div>
        </Container>
    </section>

    <Container class="py-10 sm:py-12">
        <TrustBadges />
    </Container>

    <section v-if="featuredCollections.length" class="py-12 sm:py-16 lg:pb-24">
        <Container>
            <div class="flex items-center justify-between">
                <h2
                    class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
                >
                    Shop by Collections
                </h2>
            </div>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <CollectionBanner
                    v-for="collection in featuredCollections"
                    :key="collection.id"
                    :collection="collection"
                />
            </div>
        </Container>
    </section>

    <section
        v-if="featuredProducts.length"
        class="bg-zinc-50 py-12 sm:py-16 lg:py-20 dark:bg-zinc-900/50"
    >
        <Container>
            <div class="flex items-center justify-between">
                <h2
                    class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
                >
                    Featured Products
                </h2>
                <Link
                    :href="shop.index.url()"
                    class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                >
                    View all <span aria-hidden="true">→</span>
                </Link>
            </div>
            <div
                class="mt-10 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6"
            >
                <ProductCard
                    v-for="product in featuredProducts"
                    :key="product.id"
                    :product="product"
                />
            </div>
        </Container>
    </section>

    <section v-if="categories.length" class="py-12 sm:py-16 lg:py-20">
        <Container>
            <div class="text-center">
                <h2
                    class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
                >
                    Shop by Category
                </h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Browse our wide range of categories
                </p>
            </div>
            <div
                class="mt-8 grid grid-cols-3 gap-6 sm:grid-cols-4 lg:grid-cols-8"
            >
                <CategoryCard
                    v-for="category in categories"
                    :key="category.id"
                    :category="category"
                />
            </div>
        </Container>
    </section>
</template>
