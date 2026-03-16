const { chromium } = require('playwright');

(async () => {

  if (process.argv.length < 5) {
    console.log(`
Usage:
node moodle_screenshot.js <moodle_url> <page_url> <output.png> [selectors...]

Selectors: CSS (e.g. "#region-main") or text (e.g. text=מטלה or text=Add an activity or resource).

Example:
node moodle_screenshot.js https://dev.moodle \\
  https://dev.moodle/course/view.php?id=5 \\
  screenshot.png "text=הוסף פעילות או משאב" "text=מטלה"
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

  // לחיצה על selectors אם נשלחו (כל בורר אופציונלי – אם לא נמצא, מדלגים וממשיכים)
  // בורר לפי טקסט: "text=מטלה" או "text=Add an activity or resource" – לוחץ על אלמנט שמכיל את הטקסט
  for (const selector of selectors) {
    console.log("Clicking:", selector);
    try {
      const isTextSelector = /^text=/i.test(selector.trim());
      const element = isTextSelector
        ? page.getByText(selector.replace(/^text=/i, '').trim(), { exact: false }).first()
        : page.locator(selector).first();
      await element.waitFor({ state: 'visible', timeout: 15000 });
      await element.click({ force: true });
      await page.waitForTimeout(1500);
    } catch (err) {
      console.warn("Warning: selector not found or not clickable, skipping:", selector, err.message);
    }
  }

  // צילום מסך
  console.log("Saving screenshot:", output);

  await page.screenshot({
    path: output,
    //fullPage: true
  });

  await browser.close();

  console.log("Done");

})();