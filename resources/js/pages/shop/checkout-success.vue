<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Check, Clock3 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import KomercePaymentPanel from '@/components/shop/komerce-payment-panel.vue';
import type { KomercePaymentInstructions } from '@/components/shop/komerce-payment-panel.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import { show as ordersShow } from '@/routes/account/orders';
import * as shop from '@/routes/shop';

type OrderStatusLike = string | { value?: string; label?: string } | null;

type Order = {
    id: number;
    number: string;
    price_amount: number;
    currency_code: string;
    status: OrderStatusLike;
    payment_status?: string;
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
    status_label?: string | null;
    awb: string | null;
    tracking_number: string | null;
    carrier: string | null;
    service: string | null;
    tracking_history: TrackingEvent[];
};

const props = defineProps<{
    order: Order;
    shipments?: Shipment[];
    komercePayment?: KomercePaymentInstructions | null;
    canRetryPayment?: boolean;
    canCancel?: boolean;
    cancelledReason?: string | null;
    cancelledReasonLabel?: string | null;
}>();

const shipments = computed(() => props.shipments ?? []);

const page = usePage();
const flashError = computed(() => {
    const flash = page.props.flash as
        { error?: string; info?: string; success?: string } | undefined;

    return flash?.error ?? null;
});
const flashInfo = computed(() => {
    const flash = page.props.flash as
        { error?: string; info?: string; success?: string } | undefined;

    return flash?.info ?? null;
});
const flashSuccess = computed(() => {
    const flash = page.props.flash as
        { error?: string; info?: string; success?: string } | undefined;

    return flash?.success ?? null;
});
const paymentError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.payment,
);

function statusValue(status: OrderStatusLike): string | null {
    if (typeof status === 'string') {
        return status;
    }

    return status?.value ?? null;
}

const orderStatus = computed(() => statusValue(props.order.status));
const paymentStatus = computed(() => statusValue(props.order.payment_status ?? null));
const isCancelled = computed(() => orderStatus.value === 'cancelled');

const cancelledReasonLabel = computed(() => {
    if (props.cancelledReasonLabel) {
        return props.cancelledReasonLabel;
    }

    if (!isCancelled.value) {
        return null;
    }

    if (props.cancelledReason === 'Payment expired') {
        return 'Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.';
    }

    if (props.cancelledReason === 'Cancelled by customer') {
        return 'Pesanan dibatalkan oleh Anda.';
    }

    return props.cancelledReason
        ? `Pesanan dibatalkan: ${props.cancelledReason}`
        : 'Pesanan dibatalkan.';
});

const needsPayment = computed(
    () => Boolean(props.komercePayment) && !isCancelled.value,
);

const paymentSetupFailed = computed(
    () =>
        !needsPayment.value &&
        !isCancelled.value &&
        paymentStatus.value !== undefined &&
        paymentStatus.value !== null &&
        paymentStatus.value !== 'paid',
);

const pageTitle = computed(() => {
    if (isCancelled.value) {
        return 'Pesanan dibatalkan';
    }

    if (needsPayment.value) {
        return 'Selesaikan pembayaran';
    }

    if (paymentSetupFailed.value) {
        return 'Pesanan dibuat';
    }

    return 'Pesanan dibuat';
});

const checkingPayment = ref(false);
const retryingPayment = ref(false);
const cancellingOrder = ref(false);
const cancelError = ref<string | null>(null);

function syncPayment(silent = false): void {
    if (!needsPayment.value || checkingPayment.value) {
        return;
    }

    checkingPayment.value = true;
    router.post(
        `/account/orders/${props.order.id}/sync-payment`,
        { silent: silent ? 1 : 0 },
        {
            preserveScroll: true,
            onFinish: () => {
                checkingPayment.value = false;
            },
        },
    );
}

function retryPayment(): void {
    retryingPayment.value = true;
    router.post(
        `/account/orders/${props.order.id}/retry-payment`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                retryingPayment.value = false;
            },
        },
    );
}

