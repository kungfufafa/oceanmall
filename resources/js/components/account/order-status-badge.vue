<script setup lang="ts">
import { computed } from 'vue';
import { Badge  } from '@/components/ui/badge';
import type {BadgeVariants} from '@/components/ui/badge';

type StatusType = 'order' | 'payment' | 'shipping';

const props = defineProps<{
    status: string;
    type?: StatusType;
}>();

const variantMap: Record<string, BadgeVariants['variant']> = {
    pending: 'warning',
    confirmed: 'default',
    processing: 'default',
    completed: 'success',
    paid: 'success',
    authorized: 'success',
    shipped: 'default',
    in_transit: 'default',
    delivered: 'success',
    cancelled: 'destructive',
    failed: 'destructive',
    refunded: 'secondary',
    partially_paid: 'warning',
    voided: 'secondary',
    returned: 'outline',
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
    unfulfilled: 'Belum diproses',
    fulfilled: 'Diproses',
    partially_shipped: 'Dikirim sebagian',
    ready_for_pickup: 'Siap diambil',
};

const variant = computed<BadgeVariants['variant']>(
    () => variantMap[props.status] ?? 'secondary',
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
    <Badge :variant="variant">{{ label }}</Badge>
</template>
