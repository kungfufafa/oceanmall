<script setup lang="ts">
defineProps<{
    class?: string;
}>();
</script>

<template>
    <div
        :class="[
            'rounded-md border border-zinc-200 bg-white p-4',
            $props.class,
        ]"
    >
        <slot />
    </div>
</template>
