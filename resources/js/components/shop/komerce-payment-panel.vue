<script setup lang="ts">
import { Copy } from 'lucide-vue-next';
import QRCode from 'qrcode';
import { computed, onMounted, ref, watch } from 'vue';
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

export type KomercePaymentInstructions = {
    payment_id: string;
    payment_type: 'bank_transfer' | 'qris' | string;
    virtual_account_number?: string | null;
    bank_code?: string | null;
    qris_string?: string | null;
    payment_url?: string | null;
    expiry_date?: string | null;
    amount: number;
    currency_code: string;
};

const props = defineProps<{
    payment: KomercePaymentInstructions;
}>();

const copied = ref(false);
const qrDataUrl = ref<string | null>(null);
const qrError = ref(false);

const isVa = computed(() => props.payment.payment_type === 'bank_transfer');
const isQris = computed(() => props.payment.payment_type === 'qris');

const methodLabel = computed(() => {
    if (isVa.value) {
        return `Transfer VA ${props.payment.bank_code ?? ''}`.trim();
    }

    if (isQris.value) {
return 'QRIS';
}

    return 'Pembayaran';
});

const formattedExpiry = computed(() => {
    if (!props.payment.expiry_date) {
return null;
}

    const date = new Date(props.payment.expiry_date);

    if (Number.isNaN(date.getTime())) {
        return String(props.payment.expiry_date);
    }

    return date.toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

async function renderQr(value: string | null | undefined): Promise<void> {
    qrDataUrl.value = null;
    qrError.value = false;

    if (!value) {
return;
}

    try {
        qrDataUrl.value = await QRCode.toDataURL(value, {
            width: 220,
            margin: 1,
            errorCorrectionLevel: 'M',
        });
    } catch {
        qrError.value = true;
    }
}

function copy(text: string): void {
    navigator.clipboard.writeText(text).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

onMounted(() => {
    if (isQris.value) {
        void renderQr(props.payment.qris_string);
    }
});

watch(
    () => props.payment.qris_string,
    (value) => {
        if (isQris.value) {
            void renderQr(value);
        }
    },
);
</script>

<template>
    <Card
        class="gap-0 overflow-hidden rounded-md border-border bg-card py-0 text-card-foreground shadow-none"
    >
        <CardHeader class="gap-2 border-b border-border bg-muted p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-col gap-0.5">
                    <CardDescription
                        class="text-[11px] font-semibold tracking-wide uppercase"
                    >
                        Total dibayar
                    </CardDescription>
                    <CardTitle class="text-2xl font-bold text-[var(--om-navy)]">
                        {{ formatMoney(payment.amount, payment.currency_code) }}
                    </CardTitle>
                </div>
                <Badge variant="warning" class="shrink-0 rounded-md">
                    Belum dibayar
                </Badge>
            </div>
            <p v-if="formattedExpiry" class="text-[12px] text-muted-foreground">
                Bayar sebelum
                <span class="font-semibold text-foreground">{{
                    formattedExpiry
                }}</span>
            </p>
        </CardHeader>

        <CardContent class="flex flex-col gap-4 p-4">
            <p class="text-[13px] font-semibold text-foreground">
                Cara bayar · {{ methodLabel }}
            </p>

            <template v-if="isVa && payment.virtual_account_number">
                <div class="flex flex-col gap-1.5">
                    <p class="text-[11px] font-medium text-muted-foreground">
                        Nomor Virtual Account
                    </p>
                    <Card
                        class="gap-0 rounded-md border-border bg-muted py-0 shadow-none"
                    >
                        <CardContent
                            class="flex items-center justify-between gap-2 p-3"
                        >
                            <span
                                class="font-mono text-lg font-bold tracking-wider text-[var(--om-navy)] sm:text-xl"
                            >
                                {{ payment.virtual_account_number }}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-9 shrink-0 text-[12px]"
                                @click="copy(payment.virtual_account_number!)"
                            >
                                <Copy class="size-3.5" aria-hidden="true" />
                                {{ copied ? 'Tersalin' : 'Salin' }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <ol
                    class="flex flex-col gap-2 text-[13px] text-muted-foreground"
                >
                    <li class="flex gap-2">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded-md bg-[var(--om-navy)] text-[10px] font-bold text-white"
                            >1</span
                        >
                        Buka m-banking / ATM {{ payment.bank_code ?? 'bank' }}
                    </li>
                    <li class="flex gap-2">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded-md bg-[var(--om-navy)] text-[10px] font-bold text-white"
                            >2</span
                        >
                        Pilih Transfer → Virtual Account
                    </li>
                    <li class="flex gap-2">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded-md bg-[var(--om-navy)] text-[10px] font-bold text-white"
                            >3</span
                        >
                        Tempel nomor VA, konfirmasi nominal, lalu bayar
                    </li>
                </ol>

                <div v-if="payment.payment_url" class="mt-1">
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        class="h-9 w-full text-xs"
                    >
                        <a
                            :href="payment.payment_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Buka Halaman Bayar Online (Komerce)
                        </a>
                    </Button>
                </div>
            </template>

            <template v-else-if="isQris && payment.qris_string">
                <Card
                    class="gap-0 rounded-md border-border bg-muted py-0 shadow-none"
                >
                    <CardContent class="flex flex-col items-center gap-3 p-4">
                        <img
                            v-if="qrDataUrl"
                            :src="qrDataUrl"
                            alt="Kode QRIS"
                            class="size-[220px] rounded-md bg-white"
                            width="220"
                            height="220"
                        />
                        <p
                            v-else-if="qrError"
                            class="text-center text-[12px] text-red-600"
                        >
                            QR gagal dimuat. Salin kode di bawah.
                        </p>
                        <p v-else class="text-[12px] text-muted-foreground">
                            Menyiapkan QR…
                        </p>
                        <p
                            class="text-center text-[12px] text-muted-foreground"
                        >
                            Scan pakai GoPay, OVO, Dana, ShopeePay, atau
                            m-banking
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-[12px] font-semibold text-[var(--om-navy)] hover:text-[var(--om-navy)]"
                            @click="copy(payment.qris_string!)"
                        >
                            {{ copied ? 'Kode tersalin' : 'Salin kode QRIS' }}
                        </Button>
                    </CardContent>
                </Card>
            </template>

            <Card
                v-else-if="payment.payment_url"
                class="gap-0 rounded-md border-border bg-muted py-0 shadow-none"
            >
                <CardContent class="flex flex-col gap-3 p-4">
                    <p class="text-[13px] text-foreground">
                        Instruksi lengkap tersedia di halaman pembayaran
                        Komerce.
                    </p>
                    <Button as-child size="xl">
                        <a
                            :href="payment.payment_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Buka halaman bayar
                        </a>
                    </Button>
                </CardContent>
            </Card>

            <Alert v-else variant="warning" class="py-2">
                <AlertDescription class="text-[12px] text-current">
                    Instruksi pembayaran belum lengkap. Coba buat ulang dari
                    halaman pesanan.
                </AlertDescription>
            </Alert>

            <p class="text-[10px] text-muted-foreground">
                Ref {{ payment.payment_id }}
            </p>
        </CardContent>
    </Card>
</template>
