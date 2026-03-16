const { chromium } = require('playwright');

(async () => {

  if (process.argv.length < 5) {
    console.log(`
Usage:
node moodle_screenshot.js <moodle_url> <page_url> <output.png> [selectors...]

Selectors: CSS | text=TEXT | role=ROLE,name=NAME
`);
    process.exit(1);
  }

  const moodleUrl = process.argv[2];
  const pageUrl = process.argv[3];
  const output = process.argv[4];
  const selectors = process.argv.slice(5);

  const username = 'admin';
  const password = 'Aa123456!';

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  console.log("Opening login page...");
  await page.goto(`${moodleUrl}/login/index.php`);

  await page.fill('#username', username);
  await page.fill('#password', password);
  await page.click('#loginbtn');

  await page.waitForLoadState('networkidle');
  console.log("Login successful");

  console.log("Opening page:", pageUrl);
  await page.goto(pageUrl);
  await page.waitForLoadState('networkidle');

  for (const selector of selectors) {

    console.log("Clicking:", selector);

    try {

      const s = selector.trim();
      let element;

      if (/^text=/i.test(s)) {

        const text = s.replace(/^text=/i, '').trim();
        element = page.getByText(text, { exact: false }).first();

      } else if (/^role=/i.test(s)) {

        const match = s.match(/^role=(\w+),name=(.+)$/i);
        if (!match) throw new Error('role selector format: role=ROLE,name=NAME');

        const role = match[1].toLowerCase();
        const name = match[2].trim();

        element = page.getByRole(role, { name }).first();

      } else {

        element = page.locator(selector).first();

      }

      // מחכים רק לקיום האלמנט
      await element.waitFor({ state: 'attached', timeout: 15000 });

      // גלילה
      await element.scrollIntoViewIfNeeded().catch(() => {});

      // hover (חשוב ל-Moodle)
      await element.hover().catch(() => {});

      // ניסיון קליק
      await element.click({ force: true });

      // אם יש ניווט
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});

      await page.waitForTimeout(1200);

    } catch (err) {

      console.warn("Warning: selector failed, skipping:", selector, err.message);

    }

  }

  console.log("Saving screenshot:", output);

  await page.screenshot({
    path: output
  });

  await browser.close();

  console.log("Done");

})();