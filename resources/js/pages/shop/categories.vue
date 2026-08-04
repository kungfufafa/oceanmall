<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type { Category } from '@/types/shop';

defineProps<{
    categories: Category[];
}>();

function productLabel(count: number): string {
    return `${count} produk`;
}
</script>

<template>
    <Head title="Kategori" />

    <AppPageHeader
        class="lg:hidden"
        title="Kategori"
        :back-href="home.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="pt-4 pb-8 lg:pt-6">
        <h1 class="om-page-title mb-1 hidden !text-lg lg:block">Kategori</h1>
        <p class="om-meta mb-4">Jelajahi produk berdasarkan kategori</p>

        <EmptyState
            v-if="!categories.length"
            title="Tidak ada kategori"
            :icon="Smartphone"
        />

        <div
            v-else
            class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
        >
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="shop.category.url({ category: category.slug })"
                class="flex flex-col overflow-hidden rounded-md border border-zinc-200 bg-white"
            >
                <div
                    class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-zinc-100"
                >
                    <img
                        v-if="category.thumbnail"
                        :src="category.thumbnail"
                        :alt="category.name"
                        loading="lazy"
                        class="size-full object-cover object-center"
                    />
                    <Smartphone
                        v-else
                        class="size-8 text-[var(--om-navy)]"
                        aria-hidden="true"
                    />
                </div>
                <div class="px-2.5 py-2">
                    <h3
                        class="line-clamp-1 text-[13px] font-semibold text-zinc-900"
                    >
                        {{ category.name }}
                    </h3>
                    <p
                        v-if="category.products_count !== undefined"
                        class="om-meta mt-0.5 !text-[11px]"
                    >
                        {{ productLabel(category.products_count) }}
                    </p>
                </div>
            </Link>
        </div>
    </Container>
</template>
