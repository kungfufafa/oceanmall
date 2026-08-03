<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { orders as accountOrders } from '@/routes/account';
import { show as ordersShow } from '@/routes/account/orders';
import * as shop from '@/routes/shop';

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
    street_address?: string | null;
    street_address_plus?: string | null;
    city?: string | null;
    postal_code?: string | null;
    country_name?: string | null;
};

type Order = {
    id: number;
    number: string;
    created_at: string;
    updated_at: string;
    price_amount: number;
    currency_code: string;
    status: string;
    payment_status: string;
    shipping_status: string;
    shipping_address: Address | null;
    items: OrderItem[];
};

type Paginated<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    orders: Paginated<Order>;
    filters: { tab: string };
}>();

const tabs = [
    { value: 'all', label: 'Orders' },
    { value: 'not-shipped', label: 'Not Yet Shipped' },
    { value: 'cancelled', label: 'Cancelled Orders' },
];

const activeTab = computed<string>(() => props.filters.tab || 'all');

function changeTab(value: string): void {
    router.get(
        accountOrders.url(),
        { tab: value },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function formatDate(value: string, format: 'short' | 'long' = 'long'): string {
    const date = new Date(value);
    return format === 'short'
        ? date.toLocaleDateString('en-US', { month: 'short', day: '2-digit' })
        : date.toLocaleDateString('en-US', {
              month: 'short',
              day: '2-digit',
              year: 'numeric',
          });
}

function itemThumbnail(item: OrderItem): string | null {
    return item.product?.thumbnail ?? item.product?.images?.[0]?.url ?? null;
}

function shippingLabel(order: Order): string {
    const date = formatDate(order.updated_at, 'short');
    if (order.shipping_status === 'delivered') return `Delivered ${date}`;
    if (
        order.shipping_status === 'shipped' ||
        order.shipping_status === 'partially_shipped'
    )
        return `Shipped ${date}`;
    if (
        order.shipping_status === 'returned' ||
        order.shipping_status === 'partially_returned'
    )
        return 'Returned';
    if (order.status === 'cancelled') return 'Cancelled';
    if (order.status === 'completed') return `Completed ${date}`;
    return 'Processing';
}
</script>

<template>
    <Head title="Your Orders" />

    <div class="flex items-center gap-3">
        <h1
            class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
        >
            Your Orders
        </h1>
        <span
            v-if="orders.total > 0"
            class="inline-flex items-center justify-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
        >
            {{ orders.total }}
        </span>
    </div>

    <div class="mt-6 flex items-center justify-between">
        <div
            class="flex items-center gap-1 rounded-lg border border-zinc-200 p-1 dark:border-zinc-700"
        >
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                :class="[
                    'rounded-md px-3 py-1.5 text-sm font-medium transition',
                    activeTab === tab.value
                        ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                ]"
                @click="changeTab(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>
    </div>

    <div
        v-if="!orders.data.length"
        class="mt-12 flex flex-col items-center justify-center text-center"
    >
        <ShoppingBag
            class="size-12 text-zinc-300 dark:text-zinc-600"
            aria-hidden="true"
        />
        <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">
            No orders found
        </h3>
        <p class="mt-1 text-sm text-zinc-500">
            Your orders will appear here once you make a purchase.
        </p>
        <Link :href="shop.index.url()" class="mt-6">
            <Button>Start Shopping</Button>
        </Link>
    </div>

    <template v-else>
        <div class="mt-6 space-y-6">
            <div
                v-for="order in orders.data"
                :key="order.id"
                class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50"
                >
                    <div class="flex flex-wrap items-center gap-8 text-sm">
                        <div>
                            <dt class="text-xs text-zinc-500">Order placed</dt>
                            <dd
                                class="mt-0.5 font-medium text-zinc-900 dark:text-white"
                            >
                                {{ formatDate(order.created_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-500">Total</dt>
                            <dd
                                class="mt-0.5 font-medium text-zinc-900 dark:text-white"
                            >
                                {{
                                    formatMoney(
                                        order.price_amount,
                                        order.currency_code,
                                    )
                                }}
                            </dd>
                        </div>
                        <div v-if="order.shipping_address" class="max-w-xs">
                            <dt class="text-xs text-zinc-500">Ship to</dt>
                            <dd
                                class="mt-0.5 font-medium text-zinc-900 dark:text-white"
                            >
                                <span>{{
                                    order.shipping_address.street_address
                                }}</span>
                                <span class="text-xs font-normal text-zinc-700">
                                    <span
                                        v-if="
                                            order.shipping_address.postal_code
                                        "
                                        >{{
                                            order.shipping_address.postal_code
                                        }}
                                    </span>
                                    <span v-if="order.shipping_address.city">{{
                                        order.shipping_address.city
                                    }}</span>
                                    <span
                                        v-if="
                                            order.shipping_address.country_name
                                        "
                                        >,
                                        {{
                                            order.shipping_address.country_name
                                        }}</span
                                    >
                                </span>
                            </dd>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 text-sm">
                        <span class="font-medium text-zinc-900 dark:text-white"
                            >Order #{{ order.number }}</span
                        >
                        <Link
                            :href="ordersShow.url(order.id)"
                            class="text-sm font-medium text-zinc-900 hover:underline dark:text-white"
                        >
                            View order details
                        </Link>
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h3
                            class="font-heading text-base font-semibold text-zinc-900 dark:text-white"
                        >
                            {{ shippingLabel(order) }}
                        </h3>
                        <template v-if="order.status === 'cancelled'">
                            <OrderStatusBadge
                                :status="order.status"
                                type="order"
                            />
                        </template>
                        <template v-else>
                            <OrderStatusBadge
                                :status="order.payment_status"
                                type="payment"
                            />
                            <OrderStatusBadge
                                :status="order.shipping_status"
                                type="shipping"
                            />
                        </template>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex gap-4"
                        >
                            <div
                                class="size-24 shrink-0 overflow-hidden rounded-lg bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700"
                            >
                                <img
                                    v-if="itemThumbnail(item)"
                                    :src="itemThumbnail(item)!"
                                    :alt="item.name"
                                    loading="lazy"
                                    class="size-full object-cover object-center"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <Link
                                    v-if="item.product?.slug"
                                    :href="
                                        shop.product.url({
                                            product: item.product.slug,
                                        })
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
                                <p
                                    v-if="item.sku"
                                    class="mt-0.5 text-xs text-zinc-500"
                                >
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
                </div>
            </div>
        </div>

        <nav
            v-if="orders.last_page > 1"
            class="mt-8 flex justify-center gap-1"
            aria-label="Pagination"
        >
            <Link
                v-for="link in orders.links"
                :key="link.label"
                :href="link.url ?? '#'"
                :class="[
                    'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition',
                    link.active
                        ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                        : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                    link.url === null && 'pointer-events-none opacity-40',
                ]"
                v-html="link.label"
            />
        </nav>
    </template>
</template>
