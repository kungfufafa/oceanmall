<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/logo-icon-transparent.png"
        alt="OceanMall Logo"
        :class="['object-contain shrink-0 aspect-square', className]"
        v-bind="$attrs"
    />
</template>
