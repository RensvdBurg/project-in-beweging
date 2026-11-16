import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';

export async function browserChecks(t, base) {
  const browser = await chromium.launch();
  try {
    await mkdir('work/reports', { recursive: true });
    for (const width of [375, 768, 1440]) {
      await t.test(`Chromium ${width}px: layout, keyboard, forms and accessibility`, async () => {
        const context = await browser.newContext({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
        try {
          const page = await context.newPage();
          const errors = [], brokenAssets = [];
          page.on('pageerror', error => errors.push(`${page.url()}: ${error.message}`));
          page.on('response', response => { if (response.url().startsWith(base) && response.status() >= 400) brokenAssets.push(response.url()); });
          await page.goto(`${base}/project-in-beweging/index.php`);
          await page.locator('#loginForm').waitFor({ state: 'visible' });
          assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
          await page.keyboard.press('Tab');
          assert.equal(await page.locator('#loginEmail').evaluate(el => el === document.activeElement), true);
          for (const mode of ['login', 'register']) {
            if (mode === 'register') await page.locator('[data-mode="register"]').click();
            await page.locator(`#${mode}Form`).waitFor({ state: 'visible' });
            const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            assert.deepEqual(scan.violations.map(v => ({ rule: v.id, targets: v.nodes.map(n => n.target) })), []);
            await page.screenshot({ path: `work/reports/${mode}-${width}.png`, fullPage: true });
          }
          await page.locator('[data-mode="login"]').click();
          await page.locator('#loginEmail').fill('ci@example.invalid');
          await page.locator('#loginPassword').fill('Test-password-123!');
          await Promise.all([page.waitForURL('**/index.php'), page.locator('#loginForm button[type=submit]').click()]);
          await page.locator('#screen-home').waitFor({ state: 'visible' });
          assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
          await page.screenshot({ path: `work/reports/home-${width}.png`, fullPage: true });
          const homeScan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
          assert.deepEqual(homeScan.violations.map(v => ({ rule: v.id, targets: v.nodes.map(n => n.target) })), []);
          await page.locator('nav a[data-screen="leren"]').click();
          await page.locator('#screen-leren').waitFor({ state: 'visible' });
          await page.locator('#challenge-search').fill('does-not-exist');
          assert.equal(await page.locator('#challenge-list > li:visible').count(), 0);
          await page.locator('#challenge-search').fill('CI');
          assert.equal(await page.locator('#challenge-list > li:visible').count(), 1);
          await page.locator('nav a[data-screen="ranglijst"]').click();
          await page.locator('#screen-ranglijst').waitFor({ state: 'visible' });

          await Promise.all([
            page.waitForURL('**/pages/settings/settings.php'),
            page.locator('nav a[href*="settings.php"]').click(),
          ]);
          await page.waitForTimeout(600);
          errors.length = 0;
          const learnLink = page.locator('nav a[data-screen="leren"]');
          assert.match(await learnLink.getAttribute('href'), /transition=none/);
          await learnLink.click();
          await page.locator('#screen-leren').waitFor({ state: 'visible' });
          await page.waitForTimeout(50);
          assert.deepEqual(errors, []);
          assert.equal(await page.evaluate(() => new URL(location.href).searchParams.get('transition')), 'none');
          assert.equal(await page.locator('link[href*="page-transition.css"]').count(), 0);

          await page.locator('nav a[data-screen="ranglijst"]').click();
          await page.locator('#screen-ranglijst').waitFor({ state: 'visible' });
          assert.equal(await page.evaluate(() => new URL(location.href).searchParams.has('transition')), false);
          await page.waitForTimeout(600);

          await Promise.all([
            page.waitForURL('**/pages/statistics/statistics.php'),
            page.locator('nav a[data-screen="stats"]').click(),
          ]);
          const statisticsHeader = page.locator('main.upmove-statistics > header');
          await statisticsHeader.waitFor({ state: 'visible' });
          assert.equal(await statisticsHeader.evaluate(element => getComputedStyle(element).animationName), 'settings-section-enter');
          await page.waitForTimeout(600);
          errors.length = 0;

          const statisticsLearnLink = page.locator('nav a[data-screen="leren"]');
          assert.match(await statisticsLearnLink.getAttribute('href'), /transition=none/);
          await statisticsLearnLink.click();
          await page.locator('#screen-leren').waitFor({ state: 'visible' });
          await page.waitForTimeout(50);
          assert.deepEqual(errors, []);
          assert.equal(await page.locator('link[href*="page-transition.css"]').count(), 0);

          assert.deepEqual(errors, []);
          assert.deepEqual(brokenAssets, []);
        } finally { await context.close(); }
      });
    }
  } finally { await browser.close(); }
}
