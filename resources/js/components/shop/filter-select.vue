<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type FilterOption = {
    value: string;
    label: string;
};

const EMPTY = '__all__';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        options: FilterOption[];
        placeholder?: string;
        id?: string;
        class?: string;
        triggerClass?: string;
    }>(),
    {
        placeholder: 'Pilih',
        id: undefined,
        class: undefined,
        triggerClass: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selected = computed<string>(() =>
    props.modelValue === '' ? EMPTY : props.modelValue,
);

const items = computed(() =>
    props.options.map((option) => ({
        value: option.value === '' ? EMPTY : option.value,
        label: option.label,
    })),
);

function onUpdate(value: unknown): void {
    if (typeof value !== 'string') {
return;
}

    emit('update:modelValue', value === EMPTY ? '' : value);
}
</script>

<template>
    <Select :model-value="selected" @update:model-value="onUpdate">
        <SelectTrigger
            :id="id"
            size="sm"
            :class="
                cn(
                    'h-9 min-w-[8.5rem] border-input bg-background text-[12px] font-medium shadow-none',
                    triggerClass,
                    $props.class,
                )
            "
        >
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectGroup>
                <SelectItem
                    v-for="option in items"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectGroup>
        </SelectContent>
    </Select>
</template>
