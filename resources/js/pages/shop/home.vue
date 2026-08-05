<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';
import { computed } from 'vue';
import Card from '@/components/shop/card.vue';
import Container from '@/components/shop/container.vue';
import HeroCarousel from '@/components/shop/hero-carousel.vue';
import ProductRail from '@/components/shop/product-rail.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import * as shop from '@/routes/shop';
import type { Brand, Category, Collection, Product } from '@/types/shop';

defineProps<{
    featuredProducts: Product[];
    promoProducts: Product[];
    featuredCollections: Collection[];
    categories: Category[];
    brands: Brand[];
}>();

const page = usePage();
const isGuest = computed(() => !page.props.auth.user);

const firstName = computed(() => {
    const user = page.props.auth.user as
        | { first_name?: string; name?: string }
        | null
        | undefined;
    return user?.first_name ?? user?.name?.split(' ')[0] ?? '';
});

function brandShopUrl(brandId: number): string {
    return shop.index.url({ query: { brand: brandId } });
}
</script>

<template>
    <Head title="Beranda" />

    <div class="pb-6">
        <Container class="pt-3">
            <Card
                v-if="isGuest"
                class="border-[var(--om-navy)]/25"
                content-class="px-3 py-3"
            >
                <p class="om-meta leading-snug !text-muted-foreground">
                    Masuk atau daftar akun OceanMall untuk kemudahan berbelanja
                    dan promo eksklusif bagi member
                </p>
                <div class="mt-2.5 grid grid-cols-2 gap-2">
                    <Button as-child variant="outline" size="xl">
                        <Link :href="login.url()"> Masuk </Link>
                    </Button>
                    <Button as-child size="xl">
                        <Link :href="register.url()"> Daftar </Link>
                    </Button>
                </div>
            </Card>

            <Card
                v-else
                content-class="flex items-center justify-between px-3 py-2.5"
            >
                <div>
                    <p class="text-[13px] font-semibold text-[var(--om-navy)]">
                        Halo, {{ firstName }}
                    </p>
                    <p class="om-meta mt-0.5 !text-[11px]">
                        Siap belanja hari ini?
                    </p>
                </div>
                <Link :href="dashboard.url()" class="om-action-primary">
                    Akun
                </Link>
            </Card>
        </Container>

        <section v-if="categories.length" class="mt-4">
            <Container>
                <div
                    class="flex gap-3 overflow-x-auto pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="shop.category.url({ category: category.slug })"
                        class="flex w-[4.5rem] shrink-0 flex-col items-center gap-1.5 text-center"
                    >
                        <div
                            class="flex size-11 items-center justify-center overflow-hidden rounded-full bg-muted ring-1 ring-border/60"
                        >
                            <img
                                v-if="category.thumbnail"
                                :src="category.thumbnail"
                                :alt="category.name"
                                class="size-full object-cover"
                                loading="lazy"
                            />
                            <Smartphone
                                v-else
                                class="size-5 text-[var(--om-navy)]"
                                aria-hidden="true"
                            />
                        </div>
                        <span
                            class="line-clamp-2 text-[11px] leading-tight font-medium text-foreground"
                        >
                            {{ category.name }}
                        </span>
                    </Link>
                </div>
            </Container>
        </section>

        <HeroCarousel :collections="featuredCollections" />

        <section v-if="brands.length" class="mt-4">
            <Container>
                <div
                    class="flex gap-3 overflow-x-auto pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <Link
                        v-for="brand in brands"
                        :key="brand.id"
                        :href="brandShopUrl(brand.id)"
                        class="flex w-[4.25rem] shrink-0 flex-col items-center gap-1"
                    >
                        <div
                            class="flex size-12 items-center justify-center overflow-hidden rounded-full bg-card ring-1 ring-border"
                        >
                            <img
                                v-if="brand.thumbnail"
                                :src="brand.thumbnail"
                                :alt="brand.name"
                                class="size-full object-contain p-1.5"
                                loading="lazy"
                            />
                            <span
                                v-else
                                class="text-[11px] font-bold text-[var(--om-navy)]"
                            >
                                {{ brand.name.slice(0, 2).toUpperCase() }}
                            </span>
                        </div>
                        <span
                            class="line-clamp-1 text-center text-[10px] text-muted-foreground"
                        >
                            {{ brand.name }}
                        </span>
                    </Link>
                </div>
            </Container>
        </section>

        <ProductRail
            v-if="promoProducts.length"
            title="Promo hari ini"
            :href="shop.index.url()"
            :products="promoProducts"
            tone="soft"
        />

        <ProductRail
            v-if="featuredProducts.length"
            title="Produk unggulan"
            :href="shop.index.url()"
            :products="featuredProducts"
        />
    </div>
</template>
