<script setup lang="ts">
import { MessageCircle, RotateCcw, ShieldCheck, Truck } from 'lucide-vue-next';

const badges = [
    { icon: Truck, title: 'Free Shipping', description: 'On orders over $50' },
    {
        icon: ShieldCheck,
        title: 'Secure Payment',
        description: '100% secure checkout',
    },
    {
        icon: RotateCcw,
        title: 'Easy Returns',
        description: '30-day return policy',
    },
    {
        icon: MessageCircle,
        title: '24/7 Support',
        description: 'Dedicated support',
    },
];
</script>

<template>
    <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        <div
            v-for="badge in badges"
            :key="badge.title"
            class="flex flex-col items-center text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
            >
                <component
                    :is="badge.icon"
                    class="size-6 text-zinc-600 dark:text-zinc-400"
                />
            </div>
            <h3
                class="mt-3 font-heading text-sm font-medium text-zinc-900 dark:text-white"
            >
                {{ badge.title }}
            </h3>
            <p class="mt-1 text-xs text-zinc-500">{{ badge.description }}</p>
        </div>
    </div>
</template>
