<script setup lang="ts">
import { Copy } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

export type KomercePaymentInstructions = {
    payment_id: string;
    payment_type: 'bank_transfer' | 'qris' | string;
    virtual_account_number?: string | null;
    bank_code?: string | null;
    qris_string?: string | null;
    expiry_date?: string | null;
    amount: number;
    currency_code: string;
};

const props = defineProps<{
    payment: KomercePaymentInstructions;
}>();

const copied = ref(false);

function copy(text: string): void {
    navigator.clipboard.writeText(text).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

const isVa = props.payment.payment_type === 'bank_transfer';
const isQris = props.payment.payment_type === 'qris';
</script>

<template>
    <div
        class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 space-y-4 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400"
            >
                Awaiting payment
            </span>
            <span class="text-xs text-zinc-500">Ref: {{ payment.payment_id }}</span>
        </div>

        <template v-if="isVa && payment.virtual_account_number">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-1">
                    {{ payment.bank_code ?? 'Bank' }} Virtual Account
                </p>
                <div class="flex items-center gap-2">
                    <span
                        class="font-mono text-xl font-semibold text-zinc-900 dark:text-white tracking-widest"
                    >
                        {{ payment.virtual_account_number }}
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 w-7 p-0 text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                        :title="copied ? 'Copied!' : 'Copy VA number'"
                        @click="copy(payment.virtual_account_number!)"
                    >
                        <Copy class="size-4" aria-hidden="true" />
                    </Button>
                </div>
                <p v-if="copied" class="mt-1 text-xs text-emerald-600">Copied to clipboard</p>
            </div>

            <ol class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400 list-decimal list-inside">
                <li>Open your {{ payment.bank_code ?? 'bank' }} mobile app or ATM.</li>
                <li>Choose "Transfer / Virtual Account".</li>
                <li>Enter the virtual account number above.</li>
                <li>Confirm the amount and complete the transfer.</li>
            </ol>
        </template>

        <template v-else-if="isQris && payment.qris_string">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 mb-2">
                    QRIS Code
                </p>
                <div class="flex flex-col items-start gap-3">
                    <p class="text-xs text-zinc-500">
                        Scan this QRIS code using your mobile banking or e-wallet app.
                    </p>
                    <div
                        class="rounded-lg bg-white p-3 ring-1 ring-zinc-200 dark:ring-zinc-700"
                    >
                        <p class="break-all font-mono text-xs text-zinc-500 max-w-xs">
                            {{ payment.qris_string }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="copy(payment.qris_string!)"
                    >
                        <Copy class="mr-2 size-3.5" aria-hidden="true" />
                        {{ copied ? 'Copied!' : 'Copy QRIS string' }}
                    </Button>
                </div>
            </div>
        </template>

        <div
            v-if="payment.expiry_date"
            class="flex items-center gap-1.5 text-xs text-zinc-500 border-t border-zinc-200 pt-3 dark:border-zinc-700"
        >
            <span>Pay before:</span>
            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ payment.expiry_date }}</span>
        </div>
    </div>
</template>
