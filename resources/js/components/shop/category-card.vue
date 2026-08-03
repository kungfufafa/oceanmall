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
            class="relative size-24 overflow-hidden rounded-full bg-zinc-100 ring-2 ring-transparent transition group-hover:ring-zinc-900 dark:bg-zinc-800 dark:group-hover:ring-white"
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
            class="mt-3 text-center text-sm font-medium text-zinc-900 transition group-hover:text-zinc-600 dark:text-white dark:group-hover:text-zinc-300"
        >
            {{ category.name }}
        </span>
    </Link>
</template>
