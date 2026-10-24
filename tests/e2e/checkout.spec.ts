import { expect, test } from '@playwright/test';
import { addFirstProductToCart } from './helpers';

test.describe('guest checkout', () => {
    test('a guest can pay cash on delivery and follows the order to the end', async ({ page }) => {
        test.setTimeout(120_000);

        const name = await addFirstProductToCart(page);
        await page.goto('/checkout');

        await page.locator('#field-first_name').fill('Awa');
        await page.locator('#field-last_name').fill('Koné');
        await page.locator('#field-email').fill(`e2e-${Date.now()}@example.com`);
        await page.locator('#field-phone').fill('0701020304');
        await page.locator('#field-city').fill('Abidjan');
        await page.locator('#field-quartier').fill('Cocody');

        const cash = page.locator('input[name="payment_method"][value="cash_on_delivery"]');
        await cash.check({ force: true });
        await expect(cash).toBeChecked();

        await page.getByRole('button', { name: /Confirmer et payer/ }).click();

        await expect(page).toHaveURL(/\/checkout\/success\/\d+\?signature=/);
        await expect(page.getByRole('heading', { name: 'Commande enregistrée' })).toBeVisible();
        await expect(page.getByText('Numéro de commande :')).toBeVisible();
        await expect(page.getByText(/ORD-[A-Z0-9]+/)).toBeVisible();
        await expect(page.locator('main')).toContainText(name);
        await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,nofollow');

        await page.goto('/cart');
        await expect(page.getByRole('heading', { name: 'Votre panier est vide' })).toBeVisible();
    });

    test('the server-side validation keeps the cart and lists the errors', async ({ page }) => {
        await addFirstProductToCart(page);
        await page.goto('/checkout');

        // What a browser without JavaScript (or with the HTML validation removed) sends
        await page.locator('form[data-checkout]').evaluate((form) => {
            (form as HTMLFormElement).noValidate = true;
        });
        await page.getByRole('button', { name: /Confirmer et payer/ }).click();

        await expect(page).toHaveURL(/\/checkout$/);
        await expect(page.locator('[data-error-summary]')).toBeVisible();
        await expect(page.locator('#field-first_name-error').getByText('Le prénom est obligatoire.')).toBeVisible();
        await expect(page.locator('#field-first_name')).toHaveAttribute('aria-invalid', 'true');

        await page.goto('/cart');
        await expect(page.locator('[data-cart-line]')).toHaveCount(1);
    });

    test('the checkout advertises the payment methods the shop enables', async ({ page }) => {
        await addFirstProductToCart(page);
        await page.goto('/checkout');

        await expect(page.getByText('Choisissez comment vous réglez cette commande.')).toBeVisible();
        await expect(page.locator('input[name="payment_method"][value="cash_on_delivery"]')).toHaveCount(1);
        await expect(page.getByText('Vous payez en espèces à la réception de votre commande.')).toBeVisible();
    });
});
