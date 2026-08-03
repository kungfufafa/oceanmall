<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Container from '@/components/shop/container.vue';
import * as shop from '@/routes/shop';
import type { Category } from '@/types/shop';

defineProps<{
    categories: Category[];
}>();

function productLabel(count: number): string {
    return count === 1 ? `${count} product` : `${count} products`;
}
</script>

<template>
    <Head title="Categories" />

    <Container class="py-8 sm:py-12">
        <div class="text-center">
            <h1
                class="font-heading text-3xl font-bold text-zinc-900 dark:text-white"
            >
                Categories
            </h1>
            <p class="mt-2 text-sm text-zinc-500">
                Browse products by category
            </p>
        </div>

        <div
            v-if="!categories.length"
            class="mt-16 flex flex-col items-center justify-center text-center"
        >
            <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                No categories found
            </h3>
        </div>

        <div
            v-else
            class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4"
        >
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="shop.category.url({ category: category.slug })"
                class="group relative overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800"
            >
                <div class="aspect-4/3">
                    <img
                        v-if="category.thumbnail"
                        :src="category.thumbnail"
                        :alt="category.name"
                        loading="lazy"
                        class="size-full object-cover object-center transition duration-500 group-hover:scale-105"
                    />
                    <div
                        class="absolute inset-0 bg-linear-to-t from-zinc-900/70 to-transparent"
                    />
                </div>
                <div class="absolute inset-x-0 bottom-0 p-4">
                    <h3 class="text-base font-semibold text-white">
                        {{ category.name }}
                    </h3>
                    <p
                        v-if="category.products_count !== undefined"
                        class="mt-0.5 text-xs text-zinc-300"
                    >
                        {{ productLabel(category.products_count) }}
                    </p>
                </div>
            </Link>
        </div>
    </Container>
</template>
