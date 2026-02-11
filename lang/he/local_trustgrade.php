<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_trustgrade.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin basic strings.
$string['pluginname'] = 'TrustGrade';
$string['plugindisabled'] = 'התוסף TrustGrade מושבת';
$string['trustgradedisabled'] = 'TrustGrade מושבת למשימה זו';

// Plugin settings.
$string['plugin_enabled'] = 'אפשר את תוסף TrustGrade';
$string['plugin_enabled_desc'] = 'אפשר או השבת את תוסף TrustGrade באופן גלובלי. כאשר מושבת, כל פונקציות TrustGrade יוסתרו מטפסי המשימה והדפים.';
$string['default_enabled'] = 'הפעל את התוסף באופן אוטומטי עבור כל מטלה חדשה';
$string['default_enabled_desc'] = 'כאשר אפשר, TrustGrade יופעל אוטומטית למשימות שנוצרו לאחרונה. המרצים עדיין יכולים להשבית אותו למשימות בודדות.';

// Assignment settings.
$string['trustgrade_enabled'] = 'הפעל את TrustGrade למשימה זו';
$string['trustgrade_enabled_desc'] = 'הפעל את תוסף מדד איכות אקדמית (TrustGrade) עבור מטלה זו.';
$string['trustgrade_tab'] = 'מדד איכות אקדמית (TrustGrade)';
$string['trustgrade_description'] = 'השתמש ב‑AI Gateway לניתוח והמלצה לשיפור הנחיות המשימה שלך.';
$string['save_assignment_first'] = 'אנא שמור את המשימה תחילה לפני יצירת שאלות';

// AI Gateway Settings.
$string['gateway_settings'] = 'הגדרות AI Gateway';
$string['gateway_settings_desc'] = 'הגדר את ה‑AI Gateway החיצוני לעיבוד AI. ה‑Gateway מטפל בכל הגדרות API כולל מפתח TrustGrade ובחירת דגם.';
$string['gateway_endpoint'] = 'כתובת URL של Endpoint ה‑Gateway';
$string['gateway_endpoint_desc'] = 'כתובת URL של נקודת הקצה של ה‑API של ה‑Gateway החיצוני (למשל: https://trustgrade.cloud/api)';
$string['gateway_token'] = 'אסימון אימות ל‑Gateway';
$string['gateway_token_desc'] = 'אסימון אימות לתקשורת מאובטחת עם ה‑Gateway';
$string['gateway_test'] = 'בדיקת חיבור ל‑Gateway';
$string['test_gateway_connection'] = 'בדוק חיבור ל‑Gateway';

// Instruction analysis.
$string['check_instructions'] = 'בדוק הנחיות המטלה בעזרת AI';
$string['ai_recommendation'] = 'המלצת בינה מלאכותית לשיפור הנחיות המטלה לפי הטקסונומיה של Bloom';
$string['processing'] = 'מעבד…';
$string['no_instructions'] = 'לא נמצאו הנחיות לניתוח';
$string['no_instructions_error'] = 'לא נמצאו הנחיות לניתוח';
$string['no_instructions_or_files'] = 'נדרש או הנחיות או לפחות קובץ אחד לניתוח';
$string['no_instructions_questions_error'] = 'לא נמצאו הנחיות ליצירת שאלות';

// Question generation.
$string['generate_questions'] = 'צור מאגר שאלות עם AI';
$string['generated_questions'] = 'שאלות שנוצרו';
$string['generating_questions'] = 'יוצרים שאלות דרך ה‑Gateway…';
$string['questions_generated_success'] = 'השאלות נוצרו ונשמרו בהצלחה!';
$string['questions_generated_successfully'] = 'השאלות נוצרו בהצלחה';
$string['error_saving_questions'] = 'שגיאה בשמירת השאלות שנוצרו';
$string['error_generating_questions'] = 'שגיאה ביצירת שאלות';
$string['processing_question_generation'] = 'מעבד יצירת שאלות';
$string['processing_question_generation_message'] = 'אנא המתן בזמן שאנו יוצרים שאלות למשימה שלך…';

// Auto‑generate questions.
$string['auto_generate_questions'] = 'צור בנק שאלות עבור מטלה זו בעזרת AI';
$string['auto_generate_questions_desc'] = 'צור בנק שאלות על בסיס הוראות המטלה באופן אוטומטי, עם שמירת המטלה.';
$string['questions_generation_failed'] = 'נכשל יצירת שאלות באופן אוטומטי';
$string['questions_generation_error'] = 'אירעה שגיאה במהלך יצירת שאלות אוטומטית';
$string['questions_will_be_generated'] = 'שאלות ייווצרו אוטומטית למשימה זו';

// Question bank.
$string['question_bank'] = 'מאגר שאלות';
$string['question_bank_description'] = 'נהל את השאלות שנוצרו על‑ידי AI. ניתן לצפות, לערוך, למחוק שאלות קיימות, או ליצור חדשות.';
$string['question_bank_title'] = 'מאגר שאלות';
$string['loading_question_bank'] = 'טוען מאגר שאלות…';
$string['no_questions_available'] = 'אין שאלות זמינות עבור משימה זו.';
$string['no_questions_found'] = 'לא נמצאו שאלות. צור כמה שאלות כדי להתחיל.';
$string['add_new_question'] = 'הוסף שאלה חדשה';

