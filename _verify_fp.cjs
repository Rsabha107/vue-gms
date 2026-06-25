const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();

  // Use the Inertia-based login: fill the form and let Vue handle submission
  await page.goto('http://vue-gms.test/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);

  // Fill fields
  await page.fill('#email', 'rsabha@gmail.com');
  await page.fill('#password', 'password');

  // Wait for Vue to be ready, then let the Inertia form handle submission
  // Instead of clicking the submit button (which does Inertia POST),
  // we need to use evaluate to trigger the form submission via Inertia
  await page.evaluate(() => {
    // Find the form element and trigger its submit event through Vue
    const form = document.querySelector('form');
    if (form) {
      const event = new Event('submit', { bubbles: true, cancelable: true });
      form.dispatchEvent(event);
    }
  });

  await page.waitForTimeout(3000);
  console.log('After form dispatch URL:', page.url());

  // If still on login, try clicking the button and wait for navigation via Inertia
  if (page.url().includes('login')) {
    // Inertia handles clicks on button type=submit within the form component
    // Let's try clicking while intercepting network
    await page.goto('http://vue-gms.test/login', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    await page.fill('#email', 'rsabha@gmail.com');
    await page.fill('#password', 'password');
    await page.waitForTimeout(200);

    // Click and wait for Inertia navigation
    await Promise.all([
      page.waitForResponse(r => r.url().includes('/login') && r.request().method() === 'POST', { timeout: 10000 }).catch(() => null),
      page.click('button[type="submit"]')
    ]);

    await page.waitForTimeout(3000);
    console.log('After click+wait URL:', page.url());
  }

  const scratchpad = 'C:/Users/R8257~1.SAB/AppData/Local/Temp/claude/c--laragon-www-vue-gms/b65612fa-6822-47f3-955c-c5fe777ac761/scratchpad';

  // Navigate to floorplans
  await page.goto('http://vue-gms.test/gms/floorplans', { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);
  console.log('Floorplans URL:', page.url());

  // If redirected to login, we're not authenticated
  if (page.url().includes('login')) {
    console.log('AUTH FAILED - cannot view floorplans');
    await page.screenshot({ path: `${scratchpad}/floorplan-initial.png` });
  } else {
    await page.screenshot({ path: `${scratchpad}/floorplan-initial.png`, fullPage: false });
    console.log('Screenshot 1 saved');

    // Click Sample button
    try {
      await page.locator('button', { hasText: 'Sample' }).first().click({ timeout: 3000 });
      await page.waitForTimeout(2000);
      console.log('Clicked Sample');
    } catch {
      try {
        await page.locator('button', { hasText: 'Load sample' }).click({ timeout: 3000 });
        await page.waitForTimeout(2000);
        console.log('Clicked Load sample');
      } catch {
        console.log('No sample buttons found');
      }
    }

    await page.screenshot({ path: `${scratchpad}/floorplan-sample.png`, fullPage: false });
    console.log('Screenshot 2 saved');
  }

  await browser.close();
})();
