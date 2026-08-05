import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL
    || process.env.DEPLOY_BASE_URL
    || 'http://127.0.0.1:8000';

/**
 * Browser QA for OceanMall storefront (QA Engineer layer).
 * Assumes Laravel is already serving baseURL with built assets.
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    timeout: 60_000,
    expect: { timeout: 15_000 },
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: 'playwright-report' }],
        ['json', { outputFile: 'test-results/playwright-qa.json' }],
    ],
    outputDir: 'test-results/artifacts',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        locale: 'id-ID',
        timezoneId: 'Asia/Jakarta',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'mobile-chrome',
            use: { ...devices['Pixel 7'] },
        },
    ],
});
