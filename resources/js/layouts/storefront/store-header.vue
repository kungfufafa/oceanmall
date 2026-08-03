<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Search, ShoppingBag, User, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AnnouncementBar from '@/components/shop/announcement-bar.vue';
import BrandIcon from '@/components/shop/brand-icon.vue';
import Container from '@/components/shop/container.vue';
import { useShop } from '@/composables/useShop';
import { dashboard, home, login, logout, register } from '@/routes';
import * as shop from '@/routes/shop';
import type { NavCategory } from '@/types/shop';

const page = usePage();
const { cartCount } = useShop();

const mobileOpen = ref<boolean>(false);
const currentUrl = computed<string>(() => page.url ?? '');

const navCategories = computed<NavCategory[]>(
    () => page.props.shop?.nav_categories ?? [],
);

function isActive(path: string): boolean {
    if (path === '/')
        return currentUrl.value === '/' || currentUrl.value === '';
    return currentUrl.value.startsWith(path);
}

function isCategoryActive(slug: string): boolean {
    return currentUrl.value.startsWith(`/categories/${slug}`);
}
</script>

<template>
    <header
        class="sticky top-0 z-30 border-b border-zinc-200 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900"
    >
        <AnnouncementBar
            message="Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%!"
        />

        <Container>
            <div class="flex h-16 items-center justify-between">
                <button
                    type="button"
                    class="-ml-2 rounded-md p-2 text-zinc-500 hover:text-zinc-900 lg:hidden dark:hover:text-white"
                    aria-label="Open menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Menu
                        v-if="!mobileOpen"
                        class="size-5"
                        aria-hidden="true"
                    />
                    <X v-else class="size-5" aria-hidden="true" />
                </button>

                <Link :href="home.url()" class="flex items-center gap-2">
                    <BrandIcon
                        class="size-8 fill-current text-black dark:text-white"
                    />
                    <span
                        class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white"
                    >
                        OceanMall
                    </span>
                </Link>

                <nav
                    role="navigation"
                    class="hidden items-center gap-6 lg:flex"
                >
                    <Link
                        :href="home.url()"
                        :class="[
                            'text-sm transition',
                            isActive('/') && !currentUrl.startsWith('/shop')
                                ? 'font-medium text-zinc-900 dark:text-white'
                                : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white',
                        ]"
                    >
                        Home
                    </Link>
                    <Link
                        :href="shop.index.url()"
                        :class="[
                            'text-sm transition',
                            currentUrl === '/shop' ||
                            currentUrl.startsWith('/shop?')
                                ? 'font-medium text-zinc-900 dark:text-white'
                                : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white',
                        ]"
                    >
                        Shop
                    </Link>
                    <Link
                        v-for="category in navCategories"
                        :key="category.id"
                        :href="shop.category.url({ category: category.slug })"
                        :class="[
                            'text-sm transition',
                            isCategoryActive(category.slug)
                                ? 'font-medium text-zinc-900 dark:text-white'
                                : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white',
                        ]"
                    >
                        {{ category.name }}
                    </Link>
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        :href="shop.search.url()"
                        class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                        aria-label="Search"
                    >
                        <Search class="size-5" aria-hidden="true" />
                    </Link>

                    <Link
                        :href="shop.cart.url()"
                        class="relative text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                        aria-label="Cart"
                    >
                        <ShoppingBag class="size-5" aria-hidden="true" />
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-2 -right-2 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-zinc-900 px-1 text-[10px] font-medium text-white dark:bg-white dark:text-zinc-900"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>

                    <Link
                        v-if="page.props.auth.user"
                        :href="dashboard.url()"
                        class="hidden text-zinc-500 transition hover:text-zinc-900 lg:inline-flex dark:hover:text-white"
                        aria-label="Account"
                    >
                        <User class="size-5" aria-hidden="true" />
                    </Link>
                    <Link
                        v-else
                        :href="login.url()"
                        class="hidden text-zinc-500 transition hover:text-zinc-900 lg:inline-flex dark:hover:text-white"
                        aria-label="Sign in"
                    >
                        <User class="size-5" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </Container>

        <div
            v-if="mobileOpen"
            class="border-t border-zinc-200 lg:hidden dark:border-zinc-700"
        >
            <div class="mx-auto max-w-7xl space-y-3 px-4 py-4 sm:px-6">
                <Link
                    :href="home.url()"
                    class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                    @click="mobileOpen = false"
                >
                    Home
                </Link>
                <Link
                    :href="shop.index.url()"
                    class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                    @click="mobileOpen = false"
                >
                    Shop
                </Link>
                <Link
                    v-for="category in navCategories"
                    :key="category.id"
                    :href="shop.category.url({ category: category.slug })"
                    class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                    @click="mobileOpen = false"
                >
                    {{ category.name }}
                </Link>

                <div
                    class="space-y-3 border-t border-zinc-200 pt-3 dark:border-zinc-700"
                >
                    <template v-if="page.props.auth.user">
                        <Link
                            :href="dashboard.url()"
                            class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            @click="mobileOpen = false"
                        >
                            My account
                        </Link>
                        <form method="POST" :action="logout.url()">
                            <button
                                type="submit"
                                class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            >
                                Log out
                            </button>
                        </form>
                    </template>
                    <template v-else>
                        <Link
                            :href="login.url()"
                            class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            @click="mobileOpen = false"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register.url()"
                            class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            @click="mobileOpen = false"
                        >
                            Create account
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>
