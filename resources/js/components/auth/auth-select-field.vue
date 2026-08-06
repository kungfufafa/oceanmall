<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/input-error.vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id: string;
    label: string;
    modelValue?: string;
    error?: string;
    placeholder?: string;
    options: Array<{ value: string; label: string }>;
    class?: string;
    required?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selected = computed(() => props.modelValue ?? '');

function onUpdate(value: unknown): void {
    if (typeof value !== 'string') return;
    emit('update:modelValue', value);
}
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </Label>
        <Select
            :model-value="selected || undefined"
            @update:model-value="onUpdate"
        >
            <SelectTrigger
                :id="id"
                :class="
                    cn(
                        'h-[var(--om-control-height)] w-full text-[13px] shadow-none',
                        !selected && 'text-muted-foreground',
                        props.class,
                    )
                "
                :aria-invalid="Boolean(error) || undefined"
            >
                <SelectValue :placeholder="placeholder ?? 'Pilih'" />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectGroup>
            </SelectContent>
        </Select>
        <InputError :message="error" />
    </div>
</template>
