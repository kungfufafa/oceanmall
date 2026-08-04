<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Check, Clock3 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import KomercePaymentPanel from '@/components/shop/komerce-payment-panel.vue';
import type { KomercePaymentInstructions } from '@/components/shop/komerce-payment-panel.vue';
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
        | { error?: string; info?: string; success?: string }
        | undefined;
    return flash?.error ?? null;
});
const flashInfo = computed(() => {
    const flash = page.props.flash as
        | { error?: string; info?: string; success?: string }
        | undefined;
    return flash?.info ?? null;
});
const flashSuccess = computed(() => {
    const flash = page.props.flash as
        | { error?: string; info?: string; success?: string }
        | undefined;
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
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-800"
                    >
                        <Clock3 class="size-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h1 class="om-page-title !text-base sm:!text-lg">
                            Selesaikan pembayaran
                        </h1>
                        <p class="om-meta mt-1">
                            Pesanan
                            <span class="font-semibold text-zinc-800"
                                >#{{ order.number }}</span
                            >
                            sudah dibuat. Bayar sekarang supaya langsung
                            diproses.
                        </p>
                    </div>
                </div>

                <p
                    v-if="flashError || paymentError"
                    class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[13px] text-amber-900"
                >
                    {{ flashError || paymentError }}
                </p>
                <p
                    v-else-if="flashInfo"
                    class="mt-4 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-[13px] text-zinc-700"
                >
                    {{ flashInfo }}
                </p>

                <div class="mt-5">
                    <KomercePaymentPanel :payment="komercePayment!" />
                </div>

                <button
                    type="button"
                    class="om-btn-primary mt-4 inline-flex w-full items-center justify-center px-4 disabled:opacity-50"
                    :disabled="checkingPayment"
                    @click="syncPayment(false)"
                >
                    {{
                        checkingPayment
                            ? 'Mengecek…'
                            : 'Sudah bayar? Cek status'
                    }}
                </button>
                <p class="om-meta mt-2 text-center !text-[11px]">
                    Status dicek otomatis tiap 15 detik. Atau ketuk tombol di
                    atas setelah transfer/scan.
                </p>

                <p class="om-meta mt-4 text-center !text-[11px]">
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
                    <Link
                        :href="ordersShow.url(order.id)"
                        class="om-btn-outline inline-flex flex-1 items-center justify-center px-4"
                    >
                        Lihat pesanan
                    </Link>
                    <Link
                        :href="shop.index.url()"
                        class="inline-flex flex-1 items-center justify-center px-4 text-[13px] font-semibold text-zinc-500 hover:text-zinc-800"
                    >
                        Belanja lagi
                    </Link>
                </div>
            </template>

            <!-- Paid / no pending instructions -->
            <template v-else>
                <div class="text-center">
                    <div class="flex justify-center">
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
                    </div>

                    <h1 class="om-page-title mt-5 !text-lg">
                        {{
                            flashSuccess
                                ? 'Pembayaran berhasil'
                                : paymentSetupFailed
                                  ? 'Pesanan dibuat — bayar belum siap'
                                  : 'Pesanan berhasil dibuat'
                        }}
                    </h1>
                    <p class="om-meta mt-2">
                        Nomor pesanan
                        <span class="font-semibold text-zinc-800"
                            >#{{ order.number }}</span
                        >
                        ·
                        {{
                            formatMoney(
                                order.price_amount,
                                order.currency_code,
                            )
                        }}
                    </p>

                    <p
                        v-if="flashSuccess"
                        class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-900"
                    >
                        {{ flashSuccess }}
                    </p>

                    <p
                        v-else-if="flashError || paymentSetupFailed"
                        class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-left text-[13px] text-amber-900"
                    >
                        {{
                            flashError ||
                            'Instruksi pembayaran belum tersedia. Buka detail pesanan untuk mencoba bayar lagi.'
                        }}
                        <Link
                            :href="ordersShow.url(order.id)"
                            class="mt-2 block font-semibold text-[var(--om-navy)]"
                        >
                            Bayar di detail pesanan →
                        </Link>
                    </p>

                    <div
                        class="mt-8 flex flex-col gap-2 sm:flex-row sm:justify-center"
                    >
                        <Link
                            :href="ordersShow.url(order.id)"
                            class="om-btn-primary inline-flex items-center justify-center px-5"
                        >
                            Lihat pesanan
                        </Link>
                        <Link
                            :href="shop.index.url()"
                            class="om-btn-outline inline-flex items-center justify-center px-5"
                        >
                            Lanjut belanja
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </Container>
</template>
