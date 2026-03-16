const { chromium } = require('playwright');

(async () => {

  if (process.argv.length < 5) {
    console.log(`
Usage:
node moodle_screenshot.js <moodle_url> <page_url> <output.png> [selectors...]

Selectors: CSS | text=TEXT | role=ROLE,name=NAME (e.g. role=link,name=הפעל עריכה).

Example (Classic theme, step 2):
node moodle_screenshot.js https://dev.moodle \\
  https://dev.moodle/course/view.php?id=5 \\
  screenshot.png "role=link,name=הפעל עריכה" "[data-action='open-chooser']" "text=מטלה"
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
  // תבנית Classic 4.5: role=link,name=הפעל עריכה | text=מטלה | CSS
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
        // name as string = substring match; מתאים ל-Classic "הפעל עריכה" / "Turn editing on"
        element = page.getByRole(role, { name: name }).first();
      } else {
        element = page.locator(selector).first();
      }
      await element.waitFor({ state: 'visible', timeout: 15000 });
      await element.click({ force: true });
      // אם הלחיצה גרמה לניווט (למשל "הפעל עריכה") – מחכים לטעינת הדף
      await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
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