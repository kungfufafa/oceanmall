<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset Password',
        description: 'Masukkan password baru untuk akunmu.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
const password = ref('');
const passwordConfirmation = ref('');

const canSubmit = computed(
    () => password.value.length > 0 && passwordConfirmation.value.length > 0,
);
</script>

<template>
    <Head title="Reset Password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <AuthTextField
            id="email"
            v-model="inputEmail"
            label="Email"
            type="email"
            name="email"
            autocomplete="email"
            readonly
            class="bg-zinc-50 text-zinc-500"
            :error="errors.email"
        />

        <AuthPasswordField
            id="password"
            v-model="password"
            label="Password baru"
            name="password"
            autocomplete="new-password"
            autofocus
            placeholder="Masukkan password baru *"
            :passwordrules="passwordRules"
            :error="errors.password"
        />

        <AuthPasswordField
            id="password_confirmation"
            v-model="passwordConfirmation"
            label="Konfirmasi password"
            name="password_confirmation"
            autocomplete="new-password"
            placeholder="Ulangi password baru *"
            :passwordrules="passwordRules"
            :error="errors.password_confirmation"
        />

        <AuthSubmitButton
            class="mt-2"
            label="Simpan password"
            :enabled="canSubmit"
            :processing="processing"
            data-test="reset-password-button"
        />
    </Form>
</template>
