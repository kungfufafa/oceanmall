<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import * as shop from '@/routes/shop';
import type { Category } from '@/types/shop';

defineProps<{ category: Category }>();
</script>

<template>
    <Link
        :href="shop.category.url({ category: category.slug })"
        class="group relative flex flex-col items-center"
    >
        <div
            class="relative size-20 overflow-hidden rounded-2xl bg-muted ring-1 ring-border transition group-hover:ring-[var(--om-navy)] sm:size-24"
        >
            <img
                v-if="category.thumbnail"
                :src="category.thumbnail"
                :alt="category.name"
                loading="lazy"
                class="size-full object-cover object-center"
            />
        </div>
        <span
            class="mt-2.5 line-clamp-2 text-center text-xs font-medium text-foreground transition group-hover:text-[var(--om-navy)] sm:text-sm"
        >
            {{ category.name }}
        </span>
    </Link>
</template>
