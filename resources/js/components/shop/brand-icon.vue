<script setup lang="ts">
defineProps<{
    class?: string;
}>();
</script>

<template>
    <img
        src="/images/logo-icon-transparent.png"
        alt="OceanMall Logo"
        :class="['aspect-square shrink-0 object-contain', $props.class]"
    />
</template>
