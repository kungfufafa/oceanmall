<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
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
    <Empty
        :class="cn('border-0 py-16', $props.class)"
        role="status"
    >
        <EmptyHeader>
            <EmptyMedia v-if="icon" variant="icon">
                <component :is="icon" aria-hidden="true" />
            </EmptyMedia>
            <EmptyTitle>{{ title }}</EmptyTitle>
            <EmptyDescription v-if="description">
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent v-if="actionLabel || $slots.action">
            <Button
                v-if="actionLabel"
                type="button"
                variant="outline"
                size="xl"
                @click="emit('action')"
            >
                {{ actionLabel }}
            </Button>
            <slot name="action" />
        </EmptyContent>
    </Empty>
</template>
