<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Card from '@/components/shop/card.vue';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import { formatMoney } from '@/lib/format';
import { dashboard } from '@/routes';
import { orders as accountOrders } from '@/routes/account';
import * as shop from '@/routes/shop';

type OrderShipping = {
    price?: number | null;
    carrier?: { name?: string | null } | null;
};

type OrderItem = {
    id: number;
    name: string;
    sku: string | null;
    quantity: number;
    unit_price_amount: number;
    product?: {
        slug?: string;
        thumbnail?: string | null;
        images?: Array<{ url: string }>;
    } | null;
};

type Address = {
    full_name?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    street_address?: string | null;
    street_address_plus?: string | null;
    city: string;
    postal_code: string;
    country?: { name?: string } | null;
    country_name?: string | null;
};

type Order = {
    id: number;
    number: string;
    created_at: string;
    status: string;
    payment_status: string;
    shipping_status: string;
    price_amount: number;
    tax_amount: number | null;
    currency_code: string;
    items: OrderItem[];
    shipping_address: Address | null;
    shipping_option?: OrderShipping | null;
};

const props = defineProps<{ order: Order }>();

const shippingPrice = props.order.shipping_option?.price ?? 0;
const itemsTotal =
    props.order.price_amount - (props.order.tax_amount ?? 0) - shippingPrice;

function thumbnail(item: OrderItem): string | null {
    return item.product?.thumbnail ?? item.product?.images?.[0]?.url ?? null;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <Head :title="`Order ${order.number}`" />

    <nav class="flex items-center gap-2 text-sm text-zinc-500">
        <Link
            :href="dashboard.url()"
            class="hover:text-zinc-900 dark:hover:text-white"
            >Account</Link
        >
        <span>/</span>
        <Link
            :href="accountOrders.url()"
            class="hover:text-zinc-900 dark:hover:text-white"
            >Orders</Link
        >
        <span>/</span>
        <span class="text-zinc-900 dark:text-white">Order details</span>
    </nav>

    <div class="mt-6">
        <h1
            class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
        >
            Order details
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            Ordered on {{ formatDate(order.created_at) }}
            <span class="mx-2">|</span>
            Order #{{ order.number }}
        </p>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <template v-if="order.status === 'cancelled'">
            <OrderStatusBadge :status="order.status" type="order" />
        </template>
        <template v-else>
            <OrderStatusBadge :status="order.payment_status" type="payment" />
            <OrderStatusBadge :status="order.shipping_status" type="shipping" />
        </template>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div v-if="order.shipping_address">
            <Card>
                <h3
                    class="font-heading text-sm font-semibold text-zinc-900 dark:text-white"
                >
                    Shipping address
                </h3>
                <address class="mt-3 text-sm text-zinc-500 not-italic">
                    <p class="font-medium text-zinc-900 dark:text-white">
                        {{
                            order.shipping_address.full_name ??
                            `${order.shipping_address.first_name ?? ''} ${order.shipping_address.last_name ?? ''}`.trim()
                        }}
                    </p>
                    <p>{{ order.shipping_address.street_address }}</p>
                    <p v-if="order.shipping_address.street_address_plus">
                        {{ order.shipping_address.street_address_plus }}
                    </p>
                    <p>
                        {{ order.shipping_address.city }}
                        {{ order.shipping_address.postal_code }}
                    </p>
                    <p
                        v-if="
                            order.shipping_address.country?.name ||
                            order.shipping_address.country_name
                        "
                    >
                        {{
                            order.shipping_address.country?.name ??
                            order.shipping_address.country_name
                        }}
                    </p>
                </address>
            </Card>
        </div>

        <div class="lg:col-span-2">
            <Card>
                <h3
                    class="font-heading text-sm font-semibold text-zinc-900 dark:text-white"
                >
                    Order summary
                </h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Items</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            {{ formatMoney(itemsTotal, order.currency_code) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">
                            Delivery
                            <span
                                v-if="order.shipping_option?.carrier?.name"
                                class="text-zinc-400"
                                >({{
                                    order.shipping_option.carrier.name
                                }})</span
                            >
                        </dt>
                        <dd class="text-zinc-900 dark:text-white">
                            {{
                                shippingPrice > 0
                                    ? formatMoney(
                                          shippingPrice,
                                          order.currency_code,
                                      )
                                    : 'Free'
                            }}
                        </dd>
                    </div>
                    <div
                        v-if="(order.tax_amount ?? 0) > 0"
                        class="flex justify-between"
                    >
                        <dt class="text-zinc-500">Tax</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            {{
                                formatMoney(
                                    order.tax_amount!,
                                    order.currency_code,
                                )
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700"
                    >
                        <dt class="font-semibold text-zinc-900 dark:text-white">
                            Total
                        </dt>
                        <dd class="font-semibold text-zinc-900 dark:text-white">
                            {{
                                formatMoney(
                                    order.price_amount,
                                    order.currency_code,
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </Card>
        </div>
    </div>

    <div class="mt-8 overflow-hidden">
        <Card class="!p-0">
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex gap-4 px-5 py-4"
                >
                    <div
                        class="size-24 shrink-0 overflow-hidden rounded-lg bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700"
                    >
                        <img
                            v-if="thumbnail(item)"
                            :src="thumbnail(item)!"
                            :alt="item.name"
                            loading="lazy"
                            class="size-full object-cover object-center"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link
                            v-if="item.product?.slug"
                            :href="
                                shop.product.url({ product: item.product.slug })
                            "
                            class="line-clamp-2 font-heading text-sm font-medium text-zinc-900 hover:underline dark:text-white"
                        >
                            {{ item.name }}
                        </Link>
                        <p
                            v-else
                            class="line-clamp-2 font-heading text-sm font-medium text-zinc-900 dark:text-white"
                        >
                            {{ item.name }}
                        </p>
                        <p v-if="item.sku" class="mt-0.5 text-xs text-zinc-500">
                            SKU: {{ item.sku }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Qty: {{ item.quantity }} ·
                            {{
                                formatMoney(
                                    item.unit_price_amount,
                                    order.currency_code,
                                )
                            }}
                        </p>
                    </div>
                    <p
                        class="shrink-0 text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        {{
                            formatMoney(
                                item.unit_price_amount * item.quantity,
                                order.currency_code,
                            )
                        }}
                    </p>
                </div>
            </div>
        </Card>
    </div>
</template>