// Generate questions from files (question bank page).
$string['generate_from_files'] = 'צור שאלות מקבצים';
$string['upload_files_for_questions'] = 'העלאת קבצים';
$string['upload_files_for_questions_help'] = 'הוסף קובץ אחד או יותר (למשל PDF, Word, טקסט). השאלות ייווצרו על בסיס התוכן. ניתן גם להוסיף הוראות אופציונליות למטה.';
$string['number_of_questions'] = 'כמות שאלות';
$string['optional_instructions'] = 'הוראות אופציונליות (לא חובה)';
$string['optional_instructions_help'] = 'טקסט אופציונלי להנחיית יצירת השאלות (למשל התמקדות בנושאים מרכזיים, רמת קושי). השאר ריק לשימוש בתוכן הקבצים בלבד.';
$string['generate_questions_from_files'] = 'צור שאלות';
$string['generation_status_preparing'] = 'מייצר שאלות… אנא המתן.';
$string['generation_started'] = 'יצירת השאלות החלה. הסטטוס יתעדכן למטה.';

$string['generate_from_files_help'] = '<h3>יצירת שאלות לחידון באמצעות העלאת קבצים</h3>
<p>ניתן ליצור שאלות לחידון באופן אוטומטי על-ידי העלאת קבצים למערכת. המערכת תנתח את תוכן הקבצים ותיצור שאלות בהתאם לחומר המופיע בהם.</p>
<h4>כיצד זה עובד?</h4>
<p><strong>העלאת קבצים</strong><br>ניתן להעלות קבצים באמצעות גרירה ושחרור מהמחשב האישי אל אזור העלאת הקבצים.<br>מספר קבצים מרבי: עד 20 קבצים.<br>נפח קובץ מרבי: ללא הגבלה.</p>
<p><strong>בחירת כמות שאלות</strong><br>יש להגדיר את מספר השאלות שברצונכם ליצור (לדוגמה: 5 שאלות).</p>
<p><strong>הוראות אופציונליות (לא חובה)</strong><br>ניתן להוסיף הנחיות כלליות ל-AI, כגון דגשים לתוכן, נושאים מרכזיים להדגשה, רמת עומק רצויה או נקודות שחשוב לכלול.</p>
<p><strong>יצירת שאלות מהקבצים</strong><br>לאחר העלאת הקבצים והגדרת הכמות (והנחיות אם נוספו), המערכת תיצור שאלות המבוססות על התוכן שהועלה.</p>';

// Question fields.
$string['question'] = 'שאלה';
$string['question_text'] = 'טקסט השאלה';
$string['question_text_required'] = 'טקסט השאלה נדרש';
$string['enter_question_text'] = 'הכנס טקסט שאלה…';
$string['click_edit_add_question'] = 'לחץ ערוך להוספת טקסט שאלה';
$string['type'] = 'סוג';
$string['multiple_choice'] = 'בחירה מרובה';
$string['true_false'] = 'נכון/לא נכון';
$string['short_answer'] = 'תשובה קצרה';
$string['options'] = 'אפשרויות';
$string['option_text'] = 'טקסט אפשרות';
$string['optiontext'] = 'טקסט אפשרות';
$string['option_placeholder'] = 'הכנס טקסט אפשרות…';
$string['all_options_required'] = 'כל אפשרויות התשובה נדרשות';
$string['click_edit_add_options'] = 'לחץ ערוך להוספת אפשרויות תשובה';
$string['correct_answer'] = 'תשובה נכונה';
$string['correct_answer_required'] = 'נדרשת לפחות תשובה נכונה אחת';

// Events
$string['event_task_status_changed'] = 'סטטוס משימת TrustGrade השתנה';
$string['explanation'] = 'הסבר';
$string['enter_explanation_option'] = 'הכנס הסבר לאפשרויות זו…';
$string['explanation_for_true'] = 'הסבר ל‑נכון';
$string['explanation_for_false'] = 'הסבר ל‑לא נכון';
$string['points'] = 'נקודות';
$string['points_help'] = 'נקודות המוענקות עבור שאלה זו';
$string['level'] = 'רמה';
$string['blooms_level'] = 'רמת בלום';
$string['blooms_level_label'] = 'רמת בלום';

// Bloom’s taxonomy levels.
$string['blooms_remembering'] = 'זכרון';
$string['blooms_understanding'] = 'הבנה';
$string['blooms_applying'] = 'יישום';
$string['blooms_analyzing'] = 'ניתוח';
$string['blooms_evaluating'] = 'הערכה';

$string['mandatory_question'] = 'שאלת חובה';
$string['mandatory_question_help'] = 'שאלה זו תמיד תופיע בחידונים לסטודנטים';
$string['make_mandatory'] = 'סמן כחובה';
$string['remove_mandatory'] = 'הסר חובת מענה';
$string['question_marked_mandatory'] = 'השאלה סומנה כחובה';
$string['question_unmarked_mandatory'] = 'הוסרה חובת המענה מהשאלה';
$string['error_updating_question'] = 'שגיאה בעדכון השאלה';

// Question source types.
$string['question_source_instructor'] = 'מרצה';
$string['question_source_ai_generated'] = 'נוצר על‑ידי AI';
$string['question_source_submission'] = 'מבוסס על הגשה';
$string['instructor_question'] = 'שאלה של המרצה';
$string['based_on_submission'] = 'מבוסס על ההגשה שלך';

// Question actions.
$string['edit'] = 'ערוך';
$string['delete'] = 'מחק';
$string['save_changes'] = 'שמור שינויים';
$string['question_saved_success'] = 'השאלה נשמרה בהצלחה';
$string['confirm_delete_question'] = 'האם אתה בטוח שברצונך למחוק שאלה זו? פעולה זו אינה ניתנת לביטול.';
$string['confirm_delete_question_title'] = 'מחיקת שאלה';
$string['confirm_delete_question_message'] = 'האם אתה בטוח שברצונך למחוק שאלה זו? פעולה זו אינה ניתנת לביטול.';
$string['question_deleted_success'] = 'השאלה נמחקה בהצלחה';
$string['question_deleted_successfully'] = 'השאלה נמחקה בהצלחה';
$string['error_deleting_question'] = 'שגיאה במחיקת השאלה';

