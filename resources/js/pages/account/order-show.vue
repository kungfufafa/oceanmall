<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import KomercePaymentPanel from '@/components/shop/komerce-payment-panel.vue';
import type { KomercePaymentInstructions } from '@/components/shop/komerce-payment-panel.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
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

const props = defineProps<{
    order: Order;
    shipments: Shipment[];
    komercePayment?: KomercePaymentInstructions | null;
    canRetryPayment?: boolean;
}>();

const page = usePage();
const paymentError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.payment,
);
const retryingPayment = ref(false);
const checkingPayment = ref(false);
const shippingPrice = props.shipments.length > 0
    ? props.shipments.reduce((sum, s) => sum + s.cost, 0)
    : (props.order.shipping_option?.price ?? 0);
const itemsTotal =
    props.order.price_amount - (props.order.tax_amount ?? 0) - shippingPrice;

const flashInfo = computed(() => {
    const flash = page.props.flash as
        | { info?: string; success?: string }
        | undefined;
    return flash?.info ?? null;
});
const flashSuccess = computed(() => {
    const flash = page.props.flash as
        | { info?: string; success?: string }
        | undefined;
    return flash?.success ?? null;
});

function thumbnail(item: OrderItem): string | null {
    return item.product?.thumbnail ?? item.product?.images?.[0]?.url ?? null;
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
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
const confirmingReceived = ref(false);
const receivedError = ref<string | null>(null);

const canConfirmReceived =
    props.order.status !== 'cancelled' &&
    props.order.status !== 'completed' &&
    props.order.payment_status === 'paid' &&
    props.shipments.some((shipment) => Boolean(shipment.awb || shipment.tracking_number));

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
                        'Pelacakan belum bisa diperbarui saat ini.',
                };
            },
            onFinish: () => {
                trackingShipmentId.value = null;
            },
        },
    );
}

function confirmReceived(): void {
    confirmingReceived.value = true;
    receivedError.value = null;
    router.post(
        `/account/orders/${props.order.id}/confirm-received`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                receivedError.value =
                    errors.received ??
                    'Konfirmasi penerimaan belum bisa diproses saat ini.';
            },
            onFinish: () => {
                confirmingReceived.value = false;
            },
        },
    );
}

function retryPayment(): void {
    retryingPayment.value = true;
    router.post(`/account/orders/${props.order.id}/retry-payment`, {}, {
        preserveScroll: true,
        onFinish: () => {
            retryingPayment.value = false;
        },
    });
}

