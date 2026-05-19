import { expect, test } from '@playwright/test';

async function login(page) {
    await page.goto('/login');
    await page.getByLabel(/email/i).fill('admin@example.com');
    await page.locator('#password').fill('password');
    await page.getByRole('button', { name: /log in|sign in/i }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

async function openSidebar(page) {
    const toggle = page.locator('[data-sidebar-toggle]').first();

    if (await toggle.isVisible().catch(() => false)) {
        await toggle.click();
    }
}

async function openSidebarGroup(page, label) {
    const button = page.locator('.admin-nav-toggle', { hasText: label }).first();
    await button.scrollIntoViewIfNeeded();
    await button.click({ force: true });
}

test.describe('admin web UI', () => {
    test('dashboard is responsive and sidebar child links stay visible', async ({ page }) => {
        await login(page);
        await openSidebar(page);

        await expect(page.getByText('Jewellery Chit')).toBeVisible();
        await openSidebarGroup(page, 'Customers');
        await expect(page.getByRole('link', { name: /customer list/i })).toBeVisible();
        await expect(page.getByRole('link', { name: /add customer/i })).toBeVisible();

        await openSidebarGroup(page, 'Reports');
        await expect(page.getByRole('link', { name: /cashflow report/i })).toBeVisible();
    });

    test('customer create form shows ajax validation feedback', async ({ page }) => {
        await login(page);
        await page.goto('/customers/create');
        await page.locator('#name').fill('AJAX Validation Customer');
        await page.locator('#mobile').fill('9666601999');
        await page.locator('#address').evaluate((element) => element.removeAttribute('required'));
        await page.getByRole('button', { name: /save customer/i }).click();

        await expect(page.locator('[data-error-for="address"]')).toContainText(/required/i);
    });

    test('mobile navigation opens without hiding menu children', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await login(page);
        await openSidebar(page);

        await openSidebarGroup(page, 'Payments');
        await expect(page.locator('.admin-subnav-link', { hasText: 'Collect Payment' })).toBeVisible();
    });
});
