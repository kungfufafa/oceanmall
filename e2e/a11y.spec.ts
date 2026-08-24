import { expect, test } from '@playwright/test';
import { expectNoCriticalA11y, loginAsCustomer } from './helpers/qa';

/**
 * Dedicated a11y sweep across primary storefront surfaces.
 * Fails on critical/serious WCAG 2.1 A/AA via axe-core.
 */
test.describe('TC-UI-A11Y — Accessibility sweep', () => {
    const guestPages: Array<{ id: string; path: string }> = [
        { id: 'TC-UI-A11Y-001', path: '/' },
        { id: 'TC-UI-A11Y-002', path: '/shop' },
        { id: 'TC-UI-A11Y-003', path: '/categories' },
        { id: 'TC-UI-A11Y-004', path: '/cart' },
        { id: 'TC-UI-A11Y-005', path: '/login' },
        { id: 'TC-UI-A11Y-006', path: '/register' },
        { id: 'TC-UI-A11Y-007', path: '/search?q=realme' },
    ];

    for (const entry of guestPages) {
        test(`${entry.id} axe guest ${entry.path}`, async ({ page }) => {
            await page.goto(entry.path);
            await expect(page.locator('body')).toBeVisible();
            await expectNoCriticalA11y(page, entry.path);
        });
    }

    test('TC-UI-A11Y-008 axe dashboard + addresses (auth)', async ({ page }) => {
        await loginAsCustomer(page);

        for (const path of ['/dashboard', '/account/addresses', '/account/notifications']) {
            await page.goto(path);
            await expect(page.locator('body')).toBeVisible();
            await expectNoCriticalA11y(page, path);
        }
    });
});
