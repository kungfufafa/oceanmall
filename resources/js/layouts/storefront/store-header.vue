<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Search, ShoppingCart, Bell, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BrandIcon from '@/components/shop/brand-icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
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
                    class="flex items-center overflow-hidden rounded-md bg-card shadow-sm ring-1 ring-black/5"
                >
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="store-search-mobile"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="searchPlaceholder"
                            class="h-11 border-0 bg-transparent pr-10 pl-10 text-[13px] shadow-none focus-visible:ring-0 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden"
                        />
                        <Button
                            v-if="hasQuery"
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground"
                            aria-label="Hapus pencarian"
                            @click="clearSearch"
                        >
                            <X stroke-width="2.5" />
                        </Button>
                    </div>
                    <Button
                        type="submit"
                        size="icon"
                        class="m-1 shrink-0 bg-[var(--om-navy)] hover:bg-[var(--om-navy-hover)]"
                        aria-label="Cari"
                    >
                        <Search stroke-width="2.25" />
                    </Button>
                </div>
            </form>

            <Link
                v-if="user"
                :href="accountNotifications.url()"
                class="relative flex size-11 items-center justify-center rounded-md text-primary-foreground transition hover:bg-primary-foreground/10"
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
                class="relative flex size-11 items-center justify-center rounded-md text-primary-foreground transition hover:bg-primary-foreground/10"
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
                    class="inline-flex shrink-0 items-center gap-2.5 text-primary-foreground"
                >
                    <BrandIcon class="h-9 w-auto" />
                    <span class="text-[1.35rem] font-extrabold tracking-tight flex items-center">
                        <span class="text-[#38BDF8]">OCEAN</span>
                        <span class="text-white">MALL</span>
                    </span>
                </Link>

                <form class="min-w-0 flex-1" @submit.prevent="submitSearch">
                    <label for="store-search-desktop" class="sr-only"
                        >Cari produk</label
                    >
                    <div
                        class="flex items-center overflow-hidden rounded-md bg-card shadow-sm ring-1 ring-black/5 focus-within:ring-2 focus-within:ring-primary-foreground/40"
                    >
                        <div class="relative min-w-0 flex-1">
                            <Input
                                id="store-search-desktop"
                                v-model="searchQuery"
                                type="search"
                                :placeholder="searchPlaceholder"
                                class="h-12 border-0 bg-transparent pr-11 pl-5 text-sm shadow-none focus-visible:ring-0 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden"
                            />
                            <Button
                                v-if="hasQuery"
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="absolute top-1/2 right-2 -translate-y-1/2 text-muted-foreground"
                                aria-label="Hapus pencarian"
                                @click="clearSearch"
                            >
                                <X stroke-width="2.5" />
                            </Button>
                        </div>
                        <Button
                            type="submit"
                            size="icon"
                            class="m-1.5 shrink-0 bg-[var(--om-navy)] hover:bg-[var(--om-navy-hover)]"
                            aria-label="Cari"
                        >
                            <Search class="size-[18px]" stroke-width="2.25" />
                        </Button>
                    </div>
                </form>

                <div class="flex shrink-0 items-center gap-3">
                    <Link
                        v-if="user"
                        :href="accountNotifications.url()"
                        class="relative flex size-10 items-center justify-center rounded-md text-primary-foreground transition hover:bg-primary-foreground/10"
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
                        class="relative flex size-10 items-center justify-center rounded-md text-primary-foreground transition hover:bg-primary-foreground/10"
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

                    <Separator
                        orientation="vertical"
                        class="h-7 bg-primary-foreground/25"
                    />

                    <template v-if="user">
                        <Button
                            as-child
                            variant="outline"
                            class="h-10 border-primary-foreground/80 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground"
                        >
                            <Link :href="dashboard.url()">Akun</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            as-child
                            variant="outline"
                            class="h-10 border-primary-foreground/80 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground"
                        >
                            <Link :href="login.url()">Masuk</Link>
                        </Button>
                        <Button
                            as-child
                            class="h-10 bg-background text-[var(--om-navy)] hover:bg-muted"
                        >
                            <Link :href="register.url()">Daftar</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>
