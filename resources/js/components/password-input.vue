<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="relative">
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('pr-10', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3.5 text-[var(--om-navy)] hover:text-[var(--om-navy)] focus-visible:outline-none"
            :aria-label="
                showPassword ? 'Sembunyikan password' : 'Tampilkan password'
            "
            :tabindex="-1"
        >
            <EyeOff v-if="showPassword" class="size-[18px]" stroke-width="2" />
            <Eye v-else class="size-[18px]" stroke-width="2" />
        </button>
    </div>
</template>
