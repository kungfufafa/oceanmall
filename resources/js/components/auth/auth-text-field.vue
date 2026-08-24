<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import InputError from '@/components/input-error.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

defineProps<{
    id: string;
    label: string;
    modelValue?: string;
    error?: string;
    required?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const fallthrough = computed(() => {
    const copy = { ...attrs } as Record<string, unknown>;
    delete copy.class;

    return copy;
});

const inputClass = computed(() =>
    cn(
        'h-[var(--om-control-height)] text-[13px] shadow-none',
        attrs.class as string,
    ),
);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-center gap-1">
            <Label :for="id">{{ label }}</Label>
            <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
        </div>
        <Input
            :id="id"
            :model-value="modelValue"
            v-bind="fallthrough"
            :class="inputClass"
            :aria-invalid="Boolean(error) || undefined"
            @update:model-value="
                emit('update:modelValue', String($event ?? ''))
            "
        />
        <InputError :message="error" />
    </div>
</template>
