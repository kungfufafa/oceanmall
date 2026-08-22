<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: number;
        min?: number;
        max?: number | null;
        disabled?: boolean;
        size?: 'sm' | 'md';
        class?: string;
    }>(),
    {
        min: 1,
        max: null,
        disabled: false,
        size: 'md',
        class: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();

function dec(): void {
    if (props.disabled || props.modelValue <= props.min) {
return;
}

    emit('update:modelValue', props.modelValue - 1);
}

function inc(): void {
    if (props.disabled) {
return;
}

    if (props.max !== null && props.modelValue >= props.max) {
return;
}

    emit('update:modelValue', props.modelValue + 1);
}
</script>

<template>
    <div
        :class="
            cn(
                'inline-flex items-center rounded-md border border-border bg-background',
                size === 'sm' ? 'h-8' : 'h-11',
                $props.class,
            )
        "
        role="group"
        aria-label="Jumlah"
    >
        <Button
            type="button"
            variant="ghost"
            :size="size === 'sm' ? 'icon-sm' : 'icon'"
            class="rounded-none"
            :disabled="disabled || modelValue <= min"
            aria-label="Kurangi"
            @click="dec"
        >
            <Minus />
        </Button>
        <input
            :value="modelValue"
            type="number"
            :min="min"
            :max="max ?? undefined"
            :disabled="disabled"
            class="w-12 text-center font-semibold text-foreground tabular-nums bg-transparent border-none focus:outline-none"
            :class="size === 'sm' ? 'text-xs' : 'text-sm'"
            @input="e => {
                const val = parseInt((e.target as HTMLInputElement).value);
                if (!isNaN(val) && val >= min && (max === null || val <= max)) {
                    emit('update:modelValue', val);
                } else if ((e.target as HTMLInputElement).value === '') {
                    // keep or handle empty input? Let's keep existing value
                }
            }"
        />
        <Button
            type="button"
            variant="ghost"
            :size="size === 'sm' ? 'icon-sm' : 'icon'"
            class="rounded-none"
            :disabled="disabled || (max !== null && modelValue >= max)"
            aria-label="Tambah"
            @click="inc"
        >
            <Plus />
        </Button>
    </div>
</template>
