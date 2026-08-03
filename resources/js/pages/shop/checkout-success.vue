<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { orders as accountOrders } from '@/routes/account';
import * as shop from '@/routes/shop';

type OrderStatusLike = string | { value?: string; label?: string } | null;

type Order = {
    id: number;
    number: string;
    price_amount: number;
    currency_code: string;
    status: OrderStatusLike;
};

const props = defineProps<{ order: Order }>();

function statusLabel(status: OrderStatusLike): string {
    if (!status) return '';
    if (typeof status === 'string') {
        return (
            status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')
        );
    }
    if (typeof status === 'object') {
        const raw = status.label ?? status.value ?? '';
        return raw.charAt(0).toUpperCase() + raw.slice(1).replace(/_/g, ' ');
    }
    return '';
}
</script>

<template>
    <Head title="Order confirmed" />

    <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="flex justify-center">
            <div
                class="flex size-16 items-center justify-center rounded-full bg-green-100"
            >
                <Check class="size-8 text-green-600" aria-hidden="true" />
            </div>
        </div>

        <h1
            class="mt-6 font-heading text-3xl font-bold text-zinc-900 dark:text-white"
        >
            Order Confirmed!
        </h1>
        <p class="mt-2 text-zinc-500">
            Thank you for your purchase. Your order number is
            {{ props.order.number }}.
        </p>

        <div
            class="mt-8 rounded-2xl bg-zinc-50 p-6 text-left dark:bg-zinc-800/50"
        >
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                Order Details
            </h2>
            <dl class="mt-4 space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">Order number</dt>
                    <dd
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        {{ props.order.number }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">Total</dt>
                    <dd
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        {{
                            formatMoney(
                                props.order.price_amount,
                                props.order.currency_code,
                            )
                        }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">Status</dt>
                    <dd
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        {{ statusLabel(props.order.status) }}
                    </dd>
                </div>
            </dl>
        </div>

        <div
            class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
        >
            <Link :href="accountOrders.url()">
                <Button>View My Orders</Button>
            </Link>
            <Link :href="shop.index.url()">
                <Button variant="outline">Continue Shopping</Button>
            </Link>
        </div>
    </div>
</template>
