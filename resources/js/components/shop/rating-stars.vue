<script setup lang="ts">
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        size?: 'sm' | 'md' | 'lg';
        interactive?: boolean;
    }>(),
    {
        size: 'sm',
        interactive: false,
    },
);

const emit = defineEmits<{
    change: [value: number];
}>();

const sizeClass = computed(() => {
    if (props.size === 'lg') return 'size-5';
    if (props.size === 'md') return 'size-4';
    return 'size-3.5';
});

function fill(n: number): 'full' | 'half' | 'empty' {
    if (props.interactive) {
        return n <= props.value ? 'full' : 'empty';
    }

    if (n <= Math.floor(props.value)) {
        return 'full';
    }

    if (n === Math.ceil(props.value) && props.value % 1 >= 0.25) {
        return 'half';
    }

    return 'empty';
}

function select(n: number): void {
    if (props.interactive) {
        emit('change', n);
    }
}
</script>

<template>
    <div
        class="inline-flex items-center gap-0.5 text-[var(--om-navy)]"
        role="img"
        :aria-label="`${value} dari 5`"
    >
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            class="relative inline-flex"
            :class="[
                sizeClass,
                interactive
                    ? 'cursor-pointer rounded-md hover:opacity-80'
                    : 'pointer-events-none',
            ]"
            :tabindex="interactive ? 0 : -1"
            :aria-label="interactive ? `${n} bintang` : undefined"
            @click="select(n)"
        >
            <Star
                class="absolute inset-0 size-full text-zinc-300"
                stroke-width="1.5"
            />
            <span
                v-if="fill(n) !== 'empty'"
                class="absolute inset-y-0 left-0 overflow-hidden text-[var(--om-navy)]"
                :style="{ width: fill(n) === 'half' ? '50%' : '100%' }"
            >
                <Star
                    class="block fill-current"
                    :class="sizeClass"
                    stroke-width="1.5"
                />
            </span>
        </button>
    </div>
</template>
