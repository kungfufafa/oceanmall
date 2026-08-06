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

const props = defineProps<{
    order: Order;
    komercePayment?: KomercePaymentInstructions | null;
}>();

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

const needsPayment = computed(() => Boolean(props.komercePayment));

const paymentStatus = computed(() => {
    const status = props.order.payment_status;
    if (typeof status === 'string') return status;
    return null;
});

const paymentSetupFailed = computed(
    () =>
        !needsPayment.value &&
        paymentStatus.value !== undefined &&
        paymentStatus.value !== null &&
        paymentStatus.value !== 'paid',
);

const pageTitle = computed(() => {
    if (needsPayment.value) return 'Selesaikan pembayaran';
    if (paymentSetupFailed.value) return 'Pesanan dibuat';
    return 'Pesanan dibuat';
});

const checkingPayment = ref(false);
let pollTimer: ReturnType<typeof setInterval> | null = null;
let pollCount = 0;

function stopPolling(): void {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function syncPayment(silent = false): void {
    if (!needsPayment.value || checkingPayment.value) return;

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

onMounted(() => {
    if (!needsPayment.value) return;

    pollTimer = setInterval(() => {
        pollCount += 1;
        if (pollCount > 12 || !needsPayment.value) {
            stopPolling();
            return;
        }
        syncPayment(true);
    }, 15000);
});

watch(needsPayment, (needs) => {
    if (!needs) stopPolling();
});

onBeforeUnmount(() => {
    stopPolling();
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
                    <CardHeader class="flex flex-row items-start gap-3 p-4">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-800"
                        >
                            <Clock3 class="size-5" aria-hidden="true" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <CardTitle class="text-base sm:text-lg">
                                Selesaikan pembayaran
                            </CardTitle>
                            <CardDescription>
                                Pesanan
                                <span class="font-semibold text-foreground"
                                    >#{{ order.number }}</span
                                >
                                sudah dibuat. Bayar sekarang supaya langsung
                                diproses.
                            </CardDescription>
                            <Badge variant="warning" class="mt-1 w-fit">
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

                <Button
                    type="button"
                    size="xl"
                    class="mt-4 w-full"
                    :disabled="checkingPayment"
                    @click="syncPayment(false)"
                >
                    {{
                        checkingPayment
                            ? 'Mengecek…'
                            : 'Sudah bayar? Cek status'
                    }}
                </Button>
                <p class="mt-2 text-center text-[11px] text-muted-foreground">
                    Status dicek otomatis tiap 15 detik. Atau ketuk tombol di
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

                <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                    <Button as-child variant="outline" size="xl" class="flex-1">
                        <Link :href="ordersShow.url(order.id)">
                            Lihat pesanan
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        size="xl"
                        class="flex-1 text-muted-foreground hover:text-foreground"
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
                    <CardHeader class="items-center gap-4 p-4">
                        <div
                            class="flex size-14 items-center justify-center rounded-md"
                            :class="
                                paymentSetupFailed
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-emerald-100'
                            "
                        >
                            <Clock3
                                v-if="paymentSetupFailed"
                                class="size-7"
                                aria-hidden="true"
                            />
                            <Check
                                v-else
                                class="size-7 text-emerald-700"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <CardTitle class="text-lg">
                                {{
                                    flashSuccess
                                        ? 'Pembayaran berhasil'
                                        : paymentSetupFailed
                                          ? 'Pesanan dibuat — bayar belum siap'
                                          : 'Pesanan berhasil dibuat'
                                }}
                            </CardTitle>
                            <CardDescription>
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
                                class="mx-auto w-fit"
                            >
                                Pembayaran belum siap
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-4 p-4 pt-0">
                        <Alert v-if="flashSuccess" variant="success">
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
                                Instruksi pembayaran belum tersedia. Buka detail
                                pesanan untuk mencoba bayar lagi.
                                <Link
                                    :href="ordersShow.url(order.id)"
                                    class="mt-2 block font-semibold text-[var(--om-navy)]"
                                >
                                    Bayar di detail pesanan →
                                </Link>
                            </AlertDescription>
                        </Alert>

                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:justify-center"
                        >
                            <Button as-child size="xl">
                                <Link :href="ordersShow.url(order.id)">
                                    Lihat pesanan
                                </Link>
                            </Button>
                            <Button as-child variant="outline" size="xl">
                                <Link :href="shop.index.url()">
                                    Lanjut belanja
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </Container>
</template>
