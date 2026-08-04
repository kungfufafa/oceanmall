<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ShoppingBag, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import CouponField from '@/components/shop/coupon-field.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import QtyStepper from '@/components/shop/qty-stepper.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCart } from '@/composables/useCart';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';
import * as shop from '@/routes/shop';
import * as checkout from '@/routes/shop/checkout';
import type { Cart, CartContext, Product } from '@/types/shop';

const props = defineProps<{
    cart: Cart | null;
    cartContext: CartContext | null;
    couponCode?: string | null;
}>();

const page = usePage();
const { currency, taxLabel } = useShop();
const cartActions = useCart();

const isEmpty = computed(() => !props.cart || !props.cart.lines.length);

const itemCount = computed(
    () => props.cart?.lines.reduce((sum, line) => sum + line.quantity, 0) ?? 0,
);

const totalAmount = computed(
    () =>
        (props.cartContext?.subtotal ?? 0) -
        (props.cartContext?.discountTotal ?? 0),
);

function productSlug(
    purchasable: Cart['lines'][number]['purchasable'],
): string | null {
    if ('slug' in purchasable && typeof purchasable.slug === 'string') {
        return purchasable.slug;
    }
    if (
        'product' in purchasable &&
        typeof (purchasable as { product?: Product }).product?.slug === 'string'
    ) {
        return (purchasable as { product: Product }).product.slug;
    }
    return null;
}

function lineName(purchasable: Cart['lines'][number]['purchasable']): string {
    return 'name' in purchasable ? purchasable.name : '';
}

function confirmClear(): void {
    if (window.confirm('Yakin ingin mengosongkan keranjang?')) {
        cartActions.clear();
    }
}
</script>