// Debug mode and caching.
$string['debug_mode'] = 'מצב דיבוג & מטמון';
$string['debug_mode_desc'] = 'הפעל מצב דיבוג כדי לשמור תגובות של ה‑Gateway במטמון ולהימנע מקריאות API חוזרות. כאשר מופעל, בקשות זהות יחזרו מתגובה שמורה במקום לקרוא שוב ל‑Gateway. זה משפר ביצועים ומפחית שימוש ב‑API במהלך פיתוח ובדיקה.';
$string['cleanup_debug_cache'] = 'נקה מטמון דיבוג של TrustGrade';
$string['cleanup_quiz_sessions'] = 'נקה מפגשי חידונים של TrustGrade';

// Cache management.
$string['cache_management'] = 'ניהול מטמון';
$string['cache_management_widget_desc'] = 'הצג סטטיסטיקות מטמון ונהל תגובות שמורות ישירות מדף ההגדרות.';
$string['cache_disabled_message'] = 'המטמון מושבת. הפעל מצב דיבוג למעלה כדי להפעיל שמירת תגובות.';
$string['cache_stats_error'] = 'שגיאה בטעינת סטטיסטיקות מטמון: {$a}';
$string['full_management'] = 'ניהול מלא';
$string['clear_all'] = 'נקה הכל';
$string['clear_instructions'] = 'נקה הנחיות';
$string['clear_questions'] = 'נקה שאלות';
$string['clear_submissions'] = 'נקה הגשות';
$string['cleanup_old'] = 'נקה ישנים';
$string['confirm_clear_cache'] = 'האם אתה בטוח שברצונך לנקות את כל התגובות שמורות? פעולה זו אינה ניתנת לביטול.';
$string['cache_hit'] = 'היה קליק במטמון – שימוש בתגובה שמורה';

// Cache action results.
$string['cache_cleared_success'] = 'כל התגובות השמורות נמחקו בהצלחה.';
$string['instructions_cache_cleared'] = 'מטמון ניתוח ההנחיות נמחק.';
$string['questions_cache_cleared'] = 'מטמון יצירת השאלות נמחק.';
$string['submissions_cache_cleared'] = 'מטמון שאלות מבוססות הגשה נמחק.';
$string['old_cache_cleaned'] = 'רשומות מטמון ישנות ננוקו בהצלחה.';
$string['cache_clear_error'] = 'שגיאה בניקוי המטמון: {$a}';
$string['invalid_action'] = 'ביקשת פעולה לא חוקית במטמון.';

// Quiz settings.
$string['quiz_settings_title'] = 'הגדרות חידון';
$string['questions_to_generate'] = 'מספר השאלות ליצירה';
$string['questions_to_generate_help'] = 'מספר כולל של שאלות לכלול בחידון';
$string['question_distribution'] = 'הגדרת תמהיל השאלות (כמה מבנק השאלות וכמה על בסיס ההגשה)';
$string['instructor_questions'] = 'שאלות ממאגר המרצה';
$string['instructor_questions_help'] = 'מספר שאלות לבחור ממאגר שאלות המרצה';
$string['submission_questions'] = 'שאלות מבוססות הגשות';
$string['submission_questions_help'] = 'מספר שאלות ליצור על‑פי הגשות הסטודנטים';
$string['randomize_answers'] = 'Shuffle סדר התשובות';
$string['randomize_answers_desc'] = 'ערבב באופן אקראי את סדר אפשרויות התשובה לשאלות בחירה מרובה.';
$string['time_per_question'] = 'זמן לכל שאלה';
$string['time_per_question_help'] = 'הזמן המרבי המותר לכל שאלה בשניות. טיימר ירידה יוצג עבור כל שאלה.';

// Disclosure settings.
$string['disclosure_settings'] = 'הגדרות גילוי לסטודנט';
$string['disclosure_settings_desc'] = 'הגדר כיצד התלמידים יקבלו מידע על תכונות בינה מלאכותית במשימות.';
$string['show_disclosure'] = 'הצג הודעת גילוי AI';
$string['show_disclosure_desc'] = 'הצג הודעת גילוי לסטודנטים לפני שליחת המשימה, שמודיעה על תכונת חידון מבוססת AI.';
$string['custom_disclosure_message'] = 'הודעה גילוי מותאמת';
$string['custom_disclosure_message_desc'] = 'הודעה מותאמת להצגה במקום ההודעה המוגדרת. השאר ריקה לשימוש בהודעת ברירת מחדל.';

// AI disclosure messages.
$string['ai_disclosure_title'] = 'חוויה לימודית משולבת AI';
$string['ai_disclosure_message'] = 'לאחר הגשת העבודה, מערכת הבינה מלאכותית תנתח את עבודתך בזמן אמת ותיצור עבורך חידון מותאם אישית שיעזור לך לבדוק ולחזק את שליטך בחומר הנלמד.';
$string['ai_disclosure_details_toggle'] = 'למידע נוסף על אופן הפעולה';
$string['ai_disclosure_detail_analysis'] = 'המטלה שהגשת תנותח על ידי בינה מלאכותית כדי להעריך את שליטך בחומר הנלמד';
$string['ai_disclosure_detail_questions'] = 'ה‑AI ייצור {$a} שאלות מותאמות אישית על‑פי ההגשה שלך.';
$string['ai_disclosure_detail_quiz'] = 'תשתתף בחידון של {$a} שאלות שמשלב שאלות של המרצה וגם שאלות מותאמות אישית.';
$string['ai_disclosure_detail_timer'] = 'לכל שאלה בחידון יש הגבלת זמן של {$a} שניות לעידוד חשיבה מהירה.';
$string['ai_disclosure_detail_privacy'] = 'נתוני ההגשה שלך מעובדים בצורה מאובטחת ונמצאים בשימוש למטרות חינוכיות בלבד.';

