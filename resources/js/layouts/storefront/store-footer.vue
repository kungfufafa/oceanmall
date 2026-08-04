<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Package, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import BrandIcon from '@/components/shop/brand-icon.vue';
import Container from '@/components/shop/container.vue';
import { home } from '@/routes';
import * as shop from '@/routes/shop';
import type {
    FooterPaymentMethod,
    FooterShippingCourier,
    NavCategory,
} from '@/types/shop';

const page = usePage();

const currentYear = new Date().getFullYear();

const footerCategories = computed<NavCategory[]>(
    () => page.props.shop?.footer_categories ?? [],
);

const paymentMethods = computed<FooterPaymentMethod[]>(
    () => page.props.shop?.payment_methods ?? [],
);

const shippingCouriers = computed<FooterShippingCourier[]>(
    () => page.props.shop?.shipping_couriers ?? [],
);

const footerGridClass = computed(() =>
    footerCategories.value.length
        ? 'lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.85fr)_minmax(0,0.95fr)_minmax(0,1.5fr)]'
        : 'lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1.5fr)]',
);

const trustPoints = [
    { label: 'Produk original', icon: BadgeCheck },
    { label: 'Pembayaran aman', icon: ShieldCheck },
    { label: 'Kirim ke seluruh Indonesia', icon: Package },
] as const;
</script>

<template>
    <footer
        aria-labelledby="footer-heading"
        class="mt-auto border-t border-zinc-200 bg-white"
    >
        <h2 id="footer-heading" class="sr-only">Footer</h2>
        <Container>
            <div
                class="grid gap-10 py-10 sm:py-12 lg:gap-x-10 lg:gap-y-0 lg:py-14"
                :class="footerGridClass"
            >
                <!-- Brand -->
                <div>
                    <Link
                        :href="home.url()"
                        class="inline-flex items-center gap-2"
                    >
                        <BrandIcon
                            class="h-9 w-auto fill-current text-[var(--om-navy)]"
                        />
                        <span
                            class="text-lg font-extrabold tracking-tight text-[var(--om-navy)]"
                        >
                            OceanMall
                        </span>
                    </Link>
                    <p class="mt-4 max-w-xs text-sm leading-6 text-zinc-600">
                        Belanja gadget & lifestyle terpercaya. Produk original,
                        pengiriman ke seluruh Indonesia, pembayaran aman.
                    </p>
                    <p class="mt-5 text-sm text-zinc-700">
                        Pengiriman ke
                        <span class="font-semibold text-[var(--om-navy)]"
                            >seluruh Indonesia</span
                        >
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900">Belanja</h3>
                    <ul role="list" class="mt-4 space-y-2.5">
                        <li>
                            <Link
                                :href="shop.index.url()"
                                class="text-sm text-zinc-600 hover:text-[var(--om-navy)]"
                                >Semua produk</Link
                            >
                        </li>
                        <li>
                            <Link
                                :href="shop.categories.url()"
                                class="text-sm text-zinc-600 hover:text-[var(--om-navy)]"
                                >Kategori</Link
                            >
                        </li>
                    </ul>
                </div>

                <div v-if="footerCategories.length">
                    <h3 class="text-sm font-bold text-zinc-900">Kategori</h3>
                    <ul role="list" class="mt-4 space-y-2.5">
                        <li
                            v-for="category in footerCategories"
                            :key="category.id"
                        >
                            <Link
                                :href="
                                    shop.category.url({
                                        category: category.slug,
                                    })
                                "
                                class="text-sm text-zinc-600 hover:text-[var(--om-navy)]"
                            >
                                {{ category.name }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Commerce logos -->
                <div
                    v-if="paymentMethods.length || shippingCouriers.length"
                    class="space-y-7"
                >
                    <div v-if="paymentMethods.length">
                        <h3 class="text-sm font-bold text-zinc-900">
                            Metode Pembayaran
                        </h3>
                        <ul
                            class="mt-3 flex flex-wrap items-center gap-x-3.5 gap-y-3"
                        >
                            <li
                                v-for="method in paymentMethods"
                                :key="method.key"
                                class="flex h-7 items-center"
                                :title="method.title"
                            >
                                <img
                                    v-if="method.logo"
                                    :src="method.logo"
                                    :alt="method.title"
                                    class="h-6 w-auto max-w-[4.75rem] object-contain"
                                    loading="lazy"
                                />
                                <span
                                    v-else
                                    class="text-xs font-semibold text-zinc-700"
                                >
                                    {{ method.title }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="shippingCouriers.length">
                        <h3 class="text-sm font-bold text-zinc-900">
                            Jasa Pengiriman
                        </h3>
                        <ul
                            class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-3"
                        >
                            <li
                                v-for="courier in shippingCouriers"
                                :key="courier.code"
                                class="flex h-8 items-center"
                                :title="courier.label"
                            >
                                <img
                                    v-if="courier.logo"
                                    :src="courier.logo"
                                    :alt="courier.label"
                                    class="h-7 w-auto max-w-[5.5rem] object-contain"
                                    loading="lazy"
                                />
                                <span
                                    v-else
                                    class="text-xs font-semibold text-zinc-700"
                                >
                                    {{ courier.label }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col gap-4 border-t border-zinc-200 py-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-zinc-500">
                    © {{ currentYear }} OceanMall. Hak cipta dilindungi.
                </p>
                <ul
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-500"
                >
                    <li
                        v-for="point in trustPoints"
                        :key="point.label"
                        class="inline-flex items-center gap-1.5"
                    >
                        <component
                            :is="point.icon"
                            class="size-3.5 shrink-0 text-[var(--om-navy)]"
                            aria-hidden="true"
                        />
                        <span>{{ point.label }}</span>
                    </li>
                </ul>
            </div>
        </Container>
    </footer>
</template>
