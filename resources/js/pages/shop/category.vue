<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { stripHtml } from '@/lib/format';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type { Category, Product } from '@/types/shop';

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    category: Category;
    products: Paginated<Product>;
    filters: { sort: string };
}>();

const sort = ref<string>(props.filters.sort);

watch(sort, (value) => {
    router.get(
        shop.category.url({ category: props.category.slug }),
        { sort: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head :title="category.name" />

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
                :href="shop.categories.url()"
                class="transition hover:text-zinc-900 dark:hover:text-white"
                >Categories</Link
            >
            <span>/</span>
            <span class="text-zinc-900 dark:text-white">{{
                category.name
            }}</span>
        </nav>

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
                >
                    {{ category.name }}
                </h1>
                <p
                    v-if="category.description"
                    class="mt-1 text-sm text-zinc-600 dark:text-zinc-400"
                >
                    {{ stripHtml(category.description) }}
                </p>
            </div>

            <Select v-model="sort">
                <SelectTrigger class="w-auto" aria-label="Sort">
                    <SelectValue placeholder="Sort by" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="latest">Newest</SelectItem>
                    <SelectItem value="name">Name</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="!products.data.length"
            class="mt-16 flex flex-col items-center justify-center text-center"
        >
            <Search
                class="size-12 text-zinc-300 dark:text-zinc-600"
                aria-hidden="true"
            />
            <h3
                class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white"
            >
                No products in this category
            </h3>
        </div>

        <template v-else>
            <div
                class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6"
            >
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <nav
                v-if="products.last_page > 1"
                class="mt-8 flex justify-center gap-1"
                aria-label="Pagination"
            >
                <Link
                    v-for="link in products.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition',
                        link.active
                            ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                        link.url === null && 'pointer-events-none opacity-40',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </template>
    </Container>
</template>
