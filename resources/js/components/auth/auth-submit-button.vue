<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

defineOptions({ inheritAttrs: false });

defineProps<{
    label: string;
    enabled: boolean;
    processing?: boolean;
}>();
</script>

<template>
    <Button
        type="submit"
        size="xl"
        v-bind="$attrs"
        class="w-full"
        :disabled="processing || !enabled"
    >
        <Spinner v-if="processing" class="text-current" />
        <span v-else>{{ label }}</span>
    </Button>
</template>
