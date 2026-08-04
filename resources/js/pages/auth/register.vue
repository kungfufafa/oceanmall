<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Daftar',
        description:
            'Daftar akun OceanMall buat belanja lebih gampang dan dapet promo member.',
        actionLabel: 'Masuk',
        actionHref: login.url(),
    },
});

const firstName = ref('');
const lastName = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');

const canSubmit = computed(
    () =>
        firstName.value.trim().length > 0 &&
        lastName.value.trim().length > 0 &&
        email.value.trim().length > 0 &&
        password.value.length > 0 &&
        passwordConfirmation.value.length > 0,
);
</script>

<template>
    <Head title="Daftar" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <div class="grid grid-cols-2 gap-2.5">
            <AuthTextField
                id="first_name"
                v-model="firstName"
                label="Nama depan"
                type="text"
                name="first_name"
                required
                autofocus
                autocomplete="given-name"
                placeholder="Nama depan *"
                :error="errors.first_name"
            />

            <AuthTextField
                id="last_name"
                v-model="lastName"
                label="Nama belakang"
                type="text"
                name="last_name"
                required
                autocomplete="family-name"
                placeholder="Nama belakang *"
                :error="errors.last_name"
            />
        </div>

        <AuthTextField
            id="email"
            v-model="email"
            label="Email"
            type="email"
            name="email"
            required
            autocomplete="email"
            placeholder="Masukkan emailmu *"
            :error="errors.email"
        />

        <AuthPasswordField
            id="password"
            v-model="password"
            label="Password"
            name="password"
            required
            autocomplete="new-password"
            placeholder="Buat passwordmu *"
            :passwordrules="passwordRules"
            :error="errors.password"
        />

        <AuthPasswordField
            id="password_confirmation"
            v-model="passwordConfirmation"
            label="Konfirmasi password"
            name="password_confirmation"
            required
            autocomplete="new-password"
            placeholder="Ulangi passwordmu *"
            :passwordrules="passwordRules"
            :error="errors.password_confirmation"
        />

        <AuthSubmitButton
            class="mt-1"
            label="Daftar"
            :enabled="canSubmit"
            :processing="processing"
            data-test="register-user-button"
        />
    </Form>
</template>
