<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CollectionBanner from '@/components/shop/collection-banner.vue';
import type { Collection } from '@/types/shop';

const props = defineProps<{
    collections: Collection[];
}>();

const track = ref<HTMLElement | null>(null);
const activeIndex = ref(0);

function updateActiveIndex(): void {
    const el = track.value;
    if (!el || !props.collections.length) {
        return;
    }

    const children = Array.from(el.children) as HTMLElement[];
    if (!children.length) {
        return;
    }

    const center = el.scrollLeft + el.clientWidth / 2;
    let best = 0;
    let bestDist = Number.POSITIVE_INFINITY;

    children.forEach((child, index) => {
        const mid = child.offsetLeft + child.offsetWidth / 2;
        const dist = Math.abs(mid - center);
        if (dist < bestDist) {
            bestDist = dist;
            best = index;
        }
    });

    activeIndex.value = best;
}

function scrollToIndex(index: number): void {
    const el = track.value;
    const child = el?.children[index] as HTMLElement | undefined;
    if (!el || !child) {
        return;
    }

    el.scrollTo({
        left: child.offsetLeft - (el.clientWidth - child.offsetWidth) / 2,
        behavior: 'smooth',
    });
}

onMounted(() => {
    nextTick(updateActiveIndex);
    track.value?.addEventListener('scroll', updateActiveIndex, {
        passive: true,
    });
    window.addEventListener('resize', updateActiveIndex);
});

onBeforeUnmount(() => {
    track.value?.removeEventListener('scroll', updateActiveIndex);
    window.removeEventListener('resize', updateActiveIndex);
});

watch(
    () => props.collections.length,
    () => nextTick(updateActiveIndex),
);
</script>

<template>
    <section v-if="collections.length" class="relative mt-4">
        <div
            ref="track"
            class="flex gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none] snap-x snap-mandatory sm:gap-4 sm:px-6 lg:mx-auto lg:max-w-7xl [&::-webkit-scrollbar]:hidden"
        >
            <div
                v-for="collection in collections"
                :key="collection.id"
                class="w-[min(100%,calc(100vw-2.75rem))] shrink-0 snap-center sm:w-[min(100%,36rem)] lg:w-[min(100%,42rem)]"
            >
                <CollectionBanner :collection="collection" />
            </div>
        </div>

        <div
            v-if="collections.length > 1"
            class="pointer-events-none absolute right-5 bottom-3 z-10 sm:right-8 lg:right-[max(1.5rem,calc((100%-80rem)/2+1.5rem))]"
        >
            <div
                class="pointer-events-auto flex items-center gap-1.5 rounded-full bg-black/45 px-2.5 py-1.5 backdrop-blur-[2px]"
                role="tablist"
                aria-label="Slide promo"
            >
                <button
                    v-for="(collection, index) in collections"
                    :key="collection.id"
                    type="button"
                    role="tab"
                    :aria-selected="index === activeIndex"
                    :aria-label="`Slide ${index + 1}: ${collection.name}`"
                    class="h-1.5 rounded-full transition-all duration-200"
                    :class="
                        index === activeIndex
                            ? 'w-5 bg-white'
                            : 'w-1.5 bg-white/55 hover:bg-white/80'
                    "
                    @click="scrollToIndex(index)"
                />
            </div>
        </div>
    </section>
</template>
