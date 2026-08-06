<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        class?: string;
        contentClass?: string;
        padded?: boolean;
    }>(),
    {
        class: undefined,
        contentClass: undefined,
        padded: true,
    },
);
</script>

<template>
    <Card
        :class="
            cn(
                'gap-0 rounded-md border-border bg-card py-0 text-card-foreground shadow-none',
                $props.class,
            )
        "
    >
        <CardHeader
            v-if="$slots.header || $slots.title || $slots.description"
            class="gap-1 p-4 pb-0"
        >
            <slot name="header">
                <CardTitle
                    v-if="$slots.title"
                    class="text-[15px] font-semibold"
                >
                    <slot name="title" />
                </CardTitle>
                <CardDescription v-if="$slots.description">
                    <slot name="description" />
                </CardDescription>
            </slot>
        </CardHeader>
        <CardContent :class="cn(padded ? 'p-4' : 'p-0', $props.contentClass)">
            <slot />
        </CardContent>
        <CardFooter v-if="$slots.footer" class="p-4 pt-0">
            <slot name="footer" />
        </CardFooter>
    </Card>
</template>
