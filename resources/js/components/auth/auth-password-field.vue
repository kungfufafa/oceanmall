<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, useAttrs } from 'vue';
import InputError from '@/components/input-error.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
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
const showPassword = ref(false);

const fallthrough = computed(() => {
    const {
        class: _class,
        type: _type,
        ...rest
    } = attrs as Record<string, unknown>;
    return rest;
});

const inputClass = computed(() =>
    cn(
        'h-[var(--om-control-height)] pr-10 text-[13px] shadow-none',
        attrs.class as string,
    ),
);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </Label>
        <div class="relative">
            <Input
                :id="id"
                :model-value="modelValue"
                :type="showPassword ? 'text' : 'password'"
                v-bind="fallthrough"
                :class="inputClass"
                :aria-invalid="Boolean(error) || undefined"
                @update:model-value="
                    emit('update:modelValue', String($event ?? ''))
                "
            />
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="absolute inset-y-0 right-1 my-auto text-primary"
                :aria-label="
                    showPassword ? 'Sembunyikan password' : 'Tampilkan password'
                "
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <EyeOff v-if="showPassword" />
                <Eye v-else />
            </Button>
        </div>
        <InputError :message="error" />
    </div>
</template>
