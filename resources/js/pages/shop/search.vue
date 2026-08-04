<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search as SearchIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import ProductCard from '@/components/shop/product-card.vue';
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
        <div class="relative mt-0 max-w-xl lg:mt-6">
            <SearchIcon
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                aria-hidden="true"
            />
            <input
                v-model="search"
                type="search"
                placeholder="Cari produk..."
                autofocus
                aria-label="Cari"
                class="om-control w-full border border-zinc-200 bg-white pr-3 pl-9 text-zinc-900 outline-none placeholder:text-zinc-400 focus:border-[var(--om-navy)]"
            />
        </div>

        <div class="mt-8">
            <p v-if="products === null" class="om-meta text-center">
                Ketik minimal 2 karakter untuk mencari.
            </p>

            <div
                v-else-if="!products.data.length"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <SearchIcon
                    class="size-12 text-zinc-300"
                    aria-hidden="true"
                />
                <h3 class="mt-4 text-[13px] font-semibold text-zinc-900">
                    Tidak ada hasil
                </h3>
                <p class="om-meta mt-1">Coba kata kunci lain.</p>
            </div>

            <template v-else>
                <p class="om-meta mb-6">
                    {{ products.total }} hasil untuk "<span
                        class="font-medium text-zinc-900"
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
                    aria-label="Halaman"
                >
                    <Link
                        v-for="link in products.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-[13px] transition',
                            link.active
                                ? 'bg-[var(--om-navy)] text-white'
                                : 'text-zinc-600 hover:bg-zinc-100',
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
