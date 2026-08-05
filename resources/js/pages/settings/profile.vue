<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSelectField from '@/components/auth/auth-select-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
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

const canSubmit = computed(
    () =>
        form.first_name.trim().length > 0 &&
        form.last_name.trim().length > 0 &&
        form.email.trim().length > 0 &&
        !form.processing,
);

function submit(): void {
    form.patch(profileRoutes.update.url(), { preserveScroll: true });
}

const deleteOpen = ref<boolean>(false);
const deleteForm = useForm({ password: '' });

const canDelete = computed(
    () => deleteForm.password.length > 0 && !deleteForm.processing,
);

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
    <Head title="Profil" />

    <p class="mb-4 text-sm text-muted-foreground">Perbarui nama dan email akunmu.</p>

    <form class="flex flex-col gap-3.5" @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-2.5">
            <AuthTextField
                id="first_name"
                v-model="form.first_name"
                label="Nama depan"
                type="text"
                required
                autocomplete="given-name"
                placeholder="Nama depan *"
                :error="form.errors.first_name"
            />
            <AuthTextField
                id="last_name"
                v-model="form.last_name"
                label="Nama belakang"
                type="text"
                required
                autocomplete="family-name"
                placeholder="Nama belakang *"
                :error="form.errors.last_name"
            />
        </div>

        <AuthSelectField
            id="gender"
            v-model="form.gender"
            label="Jenis kelamin"
            placeholder="Pilih jenis kelamin"
            :options="genders"
            :error="form.errors.gender"
        />

        <AuthTextField
            id="email"
            v-model="form.email"
            label="Email"
            type="email"
            required
            autocomplete="email"
            placeholder="Masukkan emailmu *"
            :error="form.errors.email"
        />

        <div
            v-if="hasUnverifiedEmail"
            class="rounded-md bg-amber-50 px-3 py-2.5"
        >
            <p class="text-[13px] text-amber-900">
                Email belum diverifikasi.
                <Link
                    :href="verification.send.url()"
                    method="post"
                    as="button"
                    class="font-semibold underline"
                >
                    Kirim ulang email verifikasi
                </Link>
            </p>
            <p
                v-if="status === 'verification-link-sent'"
                class="mt-1 text-[13px] font-medium text-emerald-700"
            >
                Link verifikasi baru sudah dikirim.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <AuthSubmitButton
                class="!w-auto px-6"
                label="Simpan"
                :enabled="canSubmit"
                :processing="form.processing"
            />
            <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">
                Tersimpan.
            </p>
        </div>
    </form>

    <div class="mt-10 border-t border-border pt-6">
        <h3 class="text-[13px] font-semibold text-foreground">Hapus akun</h3>
        <p class="mt-1 text-sm text-muted-foreground">
            Hapus akun dan seluruh datanya secara permanen.
        </p>
        <button
            type="button"
            class="mt-3 inline-flex h-10 items-center rounded-md px-4 text-[13px] font-semibold text-red-600 ring-1 ring-red-200"
            @click="openDelete"
        >
            Hapus akun
        </button>
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogTitle>Hapus akun?</DialogTitle>
            <p class="mt-2 text-sm leading-5 text-muted-foreground">
                Setelah dihapus, semua data tidak bisa dikembalikan. Masukkan
                password untuk konfirmasi.
            </p>
            <form class="mt-5 flex flex-col gap-3.5" @submit.prevent="confirmDelete">
                <AuthPasswordField
                    id="delete_password"
                    v-model="deleteForm.password"
                    label="Password"
                    autocomplete="current-password"
                    placeholder="Masukkan passwordmu *"
                    :error="deleteForm.errors.password"
                />
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="om-action-muted px-3"
                        @click="deleteOpen = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-red-600 px-4 text-[13px] font-semibold text-white disabled:opacity-50"
                        :disabled="!canDelete"
                    >
                        Hapus akun
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