// Quiz interface.
$string['ai_quiz_title'] = 'חידון שנוצר על‑ידי AI';
$string['ai_quiz_report'] = 'דוח חידון AI';
$string['quiz_ready_message'] = 'חידון AI שלך מוכן! חידון זה יעזור לך להרהר בהגשתך ולהעמיק את הלמידה.';
$string['quiz_ready_subject'] = 'חידון ה-AI שלך מוכן';
$string['quiz_ready_message_html'] = 'חידון ה-AI שלך מוכן! לחץ <a href="{$a->quizurl}">כאן</a> כדי לגשת לחידון.';
$string['quiz_generation_failed_subject'] = 'יצירת החידון נכשלה';
$string['quiz_generation_failed_message'] = 'לא הצלחנו ליצור את החידון שלך. אנא פנה למרצה שלך.';
$string['take_quiz'] = 'גש לחידון';
$string['next'] = 'הבא';
$string['next_question'] = 'שאלה הבאה →';
$string['finish_quiz'] = 'סיים חידון';
$string['submit_final_answers'] = 'שלח תשובות סופיות';
$string['seconds'] = 'שניות';
$string['text'] = 'טקסט';
$string['entertext'] = 'הכנס טקסט';
$string['enter_answer_placeholder'] = 'הכנס את תשובתך כאן…';
$string['quiz_preparing'] = 'החי��ון שלך נמצא בהכנה...';
$string['quiz_preparing_message'] = 'נוצר חידון עבור {$a}';
$string['quiz_ready'] = 'החידון שלך מוכן. לחץ/י כאן להתחלה!';
$string['quiz_failed'] = 'יצירת החידון נכשלה';
$string['quiz_failed_message'] = 'נתקלנו בבעיה ביצירת החידון שלך עבור {$a}';
$string['invalid_file_type_error'] = 'לא ניתן לעבד את ההגשה שלך: נתמכים רק קבצי טקסט (PDF, DOC, DOCX, TXT) וקבצי תמונה (JPEG, PNG, GIF). אנא שלח מחדש את העבודה שלך בפורמט קובץ מתאים.';
	
// Quiz progress and navigation.
$string['progress_auto_saved'] = 'ההתקדמות נשמרת אוטומטית. רענון הדף יחזור מהשאלה הזו.';
$string['provide_answer_warning'] = 'אנא ספק תשובה לפני המשך. זכור: לא תוכל לחזור לשאלה זו מאוחר יותר.';
$string['quiz_started_notice'] = 'חידון התחיל. זכור: לא תוכל לחזור לשאלות קודמות או להתחיל מחדש.';
$string['failed_start_session'] = 'נכשל התחלת מפגש החידון';
$string['quiz_progress_saved'] = 'ההתקדמות בחידון נשמרה אוטומטית. החידון ישוב למקום שבו הפסקת כשתחזור.';
$string['quiz_progress_complete'] = '{$a}% הושלם';
$string['question_x_of_y'] = 'שאלה {$a->current} מתוך {$a->total}';
$string['time_remaining'] = 'זמן שנותר: {$a}';

// Quiz completion.
$string['quiz_completed_header'] = 'חידון הושלם';
$string['quiz_completed_message'] = 'ההערכה הרשמית שלך הוגשה בהצלחה ולא ניתן לחזור עליה.';
$string['your_answer'] = 'התשובה שלך: {$a}';
$string['correct_answer_was'] = 'התשובה הנכונה היתה: {$a}';
$string['final_grade_notice'] = 'זוהי הציון הסופי שלך עבור הערכה זו.';
$string['final_score'] = 'תוצאה סופית: {$a->score}/{$a->total} ({$a->percentage}%)';
$string['failed_save_results'] = 'נכשל שמירת תוצאות החידון: {$a}';
$string['failed_save_contact_instructor'] = 'נכשל שמירת התוצאות הסופיות. צור קשר עם המרצה שלך.';

// Quiz integrity.
$string['integrity_report_header'] = 'דוח יושרה';
$string['integrity_recorded'] = 'מידע זה נרשם לעיון.';
$string['integrity_violation_header'] = 'הפרת יושרה בהערכה';
$string['quiz_flagged'] = 'ניסיון החידון שלך סומן לפעילות חשודה.';
$string['incident_logged'] = 'האירוע נרשם וייבדק על‑ידי המרצה שלך.';
$string['progress_saved_cannot_continue'] = 'ההתקדמות הנוכחית נשמרה, אך אינך יכול להמשיך בהערכה.';
$string['dev_tools_blocked'] = 'גישה לכלי מפתח אינה מותרת במהלך החידון.';
$string['window_switching_warning'] = 'אזהרה: החלפת חלונות/כרטיסיות {$a->count} פעמים. מקסימום מותר: {$a->max}. החלפה מופרזת עשויה להוביל להפסקת החידון.';
$string['window_focus_lost'] = 'אובדן פוקוס חלון {$a} פעם(ות) במהלך החידון.';
$string['exceeded_window_switches'] = 'חצית את המקסימום המותר להחלפת חלונות ({$a}). החידון הופסק.';

