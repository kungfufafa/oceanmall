<script setup lang="ts">
import type { ToasterProps } from 'vue-sonner';
import { Toaster as SonnerPrimitive } from 'vue-sonner';
import 'vue-sonner/style.css';
import { useAppearance } from '@/composables/useAppearance';

withDefaults(
    defineProps<{
        position?: ToasterProps['position'];
        richColors?: boolean;
    }>(),
    {
        position: 'bottom-right',
        richColors: true,
    },
);

const { appearance } = useAppearance();
</script>

<template>
    <SonnerPrimitive
        :theme="appearance"
        class="toaster group"
        :position="position"
        :rich-colors="richColors"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
        }"
    />
</template>
