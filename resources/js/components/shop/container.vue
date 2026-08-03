<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();
const className = computed<string>(() =>
    cn('mx-auto max-w-7xl px-4 sm:px-6', attrs.class as string),
);
</script>

<template>
    <div :class="className" v-bind="{ ...$attrs, class: undefined }">
        <slot />
    </div>
</template>