<template>
    <Head title="Keranjang" />

    <AppPageHeader
        class="lg:hidden"
        title="Keranjang"
        :end-label="isEmpty ? undefined : 'Kosongkan'"
        end-tone="muted"
        @end-click="confirmClear"
    >
        <template #title>
            Keranjang
            <span v-if="!isEmpty" class="font-medium text-zinc-400">
                ({{ itemCount }})
            </span>
        </template>
    </AppPageHeader>

    <Container class="pb-8 lg:py-8">
        <div class="hidden items-center justify-between lg:flex">
            <h1 class="om-page-title !text-lg">
                Keranjang
                <span v-if="!isEmpty" class="font-medium text-zinc-400">
                    ({{ itemCount }})
                </span>
            </h1>
            <Button
                v-if="!isEmpty"
                type="button"
                variant="ghost"
                size="sm"
                class="h-auto px-0 text-zinc-500"
                @click="confirmClear"
            >
                Kosongkan
            </Button>
        </div>

        <EmptyState
            v-if="isEmpty"
            title="Keranjang masih kosong"
            description="Yuk belanja dulu, produkmu muncul di sini."
            :icon="ShoppingBag"
            class="py-20"
        >
            <template #action>
                <Button as-child size="xl">
                    <Link :href="shop.index.url()"> Belanja sekarang </Link>
                </Button>
            </template>
        </EmptyState>

        <div
            v-else
            class="lg:mt-6 lg:grid lg:grid-cols-12 lg:gap-x-12"
        >
            <ul role="list" class="divide-y divide-zinc-100 lg:col-span-7">
                <li
                    v-for="line in cart!.lines"
                    :key="line.id"
                    class="flex gap-3 py-3.5"
                >
                    <div
                        class="size-16 shrink-0 overflow-hidden rounded-md bg-zinc-100 sm:size-[72px]"
                    >
                        <img
                            v-if="line.purchasable.thumbnail"
                            :src="line.purchasable.thumbnail"
                            :alt="lineName(line.purchasable)"
                            class="size-full object-cover object-center"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3
                                    class="line-clamp-2 text-[13px] leading-snug font-medium text-zinc-900"
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
                                    >
                                        {{ lineName(line.purchasable) }}
                                    </Link>
                                    <template v-else>
                                        {{ lineName(line.purchasable) }}
                                    </template>
                                </h3>
                                <p class="mt-0.5 text-[12px] text-zinc-500">
                                    {{
                                        formatMoney(
                                            line.unit_price_amount,
                                            currency,
                                        )
                                    }}
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0 text-zinc-400"
                                aria-label="Hapus"
                                @click="cartActions.remove(line.id)"
                            >
                                <Trash2 class="size-4" stroke-width="1.75" />
                            </Button>
                        </div>

                        <div class="mt-2.5 flex items-center justify-between gap-3">
                            <QtyStepper
                                :model-value="line.quantity"
                                :min="1"
                                size="sm"
                                @update:model-value="
                                    cartActions.update(line.id, $event)
                                "
                            />

                            <p
                                class="text-[13px] font-bold text-[var(--om-navy)]"
                            >
                                {{
                                    formatMoney(
                                        line.unit_price_amount * line.quantity,
                                        currency,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>

            <aside class="mt-6 lg:col-span-5 lg:mt-0">
                <div
                    class="rounded-lg border border-zinc-200 p-4 lg:sticky lg:top-6"
                >
                    <h2 class="om-page-title !text-[14px]">Ringkasan</h2>
                    <div class="mt-3">
                        <CouponField :coupon-code="couponCode" />
                    </div>
                    <dl class="mt-3 flex flex-col gap-2 text-[13px]">
                        <div class="flex justify-between text-zinc-500">
                            <dt>Subtotal {{ taxLabel }}</dt>
                            <dd class="font-medium text-zinc-900">
                                {{
                                    formatMoney(
                                        cartContext?.subtotal ?? 0,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="cartContext && cartContext.discountTotal > 0"
                            class="flex justify-between text-zinc-500"
                        >
                            <dt>Diskon</dt>
                            <dd class="font-medium text-emerald-600">
                                −{{
                                    formatMoney(
                                        cartContext.discountTotal,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between text-zinc-500">
                            <dt>Ongkir</dt>
                            <dd>Di checkout</dd>
                        </div>
                    </dl>
                    <Separator class="mt-3" />
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-[13px] font-bold text-zinc-900"
                            >Total</span
                        >
                        <span class="om-page-title">
                            {{ formatMoney(totalAmount, currency) }}
                        </span>
                    </div>
                    <Button
                        as-child
                        size="xl"
                        class="mt-4 hidden w-full lg:flex"
                    >
                        <Link :href="checkout.index.url()">
                            {{
                                page.props.auth.user
                                    ? 'Bayar'
                                    : 'Masuk untuk bayar'
                            }}
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="link"
                        size="sm"
                        class="mt-2.5 hidden h-auto px-0 text-[12px] font-bold lg:inline-flex"
                    >
                        <Link :href="shop.index.url()"> Lanjut belanja </Link>
                    </Button>
                </div>
            </aside>
        </div>
    </Container>

    <!-- Mobile sticky checkout bar -->
    <div
        v-if="!isEmpty"
        class="fixed inset-x-0 z-40 border-t border-zinc-200 bg-white py-2.5 lg:hidden"
        style="
            bottom: calc(
                var(--om-bottom-nav-height) + env(safe-area-inset-bottom, 0px)
            )
        "
    >
        <Container class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p
                    class="text-zinc-500"
                    :style="{ fontSize: 'var(--om-text-micro)' }"
                >
                    Total {{ taxLabel }}
                </p>
                <p class="om-page-title">
                    {{ formatMoney(totalAmount, currency) }}
                </p>
            </div>
            <Button
                as-child
                size="xl"
                class="inline-flex shrink-0 px-5"
            >
                <Link :href="checkout.index.url()">
                    {{ page.props.auth.user ? 'Bayar' : 'Masuk & bayar' }}
                </Link>
            </Button>
        </Container>
    </div>

    <div
        v-if="!isEmpty"
        class="h-[calc(var(--om-control-height)+1.25rem)] lg:hidden"
        aria-hidden="true"
    />
</template>
