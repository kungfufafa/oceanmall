<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandIcon from '@/components/shop/brand-icon.vue';
import BrandShopper from '@/components/shop/brand-shopper.vue';
import BrandVue from '@/components/shop/brand-vue.vue';
import Container from '@/components/shop/container.vue';
import ZoneSelector from '@/components/shop/zone-selector.vue';
import { dashboard, home, login, register } from '@/routes';
import { orders as accountOrders } from '@/routes/account';
import * as shop from '@/routes/shop';
import type { NavCategory } from '@/types/shop';

const page = usePage();

const currentYear = new Date().getFullYear();

const footerCategories = computed<NavCategory[]>(
    () => page.props.shop?.footer_categories ?? [],
);
</script>

<template>
    <footer
        aria-labelledby="footer-heading"
        class="border-t border-zinc-200 bg-linear-to-b from-zinc-50 to-white dark:border-white/10 dark:from-zinc-900/95 dark:to-zinc-950"
    >
        <h2 id="footer-heading" class="sr-only">Footer</h2>
        <Container>
            <div
                class="py-10 sm:py-20 lg:grid lg:grid-cols-2 lg:gap-10 lg:py-24"
            >
                <div>
                    <div class="max-w-sm">
                        <Link
                            :href="home.url()"
                            class="inline-flex items-center gap-2"
                        >
                            <BrandIcon
                                class="h-10 w-auto fill-current text-black dark:text-white"
                            />
                            <span
                                class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white"
                            >
                                OceanMall
                            </span>
                        </Link>
                        <p
                            class="mt-8 text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                        >
                            Build modern, scalable online stores. It includes
                            essential features like product management,
                            checkout, and order handling.
                        </p>
                    </div>

                    <div class="mt-12">
                        <ZoneSelector />
                    </div>
                </div>

                <div
                    class="mt-16 gap-8 space-y-6 sm:grid sm:grid-cols-3 sm:space-y-0 lg:col-span-1 lg:mt-0"
                >
                    <div>
                        <h3
                            class="text-sm leading-6 font-semibold text-zinc-900 dark:text-white"
                        >
                            Shop
                        </h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <Link
                                    :href="shop.index.url()"
                                    class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                    >All Products</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="shop.categories.url()"
                                    class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                    >Categories</Link
                                >
                            </li>
                        </ul>
                    </div>

                    <div v-if="footerCategories.length">
                        <h3
                            class="text-sm leading-6 font-semibold text-zinc-900 dark:text-white"
                        >
                            Categories
                        </h3>
                        <ul role="list" class="mt-6 space-y-4">
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
                                    class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                >
                                    {{ category.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3
                            class="text-sm leading-6 font-semibold text-zinc-900 dark:text-white"
                        >
                            Account
                        </h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <template v-if="page.props.auth.user">
                                <li>
                                    <Link
                                        :href="dashboard.url()"
                                        class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                        >My account</Link
                                    >
                                </li>
                                <li>
                                    <Link
                                        :href="accountOrders.url()"
                                        class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                        >My orders</Link
                                    >
                                </li>
                            </template>
                            <template v-else>
                                <li>
                                    <Link
                                        :href="login.url()"
                                        class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                        >Log in</Link
                                    >
                                </li>
                                <li>
                                    <Link
                                        :href="register.url()"
                                        class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                        >Create account</Link
                                    >
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="justify-between border-t border-zinc-100 py-6 sm:flex sm:items-center lg:py-14 dark:border-zinc-800"
            >
                <p class="text-sm text-zinc-500">
                    © {{ currentYear }} OceanMall. All rights reserved.
                </p>
                <a
                    href="https://laravelshopper.dev"
                    class="mt-2 inline-flex items-center gap-x-2 text-sm font-medium text-zinc-400 sm:mt-0"
                    target="_blank"
                    rel="noopener"
                >
                    <span>Powered by</span>
                    <BrandShopper class="size-5 opacity-45" />
                    <span>&amp;</span>
                    <BrandVue class="size-5 opacity-45" />
                </a>
            </div>
        </Container>
    </footer>
</template>
