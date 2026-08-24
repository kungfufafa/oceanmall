<script setup lang="ts">
import { MessageCircle, RotateCcw, ShieldCheck, Truck } from 'lucide-vue-next';
import {
    Card,
    CardContent,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';

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
        <Card
            v-for="badge in badges"
            :key="badge.title"
            class="gap-0 rounded-md border-border bg-muted py-0 text-card-foreground shadow-none"
        >
            <CardContent
                class="flex flex-col items-center gap-3 px-3 py-4 text-center"
            >
                <div
                    class="flex size-11 items-center justify-center rounded-full bg-[var(--om-navy)]/10"
                >
                    <component
                        :is="badge.icon"
                        class="size-5 text-[var(--om-navy)]"
                    />
                </div>
                <div class="flex flex-col gap-0.5">
                    <CardTitle
                        class="font-heading text-sm text-[var(--om-navy)]"
                    >
                        {{ badge.title }}
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{ badge.description }}
                    </CardDescription>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