// Quiz instructions.
$string['understand_start_quiz'] = 'אני מבין – התחל חידון';
$string['important_formal_assessment'] = 'חשוב: הערכה רשמית';
$string['read_carefully'] = 'אנא קרא היטב לפני ההתחלה:';
$string['one_attempt_only'] = 'ניסיון אחד בלבד: יש לך רק ניסיון אחד להשלים חידון זה.';
$string['no_going_back'] = 'אין חזרה: לאחר מעבר לשאלה הבאה, לא ניתן לחזור לשאלות קודמות.';
$string['no_restarts'] = 'אין התחלה מחדש: רענון הדף לא יתחיל מחדש את החידון – הוא ישוב מאיפה שהיה.';
$string['time_limits'] = 'הגבלות זמן: לכל שאלה יש מגבלת זמן מחמירה. החידון יעבור אוטומטית כשהזמן יפוג.';
$string['no_cheating'] = 'אין לרמות: זהו מבחן רשמי. כל ניסיון לרמות או להתערב בפעילות המבחן יזוהה.';
$string['stay_focused'] = 'השאר מרוכז: החלפות חלונות או כרטיסיות מרובות עשויות להיחשב כהתנהגות חשודה.';
$string['cannot_restart_notice'] = 'לאחר לחיצה על "התחל חידון", לא תוכל להתחיל מחדש או לקחת את ההערכה שוב.';

// Quiz report.
$string['quiz_report'] = 'דוח חידון';
$string['quiz_report_assignment_desc'] = 'דוח חידון AI עבור משימה זו';
$string['quiz_report_course_desc'] = 'דוח חידון AI עבור כל המשימות בקורס זה';
$string['quiz_report_all_desc'] = 'דוח חידון AI עבור כל המשימות בכל הקורסים';
$string['back_to_assignment'] = 'חזרה למשימה';
$string['back_to_course'] = 'חזרה לקורס';
$string['quiz_score'] = 'ציון חידון';
$string['details'] = 'פרטים';
$string['view_details'] = 'הצג פרטים';

// Quiz details.
$string['quiz_details'] = 'פרטי חידון';
$string['session_info'] = 'מידע על המפגש';
$string['completed_on'] = 'הושלם ב‑';
$string['time_taken'] = 'זמן שנדרש';
$string['student_answer'] = 'תשובה הסטודנט';
$string['result'] = 'תוצאה';
$string['correct'] = 'נכון';
$string['incorrect'] = 'לא נכון';
$string['no_completed_quizzes'] = 'לא ישנם סטודנטים שהשלימו את חידון AI למשימה זו עדיין.';
$string['integrity_summary'] = 'סיכום יושרה';
$string['window_blur_events'] = 'אירועי Blur של חלונות';
$string['no_answer'] = 'אין תשובה';
$string['true'] = 'נכון';
$string['false'] = 'לא נכון';
$string['not_available'] = 'לא זמין';
$string['manual_grading_required'] = 'נדרש ניקוד ידני';
$string['integrity_violations_count'] = 'סך כל הפרות היושרה: {$a}';

// Answer display strings.
$string['raw_answer_value'] = 'תשובה גולמית';
$string['invalid_option_selected'] = 'נבחרה אפשרות לא חוקית';
$string['invalid_boolean_value'] = 'ערך בוליאני לא חוקי';
$string['unknown_question_type'] = 'סוג שאלה לא ידוע';

// Grading.
$string['final_grade'] = 'ציון סופי';
$string['save_all_pending'] = 'שמור הכל בהמתנה';
$string['clear_all_grades'] = 'נקה את כל הציונים';
$string['grading_instructions'] = 'הכנס ציונים ישירות בטבלה למטה. שינויים נשמרים אוטומטית לאחר 2 שניות או כאשר אתה עובר לשדה אחר.';
$string['grade_status'] = 'סטטוס ציון';
$string['grade_saved_successfully'] = 'הציון נשמר בהצלחה';
$string['grade_save_error'] = 'שגיאה בשמירת הציון: {$a}';
$string['grade_pending_save'] = 'ציון ממתין לשמירה';
$string['saving_grade'] = 'שומר ציון…';
$string['grade_saved'] = 'הציון נשמר';
$string['error_saving_grade'] = 'שגיאה בשמירת הציון';
$string['error_saving_grade_user'] = 'שגיאה בשמירת הציון למשתמש {$a}';
$string['unsaved_changes'] = '{$a} שינוי(ים) לא נשמרו';
$string['no_pending_grades'] = 'אין ציונים ממתינים לשמירה.';
$string['saving_grades'] = 'שומר…';
$string['grades_saved_success'] = '{$a} ציונים נשמרו בהצלחה';
$string['error_saving_grades'] = 'שגיאה בשמירת הציונים';
$string['clearing_grades'] = 'מנקה…';
$string['all_grades_cleared'] = 'כל הציונים נמחקו בהצלחה';
$string['error_clearing_grades'] = 'שגיאה במחיקת הציונים';
$string['grade_is_overridden'] = 'ציון זה נדרס ידנית בספר הציונים ולא ניתן לעדכן אותו באופן אוטומטי';
$string['auto_grade_skipped_overridden'] = '{$a} ציונים דולגו כי הם נדרסו ידנית';

// Bulk grading.
$string['bulk_grades_saved'] = ' {$a} ציונים נשמרו בהצלחה';
$string['bulk_grades_partial'] = ' {$a->saved} ציונים נשמרו, {$a->failed} נכשלו';
$string['grades_cleared_success'] = 'כל הציונים נמחקו בהצלחה';
$string['grade_clear_error'] = 'שגיאה במחיקת הציונים: {$a}';
$string['confirm_clear_all_grades'] = 'נקה את כל הציונים';
$string['confirm_clear_all_grades_body'] = 'האם אתה בטוח שברצונך לנקות את כל הציונים? פעולה זו אינה ניתנת לביטול.';
$string['no_quiz_attempt'] = 'אין חידון';
$string['view_quiz_report'] = 'צפה בדוח החידון';

// Grade validation.
$string['grade_not_numeric'] = 'הציון חייב להיות מספר';
$string['grade_cannot_be_negative'] = 'הציון לא יכול להיות שלילי';
$string['grade_exceeds_maximum'] = 'הציון לא יכול לעלות על המקסימום ({$a})';

