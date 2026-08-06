<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verifikasi Email',
        description:
            'Cek inbox emailmu dan klik link verifikasi yang kami kirim.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verifikasi Email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-5 rounded-xl bg-emerald-50 px-3.5 py-3 text-[13px] font-medium text-emerald-700"
    >
        Link verifikasi baru sudah dikirim ke emailmu.
    </div>

    <Form
        v-bind="send.form()"
        v-slot="{ processing }"
        class="flex flex-col gap-4"
    >
        <AuthSubmitButton
            label="Kirim ulang email verifikasi"
            :enabled="true"
            :processing="processing"
        />

        <Link
            :href="logout.url()"
            class="py-2 text-center text-[13px] font-bold text-[var(--om-navy)]"
        >
            Keluar
        </Link>
    </Form>
</template>
