<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk',
        description: 'Masuk dengan email yang sudah diverifikasi, ya.',
        actionLabel: 'Daftar',
        actionHref: register.url(),
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const email = ref('');
const password = ref('');

const canSubmit = computed(
    () => email.value.trim().length > 0 && password.value.length > 0,
);
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-5 rounded-lg bg-emerald-50 px-3.5 py-3 text-[13px] font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <AuthTextField
            id="email"
            v-model="email"
            label="Email"
            type="email"
            name="email"
            required
            autofocus
            autocomplete="email"
            placeholder="Masukkan emailmu *"
            :error="errors.email"
        />

        <div>
            <AuthPasswordField
                id="password"
                v-model="password"
                label="Password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan passwordmu *"
                :error="errors.password"
            />
            <div class="mt-2 flex justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="request.url()"
                    class="om-action-primary"
                >
                    Lupa Password?
                </Link>
            </div>
        </div>

        <AuthSubmitButton
            class="mt-1"
            label="Masuk"
            :enabled="canSubmit"
            :processing="processing"
            data-test="login-button"
        />
    </Form>
</template>
