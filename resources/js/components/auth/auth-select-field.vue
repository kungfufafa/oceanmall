<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import InputError from '@/components/input-error.vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    id: string;
    label: string;
    modelValue?: string;
    error?: string;
    placeholder?: string;
    options: Array<{ value: string; label: string }>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const fallthrough = computed(() => {
    const { class: _class, ...rest } = attrs as Record<string, unknown>;
    return rest;
});

const selectClass = computed(() =>
    cn(
        'om-control w-full appearance-none border border-zinc-200 bg-white px-3 text-zinc-900 outline-none',
        'focus:border-[var(--om-navy)]',
        !props.modelValue && 'text-zinc-400',
        props.error && 'border-red-400 focus:border-red-500',
        attrs.class as string,
    ),
);
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" class="om-label">{{ label }}</label>
        <select
            :id="id"
            :value="modelValue ?? ''"
            v-bind="fallthrough"
            :class="selectClass"
            @change="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option v-if="placeholder" disabled value="">
                {{ placeholder }}
            </option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                class="text-zinc-900"
            >
                {{ option.label }}
            </option>
        </select>
        <InputError :message="error" />
    </div>
</template>
