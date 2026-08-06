import type { Customer } from '@shopperlabs/shopper-types';

/**
 * Authenticated user shape — extends upstream `Customer` with Fortify
 * fields and the `full_name` accessor appended server-side.
 */
export type User = Customer & {
    full_name?: string | null;
    two_factor_enabled?: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