function syncPayment(): void {
    checkingPayment.value = true;
    router.post(`/account/orders/${props.order.id}/sync-payment`, {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingPayment.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Pesanan ${order.number}`" />

    <nav class="om-meta flex items-center gap-2">
        <Link
            :href="dashboard.url()"
            class="hover:text-[var(--om-navy)]"
            >Akun</Link
        >
        <span>/</span>
        <Link
            :href="accountOrders.url()"
            class="hover:text-[var(--om-navy)]"
            >Pesanan</Link
        >
        <span>/</span>
        <span class="text-[var(--om-navy)]">Detail pesanan</span>
    </nav>

    <div class="mt-6">
        <h1 class="om-page-title">Detail pesanan</h1>
        <p class="om-meta mt-1">
            Dipesan {{ formatDate(order.created_at) }}
            <span class="mx-2">|</span>
            Pesanan #{{ order.number }}
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

    <Alert
        v-if="flashSuccess && !(komercePayment || canRetryPayment)"
        variant="success"
        class="mt-5"
    >
        <AlertDescription>{{ flashSuccess }}</AlertDescription>
    </Alert>

    <Card
        v-if="komercePayment || canRetryPayment"
        class="mt-5 gap-0 overflow-hidden py-0 shadow-none"
    >
        <CardHeader class="border-b border-[var(--om-warning)]/20 bg-[var(--om-warning-soft)]">
            <CardTitle class="text-[13px] text-[var(--om-warning)]">
                Menunggu pembayaran
            </CardTitle>
            <CardDescription class="text-[11px] text-[var(--om-warning)]/80">
                Bayar dulu supaya pesanan bisa diproses & dikirim.
            </CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-3 p-3.5">
            <Alert v-if="paymentError" variant="destructive" class="py-2 text-[12px]">
                <AlertDescription>{{ paymentError }}</AlertDescription>
            </Alert>
            <Alert
                v-else-if="flashInfo"
                variant="info"
                class="py-2 text-[12px]"
            >
                <AlertDescription>{{ flashInfo }}</AlertDescription>
            </Alert>
            <Alert
                v-else-if="flashSuccess"
                variant="success"
                class="py-2 text-[12px]"
            >
                <AlertDescription>{{ flashSuccess }}</AlertDescription>
            </Alert>
            <KomercePaymentPanel
                v-if="komercePayment"
                :payment="komercePayment"
            />
            <p
                v-else-if="canRetryPayment"
                class="text-[13px] text-muted-foreground"
            >
                Instruksi pembayaran belum siap. Ketuk tombol di bawah untuk
                membuatnya.
            </p>
            <div class="flex flex-col gap-2 sm:flex-row">
                <Button
                    v-if="komercePayment"
                    type="button"
                    size="xl"
                    class="flex-1"
                    :disabled="checkingPayment"
                    @click="syncPayment"
                >
                    {{
                        checkingPayment
                            ? 'Mengecek…'
                            : 'Sudah bayar? Cek status'
                    }}
                </Button>
                <Button
                    v-if="canRetryPayment"
                    type="button"
                    variant="outline"
                    size="xl"
                    class="flex-1"
                    :disabled="retryingPayment"
                    @click="retryPayment"
                >
                    {{
                        retryingPayment
                            ? 'Memproses…'
                            : komercePayment
                              ? 'Buat ulang pembayaran'
                              : 'Bayar sekarang'
                    }}
                </Button>
            </div>
        </CardContent>
    </Card>

    <div v-if="canConfirmReceived" class="mt-4">
        <Button
            type="button"
            size="xl"
            :disabled="confirmingReceived"
            @click="confirmReceived"
        >
            {{ confirmingReceived ? 'Memproses…' : 'Tandai sudah diterima' }}
        </Button>
        <p v-if="receivedError" class="mt-2 text-sm text-destructive">
            {{ receivedError }}
        </p>
    </div>

    <div
        v-else-if="order.status === 'completed'"
        class="om-meta mt-4"
    >
        Pesanan selesai — terima kasih.
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <Card
            v-if="order.shipping_address"
            class="gap-0 py-0 shadow-none"
        >
            <CardHeader class="p-4 pb-0">
                <CardTitle class="om-page-title !text-sm">
                    Alamat pengiriman
                </CardTitle>
            </CardHeader>
            <CardContent class="p-4 pt-3">
                <address class="om-meta not-italic">
                    <p class="font-medium text-[var(--om-navy)]">
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
            </CardContent>
        </Card>

        <Card class="gap-0 py-0 shadow-none lg:col-span-2">
            <CardHeader class="p-4 pb-0">
                <CardTitle class="om-page-title !text-sm">
                    Ringkasan pesanan
                </CardTitle>
            </CardHeader>
            <CardContent class="p-4 pt-3">
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Produk</dt>
                        <dd class="text-[var(--om-navy)]">
                            {{ formatMoney(itemsTotal, order.currency_code) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">
                            Ongkir
                            <span
                                v-if="order.shipping_option?.carrier?.name"
                                class="text-muted-foreground/70"
                                >({{
                                    order.shipping_option.carrier.name
                                }})</span
                            >
                        </dt>
                        <dd class="text-[var(--om-navy)]">
                            {{
                                shippingPrice > 0
                                    ? formatMoney(
                                          shippingPrice,
                                          order.currency_code,
                                      )
                                    : 'Gratis'
                            }}
                        </dd>
                    </div>
                    <div
                        v-if="(order.tax_amount ?? 0) > 0"
                        class="flex justify-between"
                    >
                        <dt class="text-muted-foreground">Pajak</dt>
                        <dd class="text-[var(--om-navy)]">
                            {{
                                formatMoney(
                                    order.tax_amount!,
                                    order.currency_code,
                                )
                            }}
                        </dd>
                    </div>
                    <Separator />
                    <div class="flex justify-between pt-2">
                        <dt class="font-semibold text-[var(--om-navy)]">Total</dt>
                        <dd class="font-semibold text-[var(--om-navy)]">
                            {{
                                formatMoney(
                                    order.price_amount,
                                    order.currency_code,
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>

    <Card
        v-if="shipments.length"
        class="mt-8 gap-0 overflow-hidden py-0 shadow-none"
    >
        <CardHeader class="border-b border-border px-5 py-4">
            <CardTitle class="om-page-title !text-sm">
                Pengiriman / Paket
            </CardTitle>
            <CardDescription class="om-meta mt-1">
                Lacak setiap paket dalam pesanan ini.
            </CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-0 p-0">
            <div
                v-for="(shipment, index) in shipments"
                :key="shipment.id"
                class="border-b border-border px-5 py-4 last:border-b-0"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-[var(--om-navy)]">
                            Paket {{ index + 1 }}
                            <span v-if="shipment.inventory_name">
                                · {{ shipment.inventory_name }}
                            </span>
                        </p>
                        <p class="om-meta mt-1">
                            {{
                                shipmentCarrierService(shipment) ??
                                'Kurir menunggu'
                            }}
                        </p>
                    </div>
                    <p class="text-sm font-medium text-[var(--om-navy)]">
                        {{ formatShipmentStatus(shipment.status) }}
                    </p>
                </div>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-muted-foreground">AWB</dt>
                        <dd class="mt-1 text-[var(--om-navy)]">
                            {{ shipment.awb ?? 'Label menunggu' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Nomor resi</dt>
                        <dd class="mt-1 text-[var(--om-navy)]">
                            {{
                                shipment.tracking_number ??
                                shipment.awb ??
                                'Label menunggu'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Biaya ongkir</dt>
                        <dd class="mt-1 text-[var(--om-navy)]">
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
                    <Button
                        type="button"
                        :disabled="trackingShipmentId === shipment.id"
                        variant="outline"
                        size="default"
                        class="px-3 text-sm"
                        @click="trackShipment(shipment)"
                    >
                        {{
                            trackingShipmentId === shipment.id
                                ? 'Melacak…'
                                : 'Lacak paket'
                        }}
                    </Button>

                    <p
                        v-if="trackingError?.id === shipment.id"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ trackingError.message }}
                    </p>

                    <ol
                        v-if="shipment.tracking_history.length"
                        class="mt-4 flex flex-col gap-3 border-l border-border pl-4"
                    >
                        <li
                            v-for="(event, eventIndex) in shipment.tracking_history"
                            :key="eventIndex"
                            class="relative"
                        >
                            <span
                                class="absolute -left-[21px] top-1 size-2 rounded-full bg-muted-foreground/50"
                            />
                            <p class="text-sm text-[var(--om-navy)]">
                                {{ event.description }}
                            </p>
                            <p
                                v-if="event.datetime || event.location"
                                class="om-meta mt-0.5 !text-xs"
                            >
                                <span v-if="event.datetime">{{
                                    event.datetime
                                }}</span>
                                <span
                                    v-if="event.datetime && event.location"
                                >
                                    ·
                                </span>
                                <span v-if="event.location">{{
                                    event.location
                                }}</span>
                            </p>
                        </li>
                    </ol>
                </div>
            </div>
        </CardContent>
    </Card>

    <Card class="mt-8 gap-0 overflow-hidden py-0 shadow-none">
        <CardContent class="flex flex-col gap-0 p-0">
            <div
                v-for="item in order.items"
                :key="item.id"
                class="flex gap-4 border-b border-border px-5 py-4 last:border-b-0"
            >
                <div
                    class="size-24 shrink-0 overflow-hidden rounded-md bg-muted ring-1 ring-border"
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
                        class="line-clamp-2 text-sm font-medium text-[var(--om-navy)] hover:underline"
                    >
                        {{ item.name }}
                    </Link>
                    <p
                        v-else
                        class="line-clamp-2 text-sm font-medium text-[var(--om-navy)]"
                    >
                        {{ item.name }}
                    </p>
                    <p v-if="item.sku" class="om-meta mt-0.5 !text-xs">
                        SKU: {{ item.sku }}
                    </p>
                    <p class="om-meta mt-1">
                        Jml: {{ item.quantity }} ·
                        {{
                            formatMoney(
                                item.unit_price_amount,
                                order.currency_code,
                            )
                        }}
                    </p>
                </div>
                <p class="shrink-0 text-sm font-medium text-[var(--om-navy)]">
                    {{
                        formatMoney(
                            item.unit_price_amount * item.quantity,
                            order.currency_code,
                        )
                    }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
