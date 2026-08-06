<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import * as shop from '@/routes/shop';
import type { Collection } from '@/types/shop';

defineProps<{ collection: Collection }>();
</script>

<template>
    <Link
        :href="shop.collection.url({ collection: collection.slug })"
        class="group relative block overflow-hidden rounded-2xl bg-muted"
    >
        <div class="relative aspect-[16/9] sm:aspect-[2/1]">
            <img
                v-if="collection.thumbnail"
                :src="collection.thumbnail"
                :alt="collection.name"
                loading="lazy"
                class="size-full object-cover object-center transition duration-300 group-hover:scale-[1.02]"
            />
            <div
                v-else
                class="size-full bg-gradient-to-br from-[var(--om-navy)] to-[#0B5FFF]"
            />

            <div
                class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/15 to-transparent"
            />

            <!-- Leave room on the right for carousel indicator -->
            <div
                class="absolute inset-x-0 bottom-0 p-3.5 pr-24 sm:p-5 sm:pr-28"
            >
                <p
                    class="truncate text-[15px] font-bold tracking-tight text-white sm:text-lg"
                >
                    {{ collection.name }}
                </p>
                <p
                    v-if="collection.products_count"
                    class="mt-0.5 text-[11px] text-white/80 sm:text-xs"
                >
                    {{ collection.products_count }} produk
                </p>
            </div>
        </div>
    </Link>
</template>
