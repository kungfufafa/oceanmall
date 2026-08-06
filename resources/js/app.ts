import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AccountLayout from '@/layouts/account-layout.vue';
import AppLayout from '@/layouts/app-layout.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import SettingsSubLayout from '@/layouts/settings-sub-layout.vue';
import StorefrontLayout from '@/layouts/storefront-layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'OceanMall';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [StorefrontLayout, AccountLayout, SettingsSubLayout];
            case name === 'dashboard':
            case name.startsWith('account/'):
                return [StorefrontLayout, AccountLayout];
            case name.startsWith('shop/'):
                return StorefrontLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeFlashToast();
