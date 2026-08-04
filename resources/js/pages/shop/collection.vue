<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
import { stripHtml } from '@/lib/format';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type { Collection, Product } from '@/types/shop';

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    collection: Collection;
    products: Paginated<Product>;
    filters: { sort: string };
}>();

const sort = ref<string>(props.filters.sort);

const description = computed(() =>
    props.collection.description
        ? stripHtml(props.collection.description)
        : '',
);

watch(sort, (value) => {
    router.get(
        shop.collection.url({ collection: props.collection.slug }),
        { sort: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head :title="collection.name" />

    <AppPageHeader
        class="lg:hidden"
        :title="collection.name"
        :back-href="home.url()"
        max-width-class="max-w-7xl"
    >
        <template #end>
            <label class="sr-only" for="collection-sort">Urutkan</label>
            <select
                id="collection-sort"
                v-model="sort"
                class="om-action-muted mr-1 max-w-[7.5rem] truncate bg-transparent pr-1 outline-none"
            >
                <option value="latest">Terbaru</option>
                <option value="name">Nama</option>
            </select>
        </template>
    </AppPageHeader>

    <Container class="pt-3 pb-8 lg:pt-6">
        <div class="mb-4 hidden items-end justify-between gap-4 lg:flex">
            <div class="min-w-0">
                <h1 class="om-page-title !text-lg">{{ collection.name }}</h1>
                <p v-if="description" class="om-meta mt-1 line-clamp-2">
                    {{ description }}
                </p>
            </div>
            <label class="sr-only" for="collection-sort-desktop">Urutkan</label>
            <select
                id="collection-sort-desktop"
                v-model="sort"
                class="om-action-muted shrink-0 bg-transparent outline-none"
            >
                <option value="latest">Terbaru</option>
                <option value="name">Nama</option>
            </select>
        </div>

        <p v-if="description" class="om-meta mb-4 line-clamp-2 lg:hidden">
            {{ description }}
        </p>

        <div
            v-if="!products.data.length"
            class="flex flex-col items-center py-16 text-center"
        >
            <Search class="size-10 text-zinc-300" aria-hidden="true" />
            <h3 class="om-page-title mt-3">Tidak ada produk</h3>
        </div>

        <template v-else>
            <div
                class="grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
            >
                <ProductCard
                    v-for="product in products.data"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <nav
                v-if="products.last_page > 1"
                class="mt-6 flex justify-center gap-1"
                aria-label="Halaman"
            >
                <Link
                    v-for="link in products.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-2.5 text-[13px]',
                        link.active
                            ? 'bg-[var(--om-navy)] text-white'
                            : 'text-zinc-600',
                        link.url === null && 'pointer-events-none opacity-40',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </template>
    </Container>
</template>
