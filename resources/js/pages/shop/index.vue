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
import SearchField from '@/components/shop/search-field.vue';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type { Brand, Category, Product } from '@/types/shop';

type Paginated<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Filters = {
    search: string;
    category: number | null;
    brand: number | null;
    sort: string;
};

const props = defineProps<{
    products: Paginated<Product>;
    categories: Pick<Category, 'id' | 'name' | 'slug'>[];
    brands: Pick<Brand, 'id' | 'name' | 'slug'>[];
    filters: Filters;
}>();

const search = ref<string>(props.filters.search);
const sort = ref<string>(props.filters.sort);
const brand = ref<string>(
    props.filters.brand !== null ? String(props.filters.brand) : '',
);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const hasActiveFilters = computed(
    () =>
        Boolean(props.filters.search) ||
        props.filters.category !== null ||
        props.filters.brand !== null,
);

watch(
    () => props.filters.brand,
    (value) => {
        brand.value = value !== null ? String(value) : '';
    },
);

watch(
    () => props.filters.sort,
    (value) => {
        sort.value = value;
    },
);

watch(
    () => props.filters.search,
    (value) => {
        search.value = value;
    },
);

watch(sort, (value) => {
    if (value === props.filters.sort) return;
    applyFilters({ sort: value });
});

watch(brand, (value) => {
    const next = value === '' ? null : Number(value);
    if (next === props.filters.brand) return;
    applyFilters({ brand: next });
});

watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        if (value === props.filters.search) return;
        applyFilters({ search: value });
    }, 300);
});

function applyFilters(patch: Partial<Filters>): void {
    const next = {
        search:
            patch.search !== undefined ? patch.search : props.filters.search,
        category:
            patch.category !== undefined
                ? patch.category
                : props.filters.category,
        brand: patch.brand !== undefined ? patch.brand : props.filters.brand,
        sort: patch.sort !== undefined ? patch.sort : props.filters.sort,
    };

    router.get(
        shop.index.url(),
        {
            search: next.search || undefined,
            category: next.category ?? undefined,
            brand: next.brand ?? undefined,
            sort: next.sort,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function filterByCategory(categoryId: number | null): void {
    applyFilters({ category: categoryId });
}

function clearFilters(): void {
    search.value = '';
    brand.value = '';
    router.get(
        shop.index.url(),
        { sort: props.filters.sort },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Belanja" />

    <AppPageHeader
        class="lg:hidden"
        title="Belanja"
        :back-href="home.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="pt-3 pb-8 lg:pt-6">
        <div class="mb-4 hidden lg:block">
            <h1 class="om-page-title !text-lg">Belanja</h1>
        </div>

        <SearchField v-model="search" placeholder="Cari di katalog…" />

        <!-- Categories: underline tabs, not chip soup -->
        <nav
            v-if="categories.length"
            class="mt-3 flex gap-4 overflow-x-auto border-b border-zinc-200 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            aria-label="Kategori"
        >
            <button
                type="button"
                class="-mb-px shrink-0 border-b-2 pb-2.5 text-[13px] font-semibold transition"
                :class="
                    filters.category === null
                        ? 'border-[var(--om-navy)] text-[var(--om-navy)]'
                        : 'border-transparent text-zinc-500 hover:text-zinc-800'
                "
                @click="filterByCategory(null)"
            >
                Semua
            </button>
            <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="-mb-px shrink-0 border-b-2 pb-2.5 text-[13px] font-semibold transition"
                :class="
                    filters.category === cat.id
                        ? 'border-[var(--om-navy)] text-[var(--om-navy)]'
                        : 'border-transparent text-zinc-500 hover:text-zinc-800'
                "
                @click="filterByCategory(cat.id)"
            >
                {{ cat.name }}
            </button>
        </nav>

        <!-- One compact toolbar: merek + sort + count -->
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <label class="sr-only" for="shop-brand">Merek</label>
            <FilterSelect id="shop-brand" v-model="brand">
                <option value="">Semua merek</option>
                <option
                    v-for="item in brands"
                    :key="item.id"
                    :value="String(item.id)"
                >
                    {{ item.name }}
                </option>
            </FilterSelect>

            <label class="sr-only" for="shop-sort">Urutkan</label>
            <FilterSelect id="shop-sort" v-model="sort">
                <option value="latest">Terbaru</option>
                <option value="name">Nama A–Z</option>
                <option value="price_asc">Harga terendah</option>
                <option value="price_desc">Harga tertinggi</option>
            </FilterSelect>

            <p class="ml-auto text-[12px] text-zinc-500">
                <span class="font-semibold text-zinc-800">{{
                    products.total
                }}</span>
                produk
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="ml-2 font-semibold text-[var(--om-navy)]"
                    @click="clearFilters"
                >
                    Reset
                </button>
            </p>
        </div>

        <EmptyState
            v-if="!products.data.length"
            title="Produk tidak ditemukan"
            description="Coba ubah pencarian atau filter."
            :icon="Search"
            :action-label="hasActiveFilters ? 'Reset filter' : undefined"
            @action="clearFilters"
        />

        <template v-else>
            <div
                class="mt-4 grid grid-cols-2 gap-x-2.5 gap-y-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
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
