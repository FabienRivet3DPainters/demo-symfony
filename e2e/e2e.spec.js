// @ts-check
const { test, expect } = require('@playwright/test');

// Les credentials viennent de .env.local (local) ou GitHub Secrets (CI)
// Jamais de valeurs en dur ici
const EMAIL    = process.env.E2E_USER_EMAIL    ?? '';
const PASSWORD = process.env.E2E_USER_PASSWORD ?? '';

// ── Scénario 1 : Page d'accueil publique ─────────────────────────────────────
test('page accueil accessible sans connexion', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/Épouvante/);
  await expect(page.locator('.navbar-brand')).toBeVisible();
  await expect(page.locator('.hero')).toBeVisible();
});

// ── Scénario 2 : Flux de connexion ───────────────────────────────────────────
test('login utilisateur affiche lien déconnexion', async ({ page }) => {
  test.skip(!EMAIL || !PASSWORD, 'E2E_USER_EMAIL / E2E_USER_PASSWORD non définis');

  await page.goto('/login');
  await page.fill('input[name="_username"]', EMAIL);
  await page.fill('input[name="_password"]', PASSWORD);
  await page.click('button[type="submit"]');
  await expect(page.locator('.btn-nav-logout')).toBeVisible();
});

// ── Scénario 3 : Accès catalogue après connexion ─────────────────────────────
test('catalogue produits visible après connexion', async ({ page }) => {
  test.skip(!EMAIL || !PASSWORD, 'E2E_USER_EMAIL / E2E_USER_PASSWORD non définis');

  await page.goto('/login');
  await page.fill('input[name="_username"]', EMAIL);
  await page.fill('input[name="_password"]', PASSWORD);
  await page.click('button[type="submit"]');
  await page.goto('/product');
  await expect(page.locator('.crud-table')).toBeVisible();
});