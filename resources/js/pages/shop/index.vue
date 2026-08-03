<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import * as shop from '@/routes/shop';
import type { Category, Product } from '@/types/shop';

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Filters = {
    search: string;
    category: number | null;
    sort: string;
};

const props = defineProps<{
    products: Paginated<Product>;
    categories: Pick<Category, 'id' | 'name' | 'slug'>[];
    filters: Filters;
}>();

const search = ref<string>(props.filters.search);
const sort = ref<string>(props.filters.sort);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(sort, (value) => {
    router.get(
        shop.index.url(),
        { ...props.filters, sort: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            shop.index.url(),
            { ...props.filters, search: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

function filterByCategory(categoryId: number | null): void {
    router.get(
        shop.index.url(),
        { ...props.filters, category: categoryId },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Shop" />

    <Container class="py-8 sm:py-12">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
                >
                    Shop
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Browse our entire collection
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

        <div class="mt-8 lg:grid lg:grid-cols-4 lg:gap-x-8">
            <aside class="hidden lg:block">
                <div class="mb-6">
                    <label for="shop-search" class="sr-only">Search</label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                            aria-hidden="true"
                        />
                        <Input
                            id="shop-search"
                            v-model="search"
                            type="search"
                            placeholder="Search products..."
                            class="pl-9"
                        />
                    </div>
                </div>

                <div>
                    <h3
                        class="text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Categories
                    </h3>
                    <ul role="list" class="mt-3 space-y-2">
                        <li>
                            <button
                                type="button"
                                :class="[
                                    'text-sm transition',
                                    filters.category === null
                                        ? 'font-medium text-zinc-900 dark:text-white'
                                        : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white',
                                ]"
                                @click="filterByCategory(null)"
                            >
                                All
                            </button>
                        </li>
                        <li v-for="cat in categories" :key="cat.id">
                            <button
                                type="button"
                                :class="[
                                    'text-sm transition',
                                    filters.category === cat.id
                                        ? 'font-medium text-zinc-900 dark:text-white'
                                        : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white',
                                ]"
                                @click="filterByCategory(cat.id)"
                            >
                                {{ cat.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <div class="mb-6 lg:hidden">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Search products..."
                            class="pl-9"
                        />
                    </div>
                </div>

                <div
                    v-if="!products.data.length"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <Search
                        class="size-12 text-zinc-300 dark:text-zinc-600"
                        aria-hidden="true"
                    />
                    <h3
                        class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        No products found
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500">
                        Try adjusting your search or filters.
                    </p>
                </div>

                <template v-else>
                    <div
                        class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-6"
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
                                link.url === null &&
                                    'pointer-events-none opacity-40',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </template>
            </div>
        </div>
    </Container>
</template>
