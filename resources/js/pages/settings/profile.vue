<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import PasswordInput from '@/components/password-input.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import * as profileRoutes from '@/routes/profile';
import * as verification from '@/routes/verification';

type GenderOption = { value: string; label: string };

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
    genders: GenderOption[];
}>();

const page = usePage();
const user = page.props.auth.user as {
    first_name?: string;
    last_name?: string;
    email?: string;
    gender?: string | null;
    email_verified_at?: string | null;
} | null;

const form = useForm({
    first_name: user?.first_name ?? '',
    last_name: user?.last_name ?? '',
    gender: user?.gender ?? '',
    email: user?.email ?? '',
});

const hasUnverifiedEmail = props.mustVerifyEmail && !user?.email_verified_at;

function submit(): void {
    form.patch(profileRoutes.update.url(), { preserveScroll: true });
}

const deleteOpen = ref<boolean>(false);
const deleteForm = useForm({ password: '' });

function openDelete(): void {
    deleteForm.reset();
    deleteForm.clearErrors();
    deleteOpen.value = true;
}

function confirmDelete(): void {
    deleteForm.delete(profileRoutes.destroy.url(), {
        preserveScroll: true,
        onSuccess: () => (deleteOpen.value = false),
        onError: () => deleteForm.reset('password'),
    });
}
</script>

<template>
    <Head title="Profile settings" />

    <div class="space-y-1">
        <h2 class="text-lg font-medium text-zinc-900 dark:text-white">
            Profile
        </h2>
        <p class="text-sm text-zinc-500">Update your name and email address</p>
    </div>

    <form class="my-6 w-full max-w-lg space-y-6" @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="last_name">Last name</Label>
                <Input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    required
                    autocomplete="family-name"
                />
                <p
                    v-if="form.errors.last_name"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ form.errors.last_name }}
                </p>
            </div>
            <div class="space-y-2">
                <Label for="first_name">First name</Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    required
                    autocomplete="given-name"
                />
                <p
                    v-if="form.errors.first_name"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ form.errors.first_name }}
                </p>
            </div>
        </div>

        <div class="space-y-2">
            <Label for="gender">Gender</Label>
            <Select v-model="form.gender">
                <SelectTrigger id="gender" aria-label="Gender" class="w-full">
                    <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in genders"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">
                {{ form.errors.gender }}
            </p>
        </div>

        <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">
                {{ form.errors.email }}
            </p>

            <div v-if="hasUnverifiedEmail" class="mt-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Your email address is unverified.
                    <Link
                        :href="verification.send.url()"
                        method="post"
                        as="button"
                        class="text-zinc-900 underline dark:text-white"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>
                <p
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="form.processing">Save</Button>
            <p v-if="form.recentlySuccessful" class="text-sm text-zinc-500">
                Saved.
            </p>
        </div>
    </form>

    <div class="mt-10 max-w-lg space-y-3">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
            Delete account
        </h3>
        <p class="text-sm text-zinc-500">
            Delete your account and all of its resources
        </p>
        <Button type="button" variant="destructive" @click="openDelete"
            >Delete account</Button
        >
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogTitle
                class="text-lg font-semibold text-zinc-900 dark:text-white"
            >
                Are you sure you want to delete your account?
            </DialogTitle>
            <p class="mt-2 text-sm text-zinc-500">
                Once your account is deleted, all of its resources and data will
                be permanently deleted. Please enter your password to confirm
                you would like to permanently delete your account.
            </p>
            <form class="mt-6 space-y-4" @submit.prevent="confirmDelete">
                <div class="space-y-2">
                    <Label for="delete_password">Password</Label>
                    <PasswordInput
                        id="delete_password"
                        v-model="deleteForm.password"
                        autocomplete="current-password"
                    />
                    <p
                        v-if="deleteForm.errors.password"
                        class="text-xs text-red-600"
                    >
                        {{ deleteForm.errors.password }}
                    </p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="deleteOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="deleteForm.processing"
                        >Delete account</Button
                    >
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
