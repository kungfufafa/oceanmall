<script setup lang="ts">
defineProps<{
    class?: string;
}>();
</script>

<template>
    <img
        src="/images/logo-icon-transparent.png"
        alt="OceanMall Logo"
        :class="['object-contain shrink-0 aspect-square', $props.class]"
    />
</template>