// Auto‑grading.
$string['auto_grade_by_quiz'] = 'ניקוד אוטומטי על‑פי ציון החידון';
$string['auto_grade_by_quiz_desc'] = 'הגדר אוטומטית את ציוני המשימה על‑פי ציון החידון לכל הסטודנטים';
$string['auto_grade_button_text'] = 'ניקוד אוטומטי על‑פי ציון החידון';
$string['auto_grade_success'] = '{$a} סטודנטים ניקודו אוטומטי על‑פי ציון החידון';
$string['auto_grade_no_grades'] = 'לא ניתן להחיל ציונים. בדוק שסטודנטים השלימו חידונים.';
$string['auto_grade_error'] = 'שגיאה בניקוד אוטומטי של סטודנטים';
$string['auto_grade_confirmation'] = 'זה יקבע אוטומטית ציונים על‑פי ציון החידון לכל הסטודנטים. ציונים קיימים יוחלפו. להמשיך?';
$string['auto_grading_progress'] = 'ניקוד אוטומטי…';
$string['error_parsing_grades'] = 'שגיאה בפענוח JSON של ציונים';

// Submission processing.
$string['processing_submission'] = 'מעבד את ההגשה שלך';
$string['processing_submission_message'] = 'אנא המתן בזמן שאנו מעבדים את הגשת המשימה שלך…';
$string['processing_please_wait'] = 'אנא המתן…';

// UI Messages.
$string['setting_update_error'] = 'שגיאה בעדכון ההגדרה: {$a}';
$string['setting_updated_success'] = 'ההגדרה "{$a}" עודכנה בהצלחה.';
$string['input_validation_error'] = 'שגיאת אימות קלט: {$a}';
$string['gateway_error'] = 'שגיאת Gateway';

// Recommendation rendering.
$string['criteria_evaluation'] = 'הערכת קריטריונים';
$string['criterion'] = 'קריטריון';
$string['met'] = 'הושג';
$string['suggestions'] = 'הצעות';
$string['evaluation'] = 'הערכה';
$string['improved_assignment'] = 'משימה משופרת';
$string['no_criteria_provided'] = 'לא נמסרו קריטריונים.';
$string['recommendation_error'] = 'שגיאה בהצגת ההמלצה.';

// Privacy API metadata descriptions.
$string['privacy:metadata:local_trustgrade_logs'] = 'שומר יומני ניתוח הנחיות TrustGrade והמלצות AI.';
$string['privacy:metadata:local_trustgrade_logs:userid'] = 'מספר המשתמש שביקש את ניתוח ההנחיות.';
$string['privacy:metadata:local_trustgrade_logs:cmid'] = 'מספר מודול הקורס שבו בוצע הניתוח.';
$string['privacy:metadata:local_trustgrade_logs:instructions'] = 'הנחיות המשימה שנבחנו.';
$string['privacy:metadata:local_trustgrade_logs:recommendation'] = 'ההמלצה שנוצרה על‑ידי AI עבור ההנחיות.';
$string['privacy:metadata:local_trustgrade_logs:timecreated'] = 'הזמן בו בוצע בקשת הניתוח.';

$string['privacy:metadata:local_trustgrade_questions'] = 'שומר שאלות שנוצרו על‑ידי AI עבור משימות שנוצרו על‑ידי מרצים.';
$string['privacy:metadata:local_trustgrade_questions:cmid'] = 'מספר מודול הקורס עבורו יוצרו שאלות.';
$string['privacy:metadata:local_trustgrade_questions:userid'] = 'מספר המרצה שייצר את השאלות.';
$string['privacy:metadata:local_trustgrade_questions:question_data'] = 'נתוני השאלה כולל טקסט, סוג, אפשרויות ותשובות נכונות.';
$string['privacy:metadata:local_trustgrade_questions:timecreated'] = 'הזמן בו נוצרו השאלות.';
$string['privacy:metadata:local_trustgrade_questions:timemodified'] = 'הזמן בו השאלות שונו לאחרונה.';

$string['privacy:metadata:local_trustgd_sub_questions'] = 'שומר שאלות שנוצרו על‑ידי AI המבוססות על הגשות סטודנטים.';
$string['privacy:metadata:local_trustgd_sub_questions:submission_id'] = 'מספר ההגשה שעליה מבוססות השאלות.';
$string['privacy:metadata:local_trustgd_sub_questions:cmid'] = 'מספר מודול הקורס הקשור להגשה.';
$string['privacy:metadata:local_trustgd_sub_questions:userid'] = 'מספר הסטודנט שההגשה שלו נבחנו.';
$string['privacy:metadata:local_trustgd_sub_questions:question_data'] = 'נתוני השאלות המותאמות אישית מההגשה.';
$string['privacy:metadata:local_trustgd_sub_questions:timecreated'] = 'הזמן בו יוצרו השאלות.';
$string['privacy:metadata:local_trustgd_sub_questions:timemodified'] = 'הזמן בו השאלות שונו לאחרונה.';

$string['privacy:metadata:local_trustgrade_debug'] = 'שומר מידע דיבוג ותגובות API שמורות לטובת פיתוח.';
$string['privacy:metadata:local_trustgrade_debug:userid'] = 'מספר המשתמש שהפעיל את בקשת API.';
$string['privacy:metadata:local_trustgrade_debug:cmid'] = 'מספר מודול הקורס הקשור לבקשה.';
$string['privacy:metadata:local_trustgrade_debug:request_type'] = 'סוג בקשת API שבוצעה.';
$string['privacy:metadata:local_trustgrade_debug:request_data'] = 'הנתונים שנשלחו בבקשת API.';
$string['privacy:metadata:local_trustgrade_debug:raw_response'] = 'תגובה גולמית שהתקבלה מ‑API.';
$string['privacy:metadata:local_trustgrade_debug:parsed_response'] = 'נתוני התגובה מ‑API לאחר פיענוח.';
$string['privacy:metadata:local_trustgrade_debug:timecreated'] = 'הזמן בו בוצעה הבקשה.';

