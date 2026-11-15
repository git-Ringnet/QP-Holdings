import { defineConfig, devices } from '@playwright/test';

/**
 * Chạy:  cd wp-content/plugins/qp-webp-convert/tests/e2e && npm install && npx playwright install && npx playwright test
 * Biến môi trường:
 *   QP_BASE    (mặc định http://localhost/QP-Holdings)
 *   QP_USER / QP_PASS  (mặc định admin; thử lần lượt QP_PASS, minhhieu2002, admin123)
 *   QP_UPLOADS (thư mục uploads trên đĩa; mặc định suy ra từ vị trí plugin)
 */
const base = (process.env.QP_BASE || 'http://localhost/QP-Holdings').replace(/\/$/, '') + '/';

export default defineConfig({
  testDir: '.',
  timeout: 360_000,
  expect: { timeout: 15_000 },
  workers: 1,
  fullyParallel: false,
  reporter: [['list'], ['html', { open: 'never' }], ['json', { outputFile: 'results/report.json' }]],
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  use: { baseURL: base, storageState: '.auth/state.json', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [
    { name: 'functional', testMatch: /functional\.spec\.ts/, use: { ...devices['Desktop Chrome'] } },
    { name: 'chromium', testMatch: /browsers\.spec\.ts/, use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', testMatch: /browsers\.spec\.ts/, use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', testMatch: /browsers\.spec\.ts/, use: { ...devices['Desktop Safari'] } },
    { name: 'mobile-chrome', testMatch: /browsers\.spec\.ts/, use: { ...devices['Pixel 5'] } },
    { name: 'mobile-safari', testMatch: /browsers\.spec\.ts/, use: { ...devices['iPhone 13'] } },
  ],
});
