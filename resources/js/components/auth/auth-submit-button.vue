<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';

defineOptions({ inheritAttrs: false });

defineProps<{
    label: string;
    enabled: boolean;
    processing?: boolean;
}>();
</script>

<template>
    <button
        type="submit"
        v-bind="$attrs"
        class="om-btn-primary flex w-full items-center justify-center"
        :disabled="processing || !enabled"
    >
        <Spinner v-if="processing" class="text-current" />
        <span v-else>{{ label }}</span>
    </button>
</template>
