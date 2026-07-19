import { defineConfig } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8012';

export default defineConfig({
    testDir: './tests/Browser',
    timeout: 45_000,
    expect: {
        timeout: 10_000,
    },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list']],
    outputDir: './node_modules/.cache/playwright/test-results',
    use: {
        baseURL,
        channel: 'chrome',
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
        video: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8012',
        url: baseURL,
        reuseExistingServer: true,
        timeout: 120_000,
        env: {
            ...process.env,
            APP_ENV: 'testing',
            APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            APP_URL: baseURL,
            SESSION_DRIVER: 'array',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
            FASTAPI_URL: process.env.FASTAPI_URL ?? 'http://127.0.0.1:8000',
        },
    },
});
