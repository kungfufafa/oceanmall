<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Grid2x2,
    Home,
    ShoppingBag,
    ShoppingCart,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useShop } from '@/composables/useShop';
import { dashboard, home, login } from '@/routes';
import { cart, categories, index as shopIndex } from '@/routes/shop';

const page = usePage();
const { cartCount } = useShop();

const path = computed(() => (page.url ?? '').split('?')[0] || '/');

const accountHref = computed(() =>
    page.props.auth.user ? dashboard.url() : login.url(),
);

const current = computed(() => {
    const url = path.value;
    if (url === '/' || url === '') return 'home';
    if (url.includes('categor')) return 'categories';
    if (url.startsWith('/cart') || url.startsWith('/checkout')) return 'cart';
    if (
        url.startsWith('/dashboard') ||
        url.startsWith('/settings') ||
        url.startsWith('/account') ||
        url.startsWith('/login') ||
        url.startsWith('/register') ||
        url.startsWith('/forgot-password') ||
        url.startsWith('/reset-password') ||
        url.startsWith('/confirm-password') ||
        url.startsWith('/email') ||
        url.startsWith('/two-factor')
    ) {
        return 'account';
    }
    if (
        url === '/shop' ||
        url.startsWith('/shop/') ||
        url.startsWith('/products') ||
        url.startsWith('/search') ||
        url.startsWith('/collections')
    ) {
        return 'shop';
    }
    return null;
});

function itemClass(key: string): string {
    const on = current.value === key;
    return [
        'flex h-full min-w-0 flex-1 flex-col items-center justify-center gap-1',
        on ? 'text-[var(--om-navy)]' : 'text-muted-foreground',
    ].join(' ');
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-background lg:hidden"
        style="padding-bottom: env(safe-area-inset-bottom, 0px)"
        aria-label="Navigasi utama"
    >
        <div
            class="mx-auto flex max-w-lg items-stretch"
            :style="{ height: 'var(--om-bottom-nav-height)' }"
        >
            <Link :href="home.url()" :class="itemClass('home')">
                <Home class="size-6" stroke-width="1.75" aria-hidden="true" />
                <span
                    class="leading-none font-semibold"
                    :style="{ fontSize: 'var(--om-text-nav)' }"
                    >Beranda</span
                >
            </Link>

            <Link :href="categories.url()" :class="itemClass('categories')">
                <Grid2x2
                    class="size-6"
                    stroke-width="1.75"
                    aria-hidden="true"
                />
                <span
                    class="leading-none font-semibold"
                    :style="{ fontSize: 'var(--om-text-nav)' }"
                    >Kategori</span
                >
            </Link>

            <Link :href="shopIndex.url()" :class="itemClass('shop')">
                <ShoppingBag
                    class="size-6"
                    stroke-width="1.75"
                    aria-hidden="true"
                />
                <span
                    class="leading-none font-semibold"
                    :style="{ fontSize: 'var(--om-text-nav)' }"
                    >Belanja</span
                >
            </Link>

            <Link :href="cart.url()" :class="itemClass('cart')">
                <span class="relative">
                    <ShoppingCart
                        class="size-6"
                        stroke-width="1.75"
                        aria-hidden="true"
                    />
                    <Badge
                        v-if="cartCount > 0"
                        variant="count"
                        class="absolute -top-1.5 -right-2.5"
                    >
                        {{ cartCount > 99 ? '99+' : cartCount }}
                    </Badge>
                </span>
                <span
                    class="leading-none font-semibold"
                    :style="{ fontSize: 'var(--om-text-nav)' }"
                    >Keranjang</span
                >
            </Link>

            <Link :href="accountHref" :class="itemClass('account')">
                <UserRound
                    class="size-6"
                    stroke-width="1.75"
                    aria-hidden="true"
                />
                <span
                    class="leading-none font-semibold"
                    :style="{ fontSize: 'var(--om-text-nav)' }"
                    >Akun</span
                >
            </Link>
        </div>
    </nav>
</template>
