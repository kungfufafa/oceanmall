<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search as SearchIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import PagePagination from '@/components/shop/page-pagination.vue';
import ProductCard from '@/components/shop/product-card.vue';
import SearchField from '@/components/shop/search-field.vue';
import { home } from '@/routes';
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
    <Head title="Cari" />

    <AppPageHeader
        class="lg:hidden"
        title="Cari"
        :back-href="home.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="py-6 sm:py-10">
        <h1 class="om-page-title hidden !text-lg lg:block">Cari</h1>
        <label class="sr-only" for="shop-search">Cari</label>
        <SearchField
            id="shop-search"
            v-model="search"
            placeholder="Cari produk..."
            class="mt-0 max-w-xl lg:mt-6"
        />

        <div class="mt-8">
            <EmptyState
                v-if="products === null"
                title="Mulai pencarian"
                description="Ketik minimal 2 karakter untuk mencari."
                :icon="SearchIcon"
                class="py-10"
            />

            <EmptyState
                v-else-if="!products.data.length"
                title="Tidak ada hasil"
                description="Coba kata kunci lain."
                :icon="SearchIcon"
            />

            <template v-else>
                <p class="om-meta mb-6">
                    {{ products.total }} hasil untuk "<span
                        class="font-medium text-foreground"
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

                <PagePagination
                    v-if="products.last_page > 1"
                    :links="products.links"
                    class="mt-8"
                />
            </template>
        </div>
    </Container>
</template>
