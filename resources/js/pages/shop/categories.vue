<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import ShopCard from '@/components/shop/card.vue';
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
        <h1
            class="mb-1 hidden text-lg font-semibold tracking-tight text-foreground lg:block"
        >
            Kategori
        </h1>
        <p class="mb-4 text-sm text-muted-foreground">
            Jelajahi produk berdasarkan kategori
        </p>

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
                class="group block focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <ShopCard
                    :padded="false"
                    class="h-full overflow-hidden border-border bg-card transition group-hover:border-primary/50"
                    content-class="p-0"
                >
                    <div
                        class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-muted"
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
                            class="size-8 text-primary"
                            aria-hidden="true"
                        />
                    </div>
                    <div class="px-2.5 py-2">
                        <h3
                            class="line-clamp-1 text-[13px] font-semibold text-foreground"
                        >
                            {{ category.name }}
                        </h3>
                        <p
                            v-if="category.products_count !== undefined"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ productLabel(category.products_count) }}
                        </p>
                    </div>
                </ShopCard>
            </Link>
        </div>
    </Container>
</template>
