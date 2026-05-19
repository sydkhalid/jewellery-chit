import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Playwright',
    timeout: 30_000,
    expect: {
        timeout: 7_500,
    },
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    reporter: [
        ['list'],
        ['html', { outputFolder: 'tests/Playwright/report', open: 'never' }],
    ],
    projects: [
        {
            name: 'chromium-desktop',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'chromium-mobile',
            use: { ...devices['Pixel 7'] },
        },
    ],
});
