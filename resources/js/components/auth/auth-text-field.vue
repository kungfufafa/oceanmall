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
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const fallthrough = computed(() => {
    const { class: _class, ...rest } = attrs as Record<string, unknown>;
    return rest;
});

const inputClass = computed(() =>
    cn(
        'om-control w-full border border-zinc-200 bg-white px-3 text-zinc-900 outline-none placeholder:text-zinc-400',
        'focus:border-[var(--om-navy)]',
        props.error && 'border-red-400 focus:border-red-500',
        attrs.class as string,
    ),
);
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" class="om-label">{{ label }}</label>
        <input
            :id="id"
            :value="modelValue"
            v-bind="fallthrough"
            :class="inputClass"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
        <InputError :message="error" />
    </div>
</template>
