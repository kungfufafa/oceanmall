<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
        actionLabel?: string;
        class?: string;
    }>(),
    {
        description: undefined,
        icon: undefined,
        actionLabel: undefined,
        class: undefined,
    },
);

const emit = defineEmits<{
    action: [];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center py-16 text-center',
                $props.class,
            )
        "
        role="status"
    >
        <component
            :is="icon"
            v-if="icon"
            class="size-10 text-muted-foreground/50"
            aria-hidden="true"
        />
        <h3 class="om-page-title mt-3">{{ title }}</h3>
        <p v-if="description" class="om-meta mt-1 max-w-sm">
            {{ description }}
        </p>
        <Button
            v-if="actionLabel"
            type="button"
            variant="outline"
            size="xl"
            class="mt-4"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </Button>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
