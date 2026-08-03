<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingBag } from 'lucide-vue-next';
import Container from '@/components/shop/container.vue';
import { Button } from '@/components/ui/button';
import { useCart } from '@/composables/useCart';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';
import * as shop from '@/routes/shop';
import * as checkout from '@/routes/shop/checkout';
import type { Cart, CartContext, Product } from '@/types/shop';

defineProps<{
    cart: Cart | null;
    cartContext: CartContext | null;
}>();

const page = usePage();
const { currency, taxLabel } = useShop();
const cartActions = useCart();

function productSlug(
    purchasable: Cart['lines'][number]['purchasable'],
): string | null {
    if ('slug' in purchasable && purchasable.slug) {
        return purchasable.slug;
    }
    if (
        'product' in purchasable &&
        (purchasable as { product?: Product }).product?.slug
    ) {
        return (purchasable as { product: Product }).product.slug;
    }
    return null;
}

function confirmClear(): void {
    if (window.confirm('Are you sure you want to clear your cart?')) {
        cartActions.clear();
    }
}
</script>

<template>
    <Head title="Cart" />

    <Container class="py-8 sm:py-12">
        <h1
            class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
        >
            Shopping Cart
        </h1>

        <div
            v-if="!cart || !cart.lines.length"
            class="mt-16 flex flex-col items-center justify-center text-center"
        >
            <ShoppingBag
                class="size-16 text-zinc-300 dark:text-zinc-600"
                aria-hidden="true"
            />
            <h2
                class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white"
            >
                Your cart is empty
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                Start shopping to add items to your cart.
            </p>
            <Link :href="shop.index.url()" class="mt-6">
                <Button>Continue Shopping</Button>
            </Link>
        </div>

        <div v-else class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
            <div class="lg:col-span-7">
                <ul
                    role="list"
                    class="divide-y divide-zinc-200 dark:divide-zinc-700"
                >
                    <li
                        v-for="line in cart.lines"
                        :key="line.id"
                        class="flex gap-4 py-6"
                    >
                        <div
                            class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 sm:size-24 dark:bg-zinc-800"
                        >
                            <img
                                v-if="line.purchasable.thumbnail"
                                :src="line.purchasable.thumbnail"
                                :alt="
                                    'name' in line.purchasable
                                        ? line.purchasable.name
                                        : ''
                                "
                                class="size-full object-cover object-center"
                            />
                        </div>

                        <div class="flex flex-1 flex-col justify-between">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <h3
                                        class="text-sm font-medium text-zinc-900 dark:text-white"
                                    >
                                        <Link
                                            v-if="productSlug(line.purchasable)"
                                            :href="
                                                shop.product.url({
                                                    product: productSlug(
                                                        line.purchasable,
                                                    ) as string,
                                                })
                                            "
                                            class="hover:underline"
                                        >
                                            {{
                                                'name' in line.purchasable
                                                    ? line.purchasable.name
                                                    : ''
                                            }}
                                        </Link>
                                        <template v-else>{{
                                            'name' in line.purchasable
                                                ? line.purchasable.name
                                                : ''
                                        }}</template>
                                    </h3>
                                    <p class="mt-0.5 text-sm text-zinc-500">
                                        {{
                                            formatMoney(
                                                line.unit_price_amount,
                                                currency,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="text-sm font-medium text-zinc-900 dark:text-white"
                                >
                                    {{
                                        formatMoney(
                                            line.unit_price_amount *
                                                line.quantity,
                                            currency,
                                        )
                                    }}
                                </p>
                            </div>

                            <div class="mt-2 flex items-center justify-between">
                                <div
                                    class="flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600"
                                >
                                    <button
                                        type="button"
                                        class="px-2 py-1 text-zinc-500 hover:text-zinc-900 disabled:opacity-40 dark:hover:text-white"
                                        :disabled="line.quantity <= 1"
                                        aria-label="Decrease"
                                        @click="
                                            cartActions.update(
                                                line.id,
                                                line.quantity - 1,
                                            )
                                        "
                                    >
                                        <Minus
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <span
                                        class="min-w-6 text-center text-xs font-medium text-zinc-900 dark:text-white"
                                        >{{ line.quantity }}</span
                                    >
                                    <button
                                        type="button"
                                        class="px-2 py-1 text-zinc-500 hover:text-zinc-900 dark:hover:text-white"
                                        aria-label="Increase"
                                        @click="
                                            cartActions.update(
                                                line.id,
                                                line.quantity + 1,
                                            )
                                        "
                                    >
                                        <Plus
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    class="text-sm text-red-500 transition hover:text-red-700"
                                    @click="cartActions.remove(line.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>

                <div
                    class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700"
                >
                    <Link
                        :href="shop.index.url()"
                        class="text-sm text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                    >
                        ← Continue Shopping
                    </Link>
                    <button
                        type="button"
                        class="text-sm text-red-500 transition hover:text-red-700"
                        @click="confirmClear"
                    >
                        Clear Cart
                    </button>
                </div>
            </div>

            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <div class="rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-800/50">
                    <h2
                        class="text-lg font-semibold text-zinc-900 dark:text-white"
                    >
                        Order Summary
                    </h2>

                    <dl class="mt-6 space-y-3 text-sm text-zinc-500">
                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Tax</dt>
                            <dd class="text-base text-zinc-900 dark:text-white">
                                {{
                                    formatMoney(
                                        cartContext?.taxTotal ?? 0,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Delivery</dt>
                            <dd>Calculated at checkout</dd>
                        </div>

                        <div
                            v-if="cartContext && cartContext.discountTotal > 0"
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Discount</dt>
                            <dd class="text-emerald-600">
                                −{{
                                    formatMoney(
                                        cartContext.discountTotal,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <dt
                                class="text-base font-semibold text-zinc-900 dark:text-white"
                            >
                                Subtotal {{ taxLabel }}
                            </dt>
                            <dd
                                class="text-base font-semibold text-zinc-900 dark:text-white"
                            >
                                {{
                                    formatMoney(
                                        cartContext?.subtotal ?? 0,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <Link :href="checkout.index.url()" class="block">
                            <Button class="w-full">
                                {{
                                    page.props.auth.user
                                        ? 'Proceed to checkout'
                                        : 'Sign in to checkout'
                                }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </Container>
</template>
