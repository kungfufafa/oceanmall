<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    id?: string;
    modelValue: string;
    class?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <select
        :id="id"
        :value="modelValue"
        :class="
            cn(
                'h-9 min-w-0 rounded-md border border-input bg-background px-2.5 text-[12px] font-medium text-foreground outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                $props.class,
            )
        "
        @change="
            emit(
                'update:modelValue',
                ($event.target as HTMLSelectElement).value,
            )
        "
    >
        <slot />
    </select>
</template>