$string['privacy:metadata:local_trustgd_quiz_sessions'] = 'שומר מצב מפגש חידון כולל תשובות סטודנט ומידע על ניטור יושרה.';
$string['privacy:metadata:local_trustgd_quiz_sessions:cmid'] = 'מספר מודול הקורס עבור החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:submissionid'] = 'מספר ההגשה הקשור למפגש החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:userid'] = 'מספר הסטודנט שממלא את החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:questions_data'] = 'השאלות שהוצגו בחידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:settings_data'] = 'הגדרות החידון שהוחלו במפגש זה.';
$string['privacy:metadata:local_trustgd_quiz_sessions:current_question'] = 'מספר השאלה הנוכחית בחידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:answers_data'] = 'תשובות הסטודנט לשאלות החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:time_remaining'] = 'הזמן שנותר לשאלה הנוכחית.';
$string['privacy:metadata:local_trustgd_quiz_sessions:window_blur_count'] = 'מספר הפעמים שהסטודנט החליף חלונות/כרטיסיות במהלך החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:attempt_started'] = 'האם ניסיון החידון התחיל.';
$string['privacy:metadata:local_trustgd_quiz_sessions:attempt_completed'] = 'האם ניסיון החידון הוש������ם.';
$string['privacy:metadata:local_trustgd_quiz_sessions:integrity_violations'] = 'הפרות יושרה שנרשמו במהלך החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:final_score'] = 'הציון הסופי שהושג בחידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timecreated'] = 'הזמן בו נוצר מפגש החידון.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timemodified'] = 'הזמן בו שונה מפגש החידון לאחרונה.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timecompleted'] = 'הזמן בו הושלם החידון.';

$string['privacy:metadata:ai_gateway'] = 'נתונים אישיים נשלחים לשירות AI Gateway חיצוני לעיבוד הנחיות משימה ויצירת שאלות אישיות.';
$string['privacy:metadata:ai_gateway:userid'] = 'מספר המשתמש עשוי להיכלל במטא‑נתונים למעקב.';
$string['privacy:metadata:ai_gateway:instructions'] = 'הנחיות המשימה נשלחות ל‑AI Gateway לניתוח.';
$string['privacy:metadata:ai_gateway:submission_text'] = 'טקסט ההגשה של הסטודנט נשלח ליצירת שאלות מותאמות אישית.';
$string['privacy:metadata:ai_gateway:files'] = 'קבצים מצורפים עשויים להישלח לניתוח וליצירת שאלות.';
$string['privacy:metadata:ai_gateway:metadata'] = 'מידע נוסף (קורס, שם קורס, מודול ID) נשלח עם הבקשות.';

// Privacy export paths.
$string['privacy:path:logs'] = 'יומני ניתוח הנחיות TrustGrade';
$string['privacy:path:questions'] = 'שאלות שנוצרו על‑ידי TrustGrade';
$string['privacy:path:submission_questions'] = 'שאלות מבוססות הגשה של TrustGrade';
$string['privacy:path:debug'] = 'נתוני דיבוג של TrustGrade';

// Cache management page strings
// Cache management page.
$string['cache_management_title'] = 'ניהול מטמון של TrustGrade';
$string['cache_management_heading'] = 'ניהול מטמון של TrustGrade';
$string['debug_mode_disabled_info'] = 'מצב דיבוג כרגע מושבת. הפעל את מצב הדיבוג בהגדרות התוסף כדי להשתמש בתכונות המטמון.';
$string['debug_mode_enabled_info'] = 'מצב דיבוג מופעל. תגובות Gateway נשמרות במטמון לשיפור ביצועים.';
$string['cache_statistics'] = 'סטטיסטיקות מטמון';
$string['total_cached_responses'] = 'סה”כ תגובות שמורות';
$string['last_24_hours'] = '24 השעות האחרונות';
$string['cacheable_responses'] = 'תגובות שניתנות לשמירה';
$string['cache_efficiency'] = 'יעילות מטמון';
$string['cache_by_type'] = 'מטמון לפי סוג בקשה';
$string['request_type'] = 'סוג בקשה';
$string['cached_responses'] = 'תגובות שמורות';
$string['actions'] = 'פעולות';
$string['clear'] = 'נקה';
$string['recent_cache_activity'] = 'פעילות מטמון אחרונה';
$string['time'] = 'זמן';
$string['status'] = 'סטטוס';
$string['cached'] = 'נשמר';
$string['not_cached'] = 'לא נשמר';
$string['error_loading_cache_stats'] = 'שגיאה בטעינת סטטיסטיקות המטמון: {$a}';
$string['cache_management_actions'] = 'פעולות ניהול מטמון';
$string['clear_all_cache_title'] = 'נקה את כל המטמון';
$string['clear_all_cache_desc'] = 'הסר את כל תגובות Gateway שמורות. זה יצמצם קריאות חדשות ל‑Gateway.';
$string['clear_all_cache_button'] = 'נקה את כל המטמון';
$string['cleanup_old_records_title'] = 'נקה רשומות ישנות';
$string['cleanup_old_records_desc'] = 'הסר רשומות מטמון שגילן עולה על 7 ימים כדי לפנות מקום בבסיס נתונים.';
$string['cleanup_old_records_button'] = 'נקה רשומות ישנות';
$string['related_pages'] = 'דפים קשורים';
$string['plugin_settings'] = 'הגדרות תוסף';
$string['all_cache_cleared'] = 'כל המטמון נוקה בהצלחה';
$string['instruction_cache_cleared'] = 'מטמון ניתוח ההנחיות נוקה';
$string['question_cache_cleared'] = 'מטמון יצירת השאלות נוקה';
$string['submission_cache_cleared'] = 'מטמון שאלות מבוססות הגשה נוקה';
$string['old_cache_cleaned_up'] = 'רשומות מטמון ישנות נוקו';