function cancelOrder(): void {
    if (!window.confirm('Yakin ingin membatalkan pesanan ini?')) {
        return;
    }

    cancellingOrder.value = true;
    cancelError.value = null;
    router.post(
        `/account/orders/${props.order.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                cancelError.value =
                    errors.cancel ?? 'Pesanan tidak bisa dibatalkan saat ini.';
            },
            onFinish: () => {
                cancellingOrder.value = false;
            },
        },
    );
}

// Same as Vue order-show and Expo: reload the GET every 10s so a captured
// Komerce payment reconciles (and can issue AWB) without a webhook.
const shouldPollPayment = computed(
    () =>
        paymentStatus.value !== 'paid' &&
        !isCancelled.value &&
        Boolean(props.komercePayment),
);

let paymentPollTimer: ReturnType<typeof setInterval> | null = null;

function stopPaymentPoll(): void {
    if (paymentPollTimer) {
        clearInterval(paymentPollTimer);
        paymentPollTimer = null;
    }
}

function startPaymentPoll(): void {
    stopPaymentPoll();

    if (!shouldPollPayment.value) {
        return;
    }

    paymentPollTimer = setInterval(() => {
        if (!shouldPollPayment.value) {
            stopPaymentPoll();

            return;
        }

        router.reload({ preserveScroll: true });
    }, 10_000);
}

onMounted(startPaymentPoll);
onBeforeUnmount(stopPaymentPoll);
watch(shouldPollPayment, (needs) => {
    if (needs) {
        startPaymentPoll();
    } else {
        stopPaymentPoll();
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppPageHeader
        class="lg:hidden"
        :title="pageTitle"
        :back-href="ordersShow.url(order.id)"
        max-width-class="max-w-7xl"
    />

    <Container class="py-8 sm:py-12">
        <div class="mx-auto max-w-xl">
            <!-- Unpaid: payment-first -->
            <template v-if="needsPayment">
                <Card
                    class="gap-0 rounded-md border-border bg-card py-0 text-card-foreground shadow-none"
                >
                    <CardHeader class="flex flex-row items-start gap-4 p-6">
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-800"
                        >
                            <Clock3 class="size-7" aria-hidden="true" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <CardTitle class="text-xl">
                                Selesaikan pembayaran
                            </CardTitle>
                            <CardDescription class="text-base">
                                Pesanan
                                <span class="font-semibold text-foreground"
                                    >#{{ order.number }}</span
                                >
                                sudah dibuat. Bayar sekarang supaya langsung
                                diproses.
                            </CardDescription>
                            <Badge variant="warning" class="mt-2 w-fit">
                                Belum dibayar
                            </Badge>
                        </div>
                    </CardHeader>
                </Card>

                <Alert
                    v-if="flashError || paymentError"
                    variant="destructive"
                    class="mt-4"
                >
                    <AlertDescription class="text-[13px] text-current">
                        {{ flashError || paymentError }}
                    </AlertDescription>
                </Alert>
                <Alert v-else-if="flashInfo" variant="info" class="mt-4">
                    <AlertDescription class="text-[13px] text-current">
                        {{ flashInfo }}
                    </AlertDescription>
                </Alert>

                <div class="mt-5">
                    <KomercePaymentPanel :payment="komercePayment!" />
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <Button
                        type="button"
                        size="xl"
                        class="w-full"
                        :disabled="checkingPayment"
                        @click="syncPayment(false)"
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
                        class="w-full"
                        :disabled="retryingPayment"
                        @click="retryPayment"
                    >
                        {{
                            retryingPayment
                                ? 'Memproses…'
                                : 'Buat ulang pembayaran'
                        }}
                    </Button>
                    <Button
                        v-if="canCancel"
                        type="button"
                        variant="outline"
                        size="xl"
                        class="w-full text-destructive"
                        :disabled="cancellingOrder"
                        @click="cancelOrder"
                    >
                        {{
                            cancellingOrder
                                ? 'Membatalkan…'
                                : 'Batalkan pesanan'
                        }}
                    </Button>
                    <p
                        v-if="cancelError"
                        class="text-center text-sm text-destructive"
                    >
                        {{ cancelError }}
                    </p>
                </div>
                <p class="mt-2 text-center text-[11px] text-muted-foreground">
                    Status dicek otomatis tiap 10 detik. Atau ketuk tombol di
                    atas setelah transfer/scan.
                </p>

                <p class="mt-4 text-center text-[11px] text-muted-foreground">
                    Belum sempat bayar? Instruksi tersimpan di
                    <Link
                        :href="ordersShow.url(order.id)"
                        class="font-semibold text-[var(--om-navy)]"
                    >
                        detail pesanan
                    </Link>
                    .
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <Button as-child variant="outline" size="xl" class="flex-1">
                        <Link :href="ordersShow.url(order.id)">
                            Lihat pesanan
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        size="xl"
                        class="flex-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Link :href="shop.index.url()">Belanja lagi</Link>
                    </Button>
                </div>
            </template>

            <!-- Paid / no pending instructions -->
            <template v-else>
                <Card
                    class="gap-0 rounded-md border-border bg-card py-0 text-center text-card-foreground shadow-none"
                >
                    <CardHeader class="items-center gap-4 p-6 sm:p-8">
                        <div
                            class="flex size-16 items-center justify-center rounded-full"
                            :class="
                                isCancelled || paymentSetupFailed
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-emerald-100'
                            "
                        >
                            <Clock3
                                v-if="isCancelled || paymentSetupFailed"
                                class="size-8"
                                aria-hidden="true"
                            />
                            <Check
                                v-else
                                class="size-8 text-emerald-700"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <CardTitle class="text-xl sm:text-2xl">
                                {{
                                    isCancelled
                                        ? 'Pesanan dibatalkan'
                                        : flashSuccess
                                          ? 'Pembayaran berhasil'
                                          : paymentSetupFailed
                                            ? 'Pesanan dibuat — bayar belum siap'
                                            : 'Pesanan berhasil dibuat'
                                }}
                            </CardTitle>
                            <CardDescription class="text-base">
                                Nomor pesanan
                                <span class="font-semibold text-foreground"
                                    >#{{ order.number }}</span
                                >
                                ·
                                {{
                                    formatMoney(
                                        order.price_amount,
                                        order.currency_code,
                                    )
                                }}
                            </CardDescription>
                            <Badge
                                v-if="paymentSetupFailed"
                                variant="warning"
                                class="mx-auto mt-1 w-fit"
                            >
                                Pembayaran belum siap
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-4 p-6 pt-0 sm:p-8 sm:pt-0">
                        <Alert v-if="cancelledReasonLabel" variant="info">
                            <AlertDescription class="text-[13px] text-current">
                                {{ cancelledReasonLabel }}
                            </AlertDescription>
                        </Alert>

                        <Alert v-else-if="flashSuccess" variant="success">
                            <AlertDescription class="text-[13px] text-current">
                                {{ flashSuccess }}
                            </AlertDescription>
                        </Alert>

                        <Alert
                            v-else-if="flashError"
                            variant="destructive"
                            class="text-left"
                        >
                            <AlertDescription class="text-[13px] text-current">
                                {{ flashError }}
                                <Link
                                    :href="ordersShow.url(order.id)"
                                    class="mt-2 block font-semibold text-[var(--om-navy)]"
                                >
                                    Bayar di detail pesanan →
                                </Link>
                            </AlertDescription>
                        </Alert>

                        <Alert
                            v-else-if="paymentSetupFailed"
                            variant="warning"
                            class="text-left"
                        >
                            <AlertDescription class="text-[13px] text-current">
                                Instruksi pembayaran belum tersedia. Ketuk
                                tombol di bawah untuk membuat pembayaran baru,
                                atau buka detail pesanan.
                            </AlertDescription>
                        </Alert>

                        <Button
                            v-if="canRetryPayment && !isCancelled"
                            type="button"
                            size="xl"
                            :disabled="retryingPayment"
                            @click="retryPayment"
                        >
                            {{
                                retryingPayment
                                    ? 'Memproses…'
                                    : 'Bayar sekarang'
                            }}
                        </Button>
                        <Button
                            v-if="canCancel && !isCancelled"
                            type="button"
                            variant="outline"
                            size="xl"
                            class="text-destructive"
                            :disabled="cancellingOrder"
                            @click="cancelOrder"
                        >
                            {{
                                cancellingOrder
                                    ? 'Membatalkan…'
                                    : 'Batalkan pesanan'
                            }}
                        </Button>
                        <p
                            v-if="cancelError"
                            class="text-sm text-destructive"
                        >
                            {{ cancelError }}
                        </p>

                        <div
                            class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-center"
                        >
                            <Button as-child size="xl" class="sm:px-8">
                                <Link :href="ordersShow.url(order.id)">
                                    Lihat pesanan
                                </Link>
                            </Button>
                            <Button
                                as-child
                                variant="outline"
                                size="xl"
                                class="sm:px-8"
                            >
                                <Link :href="shop.index.url()">
                                    Lanjut belanja
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="shipments.length"
                    class="mt-5 gap-0 rounded-md border-border bg-card py-0 text-left text-card-foreground shadow-none"
                >
                    <CardHeader class="p-6 pb-3">
                        <CardTitle class="text-base">Pengiriman</CardTitle>
                        <CardDescription>
                            Riwayat resi yang sama dengan detail pesanan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5 p-6 pt-0">
                        <div
                            v-for="shipment in shipments"
                            :key="shipment.id"
                            class="border-t border-border pt-4 first:border-t-0 first:pt-0"
                        >
                            <p class="text-sm font-medium text-foreground">
                                {{
                                    [shipment.carrier, shipment.service]
                                        .filter(Boolean)
                                        .join(' / ') || 'Kurir menunggu'
                                }}
                                <span
                                    v-if="shipment.inventory_name"
                                    class="font-normal text-muted-foreground"
                                >
                                    · {{ shipment.inventory_name }}
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ shipment.status_label ?? shipment.status }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    shipment.awb ||
                                    shipment.tracking_number ||
                                    'Label menunggu'
                                }}
                            </p>
                            <ol
                                v-if="shipment.tracking_history.length"
                                class="mt-3 flex flex-col gap-2 border-l border-border pl-4"
                            >
                                <li
                                    v-for="(event, eventIndex) in shipment.tracking_history"
                                    :key="eventIndex"
                                >
                                    <p class="text-sm text-foreground">
                                        {{ event.description }}
                                    </p>
                                    <p
                                        v-if="event.datetime || event.location"
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        <span v-if="event.datetime">{{
                                            event.datetime
                                        }}</span>
                                        <span
                                            v-if="
                                                event.datetime && event.location
                                            "
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
                    </CardContent>
                </Card>
            </template>
        </div>
    </Container>
</template>
