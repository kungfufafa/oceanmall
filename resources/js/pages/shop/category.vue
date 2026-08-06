<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import FilterSelect from '@/components/shop/filter-select.vue';
import PagePagination from '@/components/shop/page-pagination.vue';
import ProductCard from '@/components/shop/product-card.vue';
import { stripHtml } from '@/lib/format';
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

const sortOptions = [
    { value: 'latest', label: 'Terbaru' },
    { value: 'name', label: 'Nama' },
];

const description = computed(() =>
    props.category.description ? stripHtml(props.category.description) : '',
);

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

    <AppPageHeader
        class="lg:hidden"
        :title="category.name"
        :back-href="shop.categories.url()"
        max-width-class="max-w-7xl"
    >
        <template #end>
            <label class="sr-only" for="category-sort">Urutkan</label>
            <FilterSelect
                id="category-sort"
                v-model="sort"
                :options="sortOptions"
                placeholder="Urutkan"
                class="mr-1 max-w-[7.5rem] truncate"
            />
        </template>
    </AppPageHeader>

    <Container class="pt-3 pb-8 lg:pt-6">
        <div class="mb-4 hidden items-end justify-between gap-4 lg:flex">
            <div class="min-w-0">
                <h1
                    class="text-lg font-semibold tracking-tight text-foreground"
                >
                    {{ category.name }}
                </h1>
                <p
                    v-if="description"
                    class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <label class="sr-only" for="category-sort-desktop">Urutkan</label>
            <FilterSelect
                id="category-sort-desktop"
                v-model="sort"
                :options="sortOptions"
                placeholder="Urutkan"
                class="shrink-0"
            />
        </div>

        <p
            v-if="description"
            class="mb-4 line-clamp-2 text-sm text-muted-foreground lg:hidden"
        >
            {{ description }}
        </p>

        <EmptyState
            v-if="!products.data.length"
            title="Tidak ada produk"
            :icon="Search"
        />

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

            <PagePagination
                v-if="products.last_page > 1"
                :links="products.links"
            />
        </template>
    </Container>
</template>