// Gateway test page strings
// Gateway test page.
$string['gateway_test_title'] = 'בדיקת AI Gateway';
$string['gateway_test_heading'] = 'בדיקת חיבור לנקודת API של AI Gateway';
$string['gateway_connection_success'] = 'החיבור הצליח';
$string['gateway_connection_failed'] = 'החיבור נכשל: {$a}';
$string['gateway_configuration'] = 'הגדרות Gateway';
$string['gateway_endpoint_label'] = 'נקודת קצה';
$string['gateway_token_label'] = 'אסימון';
$string['gateway_token_configured'] = 'הוגדר';
$string['gateway_token_not_configured'] = 'לא הוגדר';
$string['gateway_openrouter_note'] = 'הערה: מפתח API של TrustGrade והדגם מוגדרים בשרת Gateway, לא בתוסף.';
$string['gateway_troubleshooting'] = 'פתרון תקלות';
$string['gateway_verify_url'] = 'אמת שה‑URL של נקודת הקצה נכון ונגישה';
$string['gateway_check_token'] = 'בדוק שהאסימון לאימות ה‑Gateway תקף';
$string['gateway_ensure_running'] = 'וודא שהשרת Gateway פועל ומגיב';
$string['gateway_verify_apikey'] = 'אמת שה‑Gateway מחזיק מפתח API חוקי של TrustGrade';
$string['gateway_config_error'] = 'שגיאת הגדרה: {$a}';
$string['gateway_config_required'] = 'הגדרה נדרשת';
$string['gateway_config_endpoint'] = 'הגדר את כתובת ה‑Endpoint של Gateway בהגדרות התוסף';
$string['gateway_config_token'] = 'הגדר את אסימון האימות של Gateway';
$string['gateway_config_openrouter'] = 'ודא שלשרת Gateway מוגדר מפתח API חוקי של TrustGrade';
$string['configure_gateway_settings'] = 'הגדר את הגדרות Gateway';

// Question editor validation strings
// Question editor validation.
$string['question_saved_successfully_msg'] = 'השאלה נשמרה בהצלחה';
$string['failed_save_question'] = 'נכשל שמירת השאלה: {$a}';
$string['question_not_found_error'] = 'השאלה לא נמצאה';
$string['question_deleted_successfully_msg'] = 'השאלה נמחקה בהצלחה';
$string['failed_delete_question'] = 'נכשל מחיקת השאלה: {$a}';
$string['invalid_json_error'] = 'JSON לא חוקי: {$a}';
$string['question_data_must_be_array'] = 'נתוני השאלה חייבים להיות מערך אסוציאטיבי';
$string['question_type_required'] = 'סוג השאלה נדרש';
$string['invalid_question_type'] = 'סוג שאלה לא חוקי';
$string['question_text_field_required'] = 'טקסט השאלה (שדה "text") נדרש';
$string['options_must_be_array'] = 'אפשרויות חייבות להיות במערך';
$string['at_least_2_options_required'] = 'נדרשות לפחות שתי אפשרויות';
$string['option_must_be_object'] = 'כל אפשרות חייבת להיות אובייקט';
$string['option_non_numeric_id'] = 'אפשרות באינדקס {$a} מכילה מזהה לא מספרי';
$string['option_text_required'] = 'אפשרות באינדקס {$a} חייבת לכלול \'text\' לא ריקה';
$string['option_is_correct_required'] = 'אפשרות בא��נדקס {$a} חייבת לכלול \'is_correct\'';
$string['option_is_correct_invalid'] = 'אפשרות באינדקס {$a} מכילה \'is_correct\' לא חוקי (חייב להיות בוליאני)';
$string['option_explanation_invalid'] = 'אפשרות באינדקס {$a} מכילה \'explanation\' לא חוקי (חייב להיות מחרוזת)';
$string['at_least_one_correct_option'] = 'שאלות בחירה מרובה חייבות לכלול לפחות אפשרות נכונה אחת';
$string['metadata_must_be_object'] = 'מטא‑נתונים חייבים להיות אובייקט';
$string['points_must_be_1_to_100'] = 'נקודות חייבות להיות בין 1 ל‑100';
$string['blooms_level_must_be_string'] = 'ה‑metadata \'blooms_level\' חייבת להיות מחרוזת';
$string['mandatory_question'] = 'שאלת חובה';

// Quiz completion strings
// Quiz completion messages.
$string['quiz_already_completed'] = 'כבר השלמת הערכה זו. מותר ניסיון אחד בלבד למשימה זו.';
$string['return_to_assignment'] = 'חזור למשימה';

$string['defaultcoursestudent'] = 'סטודנט';
$string['mins_secs'] = '{$a->minutes}ד {$a->seconds}ש';
$string['secs_only'] = '{$a}ש';

$string['cachedef_quiz_redirect'] = 'שומר כתובות URL להפניה לחידון לניהול מפגש זמני';
$string['nopermission'] = 'אין לך הרשאה לגשת לדף זה';

// Async task indicator strings
$string['quiz_preparing'] = 'החידון שלך בהכנה';
$string['quiz_preparing_message'] = 'אנחנו יוצרים שאלות מותאמות אישית עבור {$a}. תקבל הודעה כשהחידון יהיה מוכן.';
$string['quiz_ready_click_to_start'] = 'החידון מוכן - לחץ להתחלה';
$string['quiz_ready_assignment'] = 'החידון שלך עבור {$a} מוכן!';
