<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Search, ShoppingCart, Bell, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BrandIcon from '@/components/shop/brand-icon.vue';
import { Badge } from '@/components/ui/badge';
import { useShop } from '@/composables/useShop';
import { dashboard, home, login, register } from '@/routes';
import { notifications as accountNotifications } from '@/routes/account';
import * as shop from '@/routes/shop';
import type { NavCategory } from '@/types/shop';

const page = usePage();
const { cartCount } = useShop();

const searchQuery = ref<string>('');

const user = computed(() => page.props.auth.user);
const unreadCount = computed(
    () => Number(page.props.notificationsUnreadCount ?? 0),
);

const navCategories = computed<NavCategory[]>(
    () => page.props.shop?.nav_categories ?? [],
);

const searchPlaceholder = computed<string>(() => {
    const first = navCategories.value[0]?.name;
    return first
        ? `Cari brand, produk, atau ${first}…`
        : 'Cari brand, produk, atau kategori…';
});

const hasQuery = computed(() => searchQuery.value.trim().length > 0);

function submitSearch(): void {
    const q = searchQuery.value.trim();
    router.get(shop.search.url(), q ? { q } : {}, { preserveState: false });
}

function clearSearch(): void {
    searchQuery.value = '';
}
</script>

<template>
    <header class="sticky top-0 z-40 bg-[var(--om-navy)]">
        <!-- Mobile chrome -->
        <div
            class="flex items-center gap-2 px-3 lg:hidden"
            :style="{
                minHeight: 'var(--om-header-height)',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
            }"
        >
            <form class="min-w-0 flex-1" @submit.prevent="submitSearch">
                <label for="store-search-mobile" class="sr-only"
                    >Cari produk</label
                >
                <div
                    class="flex items-center overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-black/5"
                >
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400"
                            aria-hidden="true"
                        />
                        <input
                            id="store-search-mobile"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="searchPlaceholder"
                            class="h-11 w-full border-0 bg-transparent pr-10 pl-10 text-[13px] text-zinc-900 outline-none placeholder:text-zinc-400 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden"
                        />
                        <button
                            v-if="hasQuery"
                            type="button"
                            class="absolute top-1/2 right-2.5 flex size-7 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
                            aria-label="Hapus pencarian"
                            @click="clearSearch"
                        >
                            <X class="size-3.5" stroke-width="2.5" />
                        </button>
                    </div>
                    <button
                        type="submit"
                        class="m-1 flex size-9 shrink-0 items-center justify-center rounded-md bg-[var(--om-navy)] text-white transition hover:bg-[var(--om-navy-hover)]"
                        aria-label="Cari"
                    >
                        <Search
                            class="size-4"
                            stroke-width="2.25"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </form>

            <Link
                v-if="user"
                :href="accountNotifications.url()"
                class="relative flex size-11 items-center justify-center rounded-md text-white transition hover:bg-white/10"
                aria-label="Notifikasi"
            >
                <Bell class="size-6" stroke-width="1.75" aria-hidden="true" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="count"
                    class="absolute top-1 right-1"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </Badge>
            </Link>

            <Link
                :href="shop.cart.url()"
                class="relative flex size-11 items-center justify-center rounded-md text-white transition hover:bg-white/10"
                aria-label="Keranjang"
            >
                <ShoppingCart
                    class="size-6"
                    stroke-width="1.75"
                    aria-hidden="true"
                />
                <Badge
                    v-if="cartCount > 0"
                    variant="count"
                    class="absolute top-1 right-1"
                >
                    {{ cartCount > 99 ? '99+' : cartCount }}
                </Badge>
            </Link>
        </div>

        <!-- Desktop -->
        <div class="hidden lg:block">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-6 py-4">
                <Link
                    :href="home.url()"
                    class="inline-flex shrink-0 items-center gap-2 text-white"
                >
                    <BrandIcon class="h-8 w-auto fill-current text-white" />
                    <span class="text-[1.35rem] font-extrabold tracking-tight">
                        OceanMall
                    </span>
                </Link>

                <form class="min-w-0 flex-1" @submit.prevent="submitSearch">
                    <label for="store-search-desktop" class="sr-only"
                        >Cari produk</label
                    >
                    <div
                        class="flex items-center overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-black/5 focus-within:ring-2 focus-within:ring-white/40"
                    >
                        <div class="relative min-w-0 flex-1">
                            <input
                                id="store-search-desktop"
                                v-model="searchQuery"
                                type="search"
                                :placeholder="searchPlaceholder"
                                class="h-12 w-full border-0 bg-transparent pr-11 pl-5 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden"
                            />
                            <button
                                v-if="hasQuery"
                                type="button"
                                class="absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
                                aria-label="Hapus pencarian"
                                @click="clearSearch"
                            >
                                <X class="size-4" stroke-width="2.5" />
                            </button>
                        </div>
                        <button
                            type="submit"
                            class="m-1.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-[var(--om-navy)] text-white transition hover:bg-[var(--om-navy-hover)]"
                            aria-label="Cari"
                        >
                            <Search
                                class="size-[18px]"
                                stroke-width="2.25"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </form>

                <div class="flex shrink-0 items-center gap-3">
                    <Link
                        v-if="user"
                        :href="accountNotifications.url()"
                        class="relative flex size-10 items-center justify-center rounded-md text-white transition hover:bg-white/10"
                        aria-label="Notifikasi"
                    >
                        <Bell
                            class="size-6"
                            stroke-width="1.75"
                            aria-hidden="true"
                        />
                        <Badge
                            v-if="unreadCount > 0"
                            variant="count"
                            class="absolute top-0.5 right-0.5"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </Badge>
                    </Link>

                    <Link
                        :href="shop.cart.url()"
                        class="relative flex size-10 items-center justify-center rounded-md text-white transition hover:bg-white/10"
                        aria-label="Keranjang"
                    >
                        <ShoppingCart
                            class="size-6"
                            stroke-width="1.75"
                            aria-hidden="true"
                        />
                        <Badge
                            v-if="cartCount > 0"
                            variant="count"
                            class="absolute top-0.5 right-0.5"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </Badge>
                    </Link>

                    <div class="h-7 w-px bg-white/25" aria-hidden="true" />

                    <template v-if="user">
                        <Link
                            :href="dashboard.url()"
                            class="inline-flex h-10 items-center rounded-lg border border-white/80 px-4 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Akun
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login.url()"
                            class="inline-flex h-10 items-center rounded-lg border border-white/80 px-4 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="register.url()"
                            class="inline-flex h-10 items-center rounded-lg bg-white px-4 text-sm font-semibold text-[var(--om-navy)] transition hover:bg-zinc-100"
                        >
                            Daftar
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>
