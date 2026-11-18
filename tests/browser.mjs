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
          page.on('pageerror', error => errors.push(error.message));
          page.on('response', response => { if (response.url().startsWith(base) && response.status() >= 400) brokenAssets.push(response.url()); });
          await page.goto(`${base}/project-in-beweging/index.php`);
          await page.locator('#loginForm').waitFor({ state: 'visible' });
          assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
          await page.keyboard.press('Tab');
          assert.equal(await page.locator('#loginEmail').evaluate(el => el === document.activeElement), true);
          for (const mode of ['login', 'register']) {
            if (mode === 'register') {
              await page.emulateMedia({ reducedMotion: 'no-preference' });
              await page.locator('[data-mode="register"]').click();
              assert.equal(
                await page.locator('#register').evaluate(element => getComputedStyle(element).animationName),
                'auth-panel-enter-forward',
              );
              await page.waitForTimeout(500);
            }
            await page.locator(`#${mode}Form`).waitFor({ state: 'visible' });
            const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            assert.deepEqual(scan.violations.map(v => ({ rule: v.id, targets: v.nodes.map(n => n.target) })), []);
            if (mode === 'register') {
              const day = page.getByRole('combobox', { name: 'Dag' });
              await day.click();
              const dayMenu = page.getByRole('listbox', { name: 'Dag' });
              const glassStyle = await dayMenu.evaluate(element => ({
                borderRadius: getComputedStyle(element).borderRadius,
                backdropFilter: getComputedStyle(element).backdropFilter,
                transitionDuration: getComputedStyle(element).transitionDuration,
                transitionProperty: getComputedStyle(element).transitionProperty,
                scrollBehavior: getComputedStyle(element).scrollBehavior,
              }));
              assert.equal(glassStyle.borderRadius, '18px');
              assert.match(glassStyle.backdropFilter, /blur\(/);
              assert.match(glassStyle.transitionDuration, /0\.18s/);
              assert.match(glassStyle.transitionProperty, /transform/);
              assert.equal(glassStyle.scrollBehavior, 'smooth');
              await dayMenu.getByRole('option', { name: '6', exact: true }).click();
              assert.equal(await page.locator('#birthDay').inputValue(), '6');

              const month = page.getByRole('combobox', { name: 'Maand' });
              await month.focus();
              await page.keyboard.press('ArrowDown');
              await page.keyboard.press('Enter');
              assert.equal(await page.locator('#birthMonth').inputValue(), '1');

              const year = page.getByRole('combobox', { name: 'Jaar' });
              await year.focus();
              await page.keyboard.press('ArrowDown');
              await page.keyboard.press('End');
              const yearMenu = page.locator('#birthYearOptions');
              const initialScroll = await yearMenu.evaluate(element => element.scrollTop);
              await page.waitForTimeout(100);
              const movingScroll = await yearMenu.evaluate(element => element.scrollTop);
              await page.waitForTimeout(500);
              const settledScroll = await yearMenu.evaluate(element => element.scrollTop);
              assert.ok(movingScroll > initialScroll);
              assert.ok(settledScroll > movingScroll);
              await page.keyboard.press('Enter');
              assert.ok(await page.locator('#birthYear').inputValue());

              await page.emulateMedia({ reducedMotion: 'reduce' });
              const reducedMotionStyle = await yearMenu.evaluate(element => ({
                scrollBehavior: getComputedStyle(element).scrollBehavior,
                transitionDuration: getComputedStyle(element).transitionDuration,
              }));
              assert.equal(reducedMotionStyle.scrollBehavior, 'auto');
              assert.ok(Number.parseFloat(reducedMotionStyle.transitionDuration) < 0.001);
              await page.emulateMedia({ reducedMotion: 'no-preference' });
            }
            await page.screenshot({ path: `work/reports/${mode}-${width}.png`, fullPage: true });
            if (mode === 'login') {
              await page.locator('#loginEmail').fill('validation-check@example.invalid');
              await page.locator('#loginForm').evaluate(form => form.requestSubmit());
              const validationPopover = page.locator('#authValidationPopover');
              await validationPopover.waitFor({ state: 'visible' });
              assert.equal(await validationPopover.textContent(), '!Vul dit veld in.');
              assert.equal(await page.locator('#loginPassword').getAttribute('aria-invalid'), 'true');
              const popoverStyle = await validationPopover.evaluate(element => ({
                borderRadius: getComputedStyle(element).borderRadius,
                backdropFilter: getComputedStyle(element).backdropFilter,
                animationName: getComputedStyle(element).animationName,
              }));
              assert.equal(popoverStyle.borderRadius, '18px');
              assert.match(popoverStyle.backdropFilter, /blur\(/);
              assert.equal(popoverStyle.animationName, 'auth-validation-pop-in');
              await page.locator('#loginEmail').fill('');
            }
          }
          await page.locator('#registerEmail').fill('popup-check@example.invalid');
          await page.locator('#registerPassword').fill('Test-password-123!');
          await page.locator('#confirmPassword').fill('Different-password-123!');
          await page.locator('#fullName').fill('Popup Check');
          await page.locator('#username').fill('popup_check');
          await Promise.all([
            page.waitForURL('**/index.php'),
            page.locator('#registerForm button[type=submit]').click(),
          ]);
          const registerStatus = page.locator('#registerStatus');
          await registerStatus.waitFor({ state: 'visible' });
          assert.match(await registerStatus.textContent(), /Wachtwoorden komen niet overeen/);
          assert.equal(await registerStatus.evaluate(element => getComputedStyle(element).animationName), 'auth-message-pop-in');
          await page.locator('[data-mode="login"]').click();
          assert.equal(
            await page.locator('#login').evaluate(element => getComputedStyle(element).animationName),
            'auth-panel-enter-backward',
          );
          await page.waitForTimeout(500);
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
          assert.deepEqual(errors, []);
          assert.deepEqual(brokenAssets, []);
        } finally { await context.close(); }
      });
    }
  } finally { await browser.close(); }
}
