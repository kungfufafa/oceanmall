<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

const displayName = computed<string>(() => {
    return typeof props.user.name === 'string'
        ? props.user.name
        : (props.user.full_name ?? props.user.email);
});

const avatarSrc = computed<string | null>(() => {
    const avatar = props.user.avatar as unknown;

    if (typeof avatar === 'string') {
        return avatar || null;
    }

    if (avatar && typeof avatar === 'object') {
        const value = avatar as { url?: string | null; default?: string | null };

        return value.url || value.default || null;
    }

    return null;
});
</script>

<template>
    <Avatar class="size-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="avatarSrc" :src="avatarSrc" :alt="displayName" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(displayName) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm/tight">
        <span class="truncate font-medium">{{ displayName }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
