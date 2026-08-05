import { expect, test } from '@playwright/test';
import { expectNoCriticalA11y, loginAsCustomer, qaUser } from './helpers/qa';

test.describe('TC-UI-AUTH — Auth (browser)', () => {
    test('TC-UI-AUTH-001 login page a11y + fields @smoke', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByLabel('Email')).toBeVisible();
        await expect(page.getByLabel('Password', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: /masuk/i })).toBeVisible();
        await expectNoCriticalA11y(page, 'login');
    });

    test('TC-UI-AUTH-002 wrong password shows error, stays on login', async ({ page }) => {
        await page.goto('/login');
        // Use a distinct email so failed attempts do not burn the QA user's login throttle bucket.
        await page.getByLabel('Email').fill('wrong-password-qa@oceanmall.test');
        await page.getByLabel('Password', { exact: true }).fill('definitely-wrong-password');
        await page.getByRole('button', { name: /masuk/i }).click();
        await expect(page).toHaveURL(/\/login/);
        await expect(
            page.getByText(/password|kredensial|salah|tidak cocok|credentials|akun/i).first(),
        ).toBeVisible({ timeout: 10_000 });
    });

    test('TC-UI-AUTH-003 valid login reaches dashboard @smoke', async ({ page }) => {
        await loginAsCustomer(page);
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.getByText(/Halo,/i)).toBeVisible();
        await expect(page.getByText(/Pesanan terbaru/i)).toBeVisible();
        await expectNoCriticalA11y(page, 'dashboard');
    });

    test('TC-UI-AUTH-004 guest checkout redirects to login @smoke', async ({ page }) => {
        await page.goto('/checkout');
        await expect(page).toHaveURL(/\/login/);
    });
});
