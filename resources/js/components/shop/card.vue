<script setup lang="ts">
defineProps<{
    class?: string;
}>();
</script>

<template>
    <div
        class="rounded-xl bg-zinc-50 p-1 shadow ring-1 ring-zinc-200/25 dark:bg-zinc-900 dark:ring-white/10"
    >
        <div
            :class="[
                'rounded-lg bg-white p-3 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-white/10',
                $props.class,
            ]"
        >
            <slot />
        </div>
    </div>
</template>
