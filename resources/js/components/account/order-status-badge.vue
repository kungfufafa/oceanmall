<script setup lang="ts">
import { computed } from 'vue';

type StatusType = 'order' | 'payment' | 'shipping';

const props = defineProps<{
    status: string;
    type?: StatusType;
}>();

const colorMap: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    paid: 'bg-green-100 text-green-800',
    authorized: 'bg-green-100 text-green-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    in_transit: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-zinc-100 text-zinc-800',
    partially_paid: 'bg-amber-100 text-amber-800',
    voided: 'bg-zinc-100 text-zinc-800',
    returned: 'bg-orange-100 text-orange-800',
};

const labelMap: Record<string, string> = {
    pending: 'Menunggu',
    confirmed: 'Dikonfirmasi',
    processing: 'Diproses',
    completed: 'Selesai',
    paid: 'Dibayar',
    authorized: 'Otorisasi',
    shipped: 'Dikirim',
    in_transit: 'Dalam perjalanan',
    delivered: 'Terkirim',
    cancelled: 'Dibatalkan',
    failed: 'Gagal',
    refunded: 'Dikembalikan',
    partially_paid: 'Sebagian dibayar',
    voided: 'Dibatalkan',
    returned: 'Dikembalikan',
};

const classes = computed<string>(
    () => colorMap[props.status] ?? 'bg-zinc-100 text-zinc-800',
);

const label = computed<string>(
    () =>
        labelMap[props.status] ??
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
