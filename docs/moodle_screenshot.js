const { chromium } = require('playwright');

(async () => {

  if (process.argv.length < 5) {
    console.log(`
Usage:
node moodle_screenshot.js <moodle_url> <page_url> <output.png> [selectors...]

Example:
node moodle_screenshot.js https://moodle.site \
https://moodle.site/course/view.php?id=5 \
screenshot.png "#region-main" ".btn-primary"
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

  // מילוי פרטי התחברות
  await page.fill('#username', username);
  await page.fill('#password', password);

  // שליחת טופס
  await page.click('#loginbtn');

  await page.waitForLoadState('networkidle');

  console.log("Login successful");

  // מעבר לדף המבוקש
  console.log("Opening page:", pageUrl);

  await page.goto(pageUrl);

  await page.waitForLoadState('networkidle');

  // לחיצה על selectors אם נשלחו
  for (const selector of selectors) {

    console.log("Clicking:", selector);

    await page.waitForSelector(selector, { timeout: 5000 });

    await page.click(selector);

    await page.waitForTimeout(1000);
  }

  // צילום מסך
  console.log("Saving screenshot:", output);

  await page.screenshot({
    path: output,
    fullPage: true
  });

  await browser.close();

  console.log("Done");

})();