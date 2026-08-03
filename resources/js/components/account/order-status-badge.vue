<script setup lang="ts">
import { computed } from 'vue';
import type { OrderStatus, PaymentStatus, ShippingStatus } from '@/types/shop';

type StatusType = 'order' | 'payment' | 'shipping';

const props = defineProps<{
    status: OrderStatus | PaymentStatus | ShippingStatus;
    type?: StatusType;
}>();

const colorMap: Record<string, string> = {
    pending:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    confirmed:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    processing:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    completed:
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    authorized:
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    shipped:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
    in_transit:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
    delivered:
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    refunded: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
    partially_paid:
        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    voided: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
    returned:
        'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
};

const classes = computed<string>(
    () =>
        colorMap[props.status] ??
        'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
);

const label = computed<string>(() =>
    props.status
        .toString()
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' '),
);
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
            classes,
        ]"
    >
        {{ label }}
    </span>
</template>
