<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        title: string;
        actionHref?: string;
        actionLabel?: string;
        class?: string;
    }>(),
    {
        actionHref: undefined,
        actionLabel: undefined,
        class: undefined,
    },
);
</script>

<template>
    <div
        :class="
            cn('flex items-end justify-between gap-3', $props.class)
        "
    >
        <div class="min-w-0">
            <h2 class="truncate text-base font-semibold tracking-tight text-foreground">
                {{ title }}
            </h2>
            <p v-if="$slots.description" class="mt-0.5 text-sm text-muted-foreground">
                <slot name="description" />
            </p>
        </div>
        <Button
            v-if="actionHref && actionLabel"
            as-child
            variant="link"
            size="sm"
            class="h-auto shrink-0 px-0 text-[12px] font-bold"
        >
            <Link :href="actionHref">
                {{ actionLabel }}
            </Link>
        </Button>
        <slot name="action" />
    </div>
</template>
