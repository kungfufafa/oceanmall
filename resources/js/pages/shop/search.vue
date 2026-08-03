<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search as SearchIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
import { Input } from '@/components/ui/input';
import { search as searchRoute } from '@/routes/shop';
import type { Product } from '@/types/shop';

type Paginated<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    query: string;
    products: Paginated<Product> | null;
}>();

const search = ref<string>(props.query);
let debounceId: number | undefined;

watch(search, (value) => {
    window.clearTimeout(debounceId);
    debounceId = window.setTimeout(() => {
        router.get(
            searchRoute.url(),
            { q: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Search" />

    <Container class="py-8 sm:py-12">
        <div class="mx-auto max-w-xl text-center">
            <h1
                class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
            >
                Search
            </h1>
            <div class="relative mt-6">
                <SearchIcon
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search for products..."
                    autofocus
                    aria-label="Search"
                    class="pl-9"
                />
            </div>
        </div>

        <div class="mt-10">
            <p
                v-if="products === null"
                class="text-center text-sm text-zinc-500"
            >
                Type at least 2 characters to search.
            </p>

            <div
                v-else-if="!products.data.length"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <SearchIcon
                    class="size-12 text-zinc-300 dark:text-zinc-600"
                    aria-hidden="true"
                />
                <h3
                    class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white"
                >
                    No results found
                </h3>
                <p class="mt-1 text-sm text-zinc-500">
                    Try a different search term.
                </p>
            </div>

            <template v-else>
                <p class="mb-6 text-sm text-zinc-500">
                    {{ products.total }}
                    {{ products.total === 1 ? 'result' : 'results' }} for "<span
                        class="font-medium text-zinc-900 dark:text-white"
                        >{{ query }}</span
                    >"
                </p>

                <div
                    class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6"
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
    </Container>
</template>
