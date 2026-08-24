<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { RadioGroupItem } from '@/components/ui/radio-group';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        value: string;
        id?: string;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        id: undefined,
        disabled: false,
        class: undefined,
    },
);
</script>

<template>
    <Label
        :for="id"
        :class="
            cn(
                'flex cursor-pointer items-start gap-3 rounded-md border border-border bg-card p-3 transition-colors has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-accent/60',
                disabled && 'cursor-not-allowed opacity-60',
                $props.class,
            )
        "
    >
        <RadioGroupItem
            :id="id"
            :value="value"
            :disabled="disabled"
            class="mt-0.5"
        />
        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </Label>
</template>
