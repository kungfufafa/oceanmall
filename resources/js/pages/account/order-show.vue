<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import Card from '@/components/shop/card.vue';
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

type TrackingEvent = {
    description: string;
    datetime: string | null;
    location: string | null;
};

type Shipment = {
    id: number;
    inventory_name: string | null;
    status: string;
    awb: string | null;
    tracking_number: string | null;
    carrier: string | null;
    service: string | null;
    cost: number;
    currency: string;
    tracking_history: TrackingEvent[];
};

const props = defineProps<{ order: Order; shipments: Shipment[] }>();

const shippingPrice = props.shipments.length > 0
    ? props.shipments.reduce((sum, s) => sum + s.cost, 0)
    : (props.order.shipping_option?.price ?? 0);
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

function formatShipmentStatus(value: string): string {
    return value
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function shipmentCarrierService(shipment: Shipment): string | null {
    return [shipment.carrier, shipment.service].filter(Boolean).join(' / ') || null;
}

const trackingShipmentId = ref<number | null>(null);
const trackingError = ref<{ id: number; message: string } | null>(null);

function trackShipment(shipment: Shipment): void {
    trackingShipmentId.value = shipment.id;
    trackingError.value = null;
    router.post(
        `/account/orders/${props.order.id}/shipments/${shipment.id}/track`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                trackingError.value = {
                    id: shipment.id,
                    message:
                        errors.tracking ??
                        'Unable to update tracking right now.',
                };
            },
            onFinish: () => {
                trackingShipmentId.value = null;
            },
        },
    );
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

    <div v-if="shipments.length" class="mt-8 overflow-hidden">
        <Card class="!p-0">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-white/10">
                <h3
                    class="font-heading text-sm font-semibold text-zinc-900 dark:text-white"
                >
                    Shipments / Packages
                </h3>
                <p class="mt-1 text-sm text-zinc-500">
                    Track each package in this order.
                </p>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                <div
                    v-for="(shipment, index) in shipments"
                    :key="shipment.id"
                    class="px-5 py-4"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <p
                                class="font-heading text-sm font-medium text-zinc-900 dark:text-white"
                            >
                                Paket {{ index + 1 }}
                                <span v-if="shipment.inventory_name">
                                    · {{ shipment.inventory_name }}
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{
                                    shipmentCarrierService(shipment) ??
                                    'Carrier pending'
                                }}
                            </p>
                        </div>
                        <p
                            class="text-sm font-medium text-zinc-900 dark:text-white"
                        >
                            {{ formatShipmentStatus(shipment.status) }}
                        </p>
                    </div>

                    <dl
                        class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                    >
                        <div>
                            <dt class="text-zinc-500">AWB</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-white">
                                {{ shipment.awb ?? 'Pending label' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Tracking</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-white">
                                {{
                                    shipment.tracking_number ??
                                    shipment.awb ??
                                    'Pending label'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500">Shipping cost</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-white">
                                {{
                                    formatMoney(
                                        shipment.cost,
                                        shipment.currency,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="shipment.awb" class="mt-4">
                        <button
                            type="button"
                            :disabled="trackingShipmentId === shipment.id"
                            class="inline-flex items-center rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            @click="trackShipment(shipment)"
                        >
                            {{
                                trackingShipmentId === shipment.id
                                    ? 'Tracking…'
                                    : 'Track package'
                            }}
                        </button>

                        <p
                            v-if="trackingError?.id === shipment.id"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ trackingError.message }}
                        </p>

                        <ol
                            v-if="shipment.tracking_history.length"
                            class="mt-4 space-y-3 border-l border-zinc-200 pl-4 dark:border-white/10"
                        >
                            <li
                                v-for="(event, eventIndex) in shipment.tracking_history"
                                :key="eventIndex"
                                class="relative"
                            >
                                <span
                                    class="absolute -left-[21px] top-1 size-2 rounded-full bg-zinc-400 dark:bg-zinc-500"
                                />
                                <p class="text-sm text-zinc-900 dark:text-white">
                                    {{ event.description }}
                                </p>
                                <p
                                    v-if="event.datetime || event.location"
                                    class="mt-0.5 text-xs text-zinc-500"
                                >
                                    <span v-if="event.datetime">{{ event.datetime }}</span>
                                    <span v-if="event.datetime && event.location"> · </span>
                                    <span v-if="event.location">{{ event.location }}</span>
                                </p>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </Card>
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
