<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
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
    { value: 'all', label: 'Semua' },
    { value: 'not-shipped', label: 'Belum dikirim' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

const activeTab = computed<string>(() => props.filters.tab || 'all');

function changeTab(value: string): void {
    router.get(
        accountOrders.url(),
        { tab: value },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function itemThumbnail(item: OrderItem): string | null {
    return item.product?.thumbnail ?? item.product?.images?.[0]?.url ?? null;
}

function shippingLabel(order: Order): string {
    if (order.shipping_status === 'delivered') return 'Terkirim';
    if (
        order.shipping_status === 'shipped' ||
        order.shipping_status === 'partially_shipped'
    ) {
        return 'Dalam pengiriman';
    }
    if (
        order.shipping_status === 'returned' ||
        order.shipping_status === 'partially_returned'
    ) {
        return 'Dikembalikan';
    }
    if (order.status === 'cancelled') return 'Dibatalkan';
    if (order.status === 'completed') return 'Selesai';
    return 'Diproses';
}
</script>

<template>
    <Head title="Pesanan" />

    <div
        class="flex [scrollbar-width:none] gap-1 overflow-x-auto [&::-webkit-scrollbar]:hidden"
    >
        <Badge
            v-for="tab in tabs"
            :key="tab.value"
            as="button"
            type="button"
            :variant="activeTab === tab.value ? 'default' : 'secondary'"
            :class="
                cn(
                    'shrink-0 cursor-pointer px-3 py-1.5 text-[12px] font-semibold',
                    activeTab !== tab.value &&
                        'bg-muted text-muted-foreground hover:bg-muted/80',
                )
            "
            @click="changeTab(tab.value)"
        >
            {{ tab.label }}
        </Badge>
    </div>

    <EmptyState
        v-if="!orders.data.length"
        title="Belum ada pesanan"
        description="Pesananmu akan muncul di sini setelah checkout."
        :icon="ShoppingBag"
        class="mt-10"
    >
        <template #action>
            <Button as-child size="xl">
                <Link :href="shop.index.url()"> Belanja sekarang </Link>
            </Button>
        </template>
    </EmptyState>

    <ul v-else class="mt-4 flex flex-col gap-3">
        <li v-for="order in orders.data" :key="order.id">
            <Card class="gap-0 overflow-hidden py-0 shadow-none">
                <div
                    class="flex items-center justify-between gap-3 border-b border-border bg-muted/50 px-3.5 py-2.5"
                >
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-foreground">
                            #{{ order.number }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatDate(order.created_at) }} ·
                            {{
                                formatMoney(
                                    order.price_amount,
                                    order.currency_code,
                                )
                            }}
                        </p>
                    </div>
                    <Link
                        :href="ordersShow.url(order.id)"
                        class="om-action-primary shrink-0"
                    >
                        Detail
                    </Link>
                </div>

                <CardContent class="px-3.5 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[13px] font-semibold text-foreground">
                            {{ shippingLabel(order) }}
                        </p>
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

                    <div class="mt-3 flex flex-col gap-2.5">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex gap-3"
                        >
                            <div
                                class="size-14 shrink-0 overflow-hidden rounded-md bg-muted"
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
                                    class="line-clamp-2 text-[13px] font-medium text-foreground"
                                >
                                    {{ item.name }}
                                </Link>
                                <p
                                    v-else
                                    class="line-clamp-2 text-[13px] font-medium text-foreground"
                                >
                                    {{ item.name }}
                                </p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ item.quantity }}×
                                    {{
                                        formatMoney(
                                            item.unit_price_amount,
                                            order.currency_code,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </li>
    </ul>

    <nav
        v-if="orders.last_page > 1"
        class="mt-6 flex justify-center gap-1"
        aria-label="Pagination"
    >
        <Link
            v-for="link in orders.links"
            :key="link.label"
            :href="link.url ?? '#'"
            :class="
                cn(
                    'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-2.5 text-[13px]',
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground',
                    link.url === null && 'pointer-events-none opacity-40',
                )
            "
            v-html="link.label"
        />
    </nav>
</template>
