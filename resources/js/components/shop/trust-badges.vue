<script setup lang="ts">
import { MessageCircle, RotateCcw, ShieldCheck, Truck } from 'lucide-vue-next';

const badges = [
    {
        icon: Truck,
        title: 'Pengiriman cepat',
        description: 'Ke seluruh Indonesia',
    },
    {
        icon: ShieldCheck,
        title: '100% original',
        description: 'Bergaransi resmi',
    },
    {
        icon: RotateCcw,
        title: 'Mudah retur',
        description: 'Kebijakan 30 hari',
    },
    {
        icon: MessageCircle,
        title: 'Bantuan siap',
        description: 'Dukungan pelanggan',
    },
];
</script>

<template>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-6">
        <div
            v-for="badge in badges"
            :key="badge.title"
            class="flex flex-col items-center rounded-md border border-border bg-muted px-3 py-4 text-center"
        >
            <div
                class="flex size-11 items-center justify-center rounded-full bg-[var(--om-navy)]/10"
            >
                <component
                    :is="badge.icon"
                    class="size-5 text-[var(--om-navy)]"
                />
            </div>
            <h3
                class="mt-3 font-heading text-sm font-semibold text-[var(--om-navy)]"
            >
                {{ badge.title }}
            </h3>
            <p class="om-meta mt-0.5 !text-xs">{{ badge.description }}</p>
        </div>
    </div>
</template>
