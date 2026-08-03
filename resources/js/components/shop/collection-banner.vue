<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { stripHtml } from '@/lib/format';
import * as shop from '@/routes/shop';
import type { Collection } from '@/types/shop';

const props = defineProps<{ collection: Collection }>();

const description = computed<string>(() =>
    stripHtml(props.collection.description),
);
</script>

<template>
    <Link
        :href="shop.collection.url({ collection: collection.slug })"
        class="group relative block overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800"
    >
        <div class="aspect-video sm:aspect-3/2">
            <img
                v-if="collection.thumbnail"
                :src="collection.thumbnail"
                :alt="collection.name"
                loading="lazy"
                class="size-full object-cover object-center transition duration-500 group-hover:scale-105"
            />
            <div
                class="absolute inset-0 bg-linear-to-t from-zinc-900/80 via-zinc-900/40 to-transparent"
            />
        </div>

        <div class="absolute inset-x-0 bottom-0 p-6">
            <h3 class="font-heading text-lg font-semibold text-white">
                {{ collection.name }}
            </h3>
            <p
                v-if="description"
                class="mt-0.5 line-clamp-2 text-sm text-zinc-200"
            >
                {{ description }}
            </p>
            <span
                class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-white transition-all group-hover:gap-2.5"
            >
                Shop now
                <ArrowRight class="size-4" aria-hidden="true" />
            </span>
        </div>
    </Link>
</template>
