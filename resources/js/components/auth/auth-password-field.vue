<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, useAttrs } from 'vue';
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
const showPassword = ref(false);

const fallthrough = computed(() => {
    const { class: _class, type: _type, ...rest } = attrs as Record<
        string,
        unknown
    >;
    return rest;
});

const inputClass = computed(() =>
    cn(
        'om-control w-full border border-zinc-200 bg-white py-0 pr-10 pl-3 text-zinc-900 outline-none placeholder:text-zinc-400',
        'focus:border-[var(--om-navy)]',
        props.error && 'border-red-400 focus:border-red-500',
        attrs.class as string,
    ),
);
</script>

<template>
    <div class="space-y-1.5">
        <label :for="id" class="om-label">{{ label }}</label>
        <div class="relative">
            <input
                :id="id"
                :value="modelValue"
                :type="showPassword ? 'text' : 'password'"
                v-bind="fallthrough"
                :class="inputClass"
                @input="
                    emit(
                        'update:modelValue',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-[var(--om-navy)]"
                :aria-label="
                    showPassword ? 'Sembunyikan password' : 'Tampilkan password'
                "
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <EyeOff v-if="showPassword" class="size-4" stroke-width="2" />
                <Eye v-else class="size-4" stroke-width="2" />
            </button>
        </div>
        <InputError :message="error" />
    </div>
</template>
