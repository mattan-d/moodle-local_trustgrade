#!/bin/bash
# יצירת צילומי מסך למדריך TrustGrade (עברית)
# מותאם ל-Moodle 4.5 (למשל: text=הפעלת עריכה, .always-visible button[data-action=open-chooser], text=מטלה).
# הרצה: מתוך שורש הפרויקט – ./guide_he/screenshot_commands.sh
#        או מתוך docs – bash guide_he/screenshot_commands.sh
# דרוש: node, playwright (npm install מתוך docs אם צריך)
# עדכן moodle_screenshot.js עם כתובת, משתמש וסיסמה אם נדרש.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DOCS_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$DOCS_DIR"

BASE="https://dev.moodle"
COURSE="$BASE/course/view.php?id=65"
# Moodle 4.x Boost: אין להשתמש ב-edit=1 ב-URL (דורש sesskey) – יש ללחוץ על "הפעל עריכה" בדף
ASSIGN="$BASE/mod/assign/view.php?id=356"
OUT="guide_he/screenshots"

mkdir -p "$OUT"

# 0 – הגדרות TrustGrade במערכת (Boost: #region-main)
node moodle_screenshot.js "$BASE" "$BASE/admin/settings.php?section=local_trustgrade" "$OUT/00-admin-trustgrade.png" "#region-main"

# 1 – עמוד הקורס (לפני הוספת מטלה)
node moodle_screenshot.js "$BASE" "$COURSE" "$OUT/01-course-view.png" "#region-main"

# 2 – הוספת פעילות – בחירת מטלה (text=הפעלת עריכה → .always-visible button[data-action=open-chooser] → text=מטלה)
S2_EDIT='text=הפעלת עריכה'
S2_CHOOSER='.always-visible button[data-action="open-chooser"]'
node moodle_screenshot.js "$BASE" "$COURSE" "$OUT/02-add-activity.png" "$S2_EDIT" "$S2_CHOOSER"

# 3 – טופס עריכת מטלה (Boost: תפריט פעולה – Bootstrap 5)
node moodle_screenshot.js "$BASE" "$ASSIGN" "$OUT/03-edit-assignment.png" "#region-main" 'button[data-bs-toggle="dropdown"]' 'a[href*="editsettings"]'

# 4 – לשונית TrustGrade בהגדרות המטלה (קישור למקטע #local_trustgrade בטופס)
node moodle_screenshot.js "$BASE" "$BASE/mod/assign/view.php?id=356&action=editsettings" "$OUT/04-trustgrade-tab.png" "#region-main" 'a[href="#local_trustgrade"]'

# 5 – תפריט המטלה – פתיחת תפריט הפעולות (Boost: dropdown)
node moodle_screenshot.js "$BASE" "$ASSIGN" "$OUT/05-assign-menu-question-bank.png" "#region-main" 'button[data-bs-toggle="dropdown"]'

# 6 – עמוד מאגר השאלות (TrustGrade: #question-bank-container או אזור ראשי)
node moodle_screenshot.js "$BASE" "$BASE/local/trustgrade/question_bank.php?cmid=356" "$OUT/06-question-bank-page.png" "#region-main"

# 7 – בדיקת הנחיות עם AI (כרטיס ההנחיות + כפתור בדיקה)
node moodle_screenshot.js "$BASE" "$BASE/local/trustgrade/question_bank.php?cmid=356" "$OUT/07-check-instructions-ai.png" "#trustgrade-instruction-review" "#check-instructions-btn"

# 8 – יצירת שאלות מהקבצים (כרטיס עם card-header "יצירת שאלות מהקבצים")
node moodle_screenshot.js "$BASE" "$BASE/local/trustgrade/question_bank.php?cmid=356" "$OUT/08-generate-from-files.png" "#region-main"

# 9 – עמוד הגשת סטודנט + גילוי
node moodle_screenshot.js "$BASE" "$BASE/mod/assign/view.php?id=356&action=editsubmission" "$OUT/09-student-submit-disclosure.png" "#region-main"

# 10 – ממשק שאלון הבקיאות (דורש submissionid תקף – להחליף XXX)
# node moodle_screenshot.js "$BASE" "$BASE/local/trustgrade/quiz_interface.php?cmid=356&submissionid=XXX" "$OUT/10-quiz-interface.png" "#region-main"

# 11 – דוח TrustGrade
node moodle_screenshot.js "$BASE" "$BASE/local/trustgrade/quiz_report.php?cmid=356" "$OUT/11-trustgrade-report.png" "#region-main"

echo "Done. Check $OUT/*.png"
