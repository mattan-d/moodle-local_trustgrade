<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'TrustGrade';
$string['trustgrade_tab'] = 'TrustGrade';
$string['check_instructions'] = 'בדוק הוראות עם בינה מלאכותית';
$string['ai_recommendation'] = 'המלצת בינה מלאכותית';
$string['processing'] = 'מעבד...';
$string['no_instructions'] = 'לא נמצאו הוראות לניתוח';
$string['trustgrade_description'] = 'השתמש בשער הבינה המלאכותית כדי לנתח ולקבל המלצות לשיפור הוראות המטלה שלך.';
$string['generate_questions'] = 'צור בנק שאלות עם בינה מלאכותית';
$string['generated_questions'] = 'שאלות שנוצרו';
$string['generating_questions'] = 'יוצר שאלות דרך השער...';
$string['loading_question_bank'] = 'טוען בנק שאלות...';
$string['questions_generated_success'] = 'השאלות נוצרו ונשמרו בהצלחה!';
$string['error_saving_questions'] = 'שגיאה בשמירת השאלות שנוצרו';
$string['debug_mode'] = 'מצב ניפוי שגיאות וזיכרון מטמון';
$string['debug_mode_desc'] =
        'הפעל מצב ניפוי שגיאות כדי לשמור תגובות השער בזיכרון מטמון ולמנוע קריאות API חוזרות. כאשר מופעל, בקשות זהות יחזירו תגובות מהמטמון במקום לקרוא לשער. זה משפר ביצועים ומפחית שימוש ב-API בזמן פיתוח ובדיקות.';
$string['cleanup_debug_cache'] = 'נקה מטמון ניפוי שגיאות של TrustGrade';
$string['cleanup_quiz_sessions'] = 'נקה מפגשי חידון של TrustGrade';
$string['ai_quiz_title'] = 'חידון שנוצר על ידי בינה מלאכותית';
$string['no_questions_available'] = 'אין שאלות זמינות למטלה זו.';
$string['next'] = 'הבא';
$string['finish_quiz'] = 'סיים חידון';
$string['quiz_ready_message'] = 'החידון שנוצר על ידי בינה מלאכותית מוכן! חידון זה יעזור לך להרהר על ההגשה שלך ולחזק את הלמידה שלך.';
$string['edit'] = 'ערוך';
$string['delete'] = 'מחק';
$string['add_new_question'] = 'הוסף שאלה חדשה';
$string['seconds'] = 'שניות';

// Quiz Settings
$string['quiz_settings_title'] = 'הגדרות חידון';
$string['questions_to_generate'] = 'מספר השאלות ליצירה';
$string['question_distribution'] = 'חלוקת מקורות השאלות';
$string['instructor_questions'] = 'שאלות מבנק המדריך';
$string['submission_questions'] = 'שאלות על בסיס הגשות';
$string['randomize_answers'] = 'ערבב סדר התשובות';
$string['randomize_answers_desc'] = 'ערבב באופן אקראי את סדר אפשרויות התשובה לשאלות רב-ברירה.';
$string['time_per_question'] = 'זמן לכל שאלה';
$string['show_countdown'] = 'הצג טיימר ספירה לאחור';
$string['show_countdown_desc'] = 'הצג טיימר ספירה לאחור לכל שאלה. כאשר הזמן פג, החידון עובר אוטומטית לשאלה הבאה.';

// Disclosure Settings
$string['disclosure_settings'] = 'הגדרות חשיפת מידע לסטודנטים';
$string['disclosure_settings_desc'] = 'הגדר איך מודיעים לסטודנטים על תכונות הבינה המלאכותית במטלות.';
$string['show_disclosure'] = 'הצג הודעת גילוי בינה מלאכותית';
$string['show_disclosure_desc'] =
        'הצג הודעת גילוי לסטודנטים לפני הגשת המטלות, להודיע להם על תכונת החידון המופעלת על ידי בינה מלאכותית.';
$string['custom_disclosure_message'] = 'הודעת גילוי מותאמת אישית';
$string['custom_disclosure_message_desc'] =
        'הודעה מותאמת אישית אופציונלית להצגה במקום הודעת הברירת מחדל. השאר ריק לשימוש בהודעת ברירת המחדל.';

// AI Disclosure Messages
$string['ai_disclosure_title'] = 'חוויית למידה משופרת בבינה מלאכותית';
$string['ai_disclosure_message'] =
        'מטלה זו כוללת תכונת למידה מופעלת על ידי בינה מלאכותית. לאחר הגשת העבודה שלך, מערכת בינה מלאכותית תנתח את ההגשה שלך כדי ליצור שאלות חידון מותאמות אישית שעוזרות לחזק את הלמידה שלך. החידון הזה יהיה זמין מיד לאחר ההגשה ומיועד לעזור לך להרהר על העבודה שלך ולהעמיק את ההבנה שלך בנושא.';
$string['ai_disclosure_details_toggle'] = 'למד עוד על איך זה עובד';
$string['ai_disclosure_detail_analysis'] = 'ההגשה שלך תנותח על ידי בינה מלאכותית כדי להבין את הגישה וההיגיון שלך.';
$string['ai_disclosure_detail_questions'] = 'הבינה המלאכותית תיצור {$a} שאלות מותאמות אישית על בסיס ההגשה הספציפית שלך.';
$string['ai_disclosure_detail_quiz'] = 'תעבור חידון של {$a} שאלות המשלב שאלות שנוצרו על ידי המדריך ושאלות מותאמות אישית.';
$string['ai_disclosure_detail_timer'] = 'לכל שאלת חידון יש מגבלת זמן של {$a} שניות כדי לעודד חשיבה מהירה.';
$string['ai_disclosure_detail_privacy'] = 'נתוני ההגשה שלך מעובדים בצורה מאובטחת ומשמשים רק למטרות חינוכיות.';

// Gateway Settings
$string['gateway_settings'] = 'הגדרות שער בינה מלאכותית';
$string['gateway_settings_desc'] =
        'הגדר שער בינה מלאכותית חיצוני לעיבוד בינה מלאכותית. השער מטפל בכל הגדרות ה-API של הבינה המלאכותית כולל מפתח OpenRouter API ובחירת מודל.';
$string['gateway_endpoint'] = 'כתובת URL של נקודת קצה השער';
$string['gateway_endpoint_desc'] = 'כתובת ה-URL של נקודת קצה API של השער החיצוני שלך (למשל, https://your-gateway.com/api)';
$string['gateway_token'] = 'טוקן אימות השער';
$string['gateway_token_desc'] = 'טוקן אימות לתקשורת מאובטחת עם השער (ברירת מחדל: Demo123 לבדיקות)';
$string['gateway_test'] = 'בדיקת חיבור השער';
$string['test_gateway_connection'] = 'בדוק חיבור השער';

// Cache Management - Settings Integration
$string['cache_management'] = 'ניהול זיכרון מטמון';
$string['cache_management_widget_desc'] = 'הצג סטטיסטיקות זיכרון מטמון ונהל תגובות מוטמנות ישירות מעמוד ההגדרות.';
$string['cache_disabled_message'] = 'זיכרון המטמון מנוטרל. הפעל מצב ניפוי שגיאות למעלה כדי להפעיל זיכרון מטמון תגובות.';
$string['cache_stats_error'] = 'שגיאה בטעינת סטטיסטיקות זיכרון מטמון: {$a}';
$string['full_management'] = 'ניהול מלא';
$string['clear_all'] = 'נקה הכל';
$string['clear_instructions'] = 'נקה הוראות';
$string['clear_questions'] = 'נקה שאלות';
$string['clear_submissions'] = 'נקה הגשות';
$string['cleanup_old'] = 'נקה ישן';
$string['confirm_clear_cache'] = 'האם אתה בטוח שברצונך לנקות את כל התגובות המוטמנות? פעולה זו לא ניתנת לביטול.';

// Cache Action Results
$string['cache_cleared_success'] = 'כל התגובות המוטמנות נוקו בהצלחה.';
$string['instructions_cache_cleared'] = 'זיכרון מטמון ניתוח הוראות נוקה.';
$string['questions_cache_cleared'] = 'זיכרון מטמון יצירת שאלות נוקה.';
$string['submissions_cache_cleared'] = 'זיכרון מטמון שאלות הגשות נוקה.';
$string['old_cache_cleaned'] = 'רשומות זיכרון מטמון ישנות נוקו בהצלחה.';
$string['cache_clear_error'] = 'שגיאה בניקוי זיכרון מטמון: {$a}';
$string['invalid_action'] = 'נדרשה פעולת זיכרון מטמון לא חוקית.';

// Quiz Report - NEW STRINGS
$string['quiz_report'] = 'דוח חידון';
$string['quiz_report_assignment_desc'] = 'דוח חידון בינה מלאכותית למטלה זו';
$string['quiz_report_course_desc'] = 'דוח חידון בינה מלאכותית לכל המטלות בקורס זה';
$string['quiz_report_all_desc'] = 'דוח חידון בינה מלאכותית לכל המטלות בכל הקורסים';
$string['back_to_assignment'] = 'חזור למטלה';
$string['back_to_course'] = 'חזור לקורס';
$string['quiz_score'] = 'ציון חידון';
$string['details'] = 'פרטים';
$string['view_details'] = 'הצג פרטים';
$string['save_all_pending'] = 'שמור את כל הממתינים';
$string['clear_all_grades'] = 'נקה את כל הציונים';
$string['auto_grade_by_quiz'] = 'דרג אוטומטית לפי ציון חידון';
$string['auto_grade_by_quiz_desc'] = 'הגדר אוטומטית ציוני מטלה על בסיס ציוני חידון לכל הסטודנטים';
$string['grading_instructions'] = 'הזן ציונים ישירות בטבלה למטה. השינויים נשמרים אוטומטית לאחר 2 שניות או כשאתה עובר לשדה אחר.';
$string['grade_status'] = 'סטטוס ציון';

// Quiz Details
$string['quiz_details'] = 'פרטי חידון';
$string['question'] = 'שאלה';
$string['student_answer'] = 'תשובת הסטודנט';
$string['correct_answer'] = 'תשובה נכונה';
$string['result'] = 'תוצאה';
$string['correct'] = 'נכון';
$string['incorrect'] = 'לא נכון';
$string['no_completed_quizzes'] = 'אף סטודנט לא השלים עדיין את חידון הבינה המלאכותית למטלה זו.';
$string['integrity_summary'] = 'סיכום יושרה';
$string['window_blur_events'] = 'אירועי טשטוש חלון';

// Additional Quiz Report Strings
$string['session_info'] = 'מידע מפגש';
$string['completed_on'] = 'הושלם ב';
$string['time_taken'] = 'זמן שנלקח';
$string['points'] = 'נקודות';
$string['no_answer'] = 'אין תשובה';
$string['true'] = 'נכון';
$string['false'] = 'לא נכון';
$string['not_available'] = 'לא זמין';
$string['manual_grading_required'] = 'נדרש דירוג ידני';
$string['integrity_violations_count'] = 'סה"כ הפרות יושרה: {$a}';

// Enhanced Answer Display Strings
$string['raw_answer_value'] = 'תשובה גולמית';
$string['invalid_option_selected'] = 'נבחרה אפשרות לא חוקית';
$string['invalid_boolean_value'] = 'ערך בוליאני לא חוקי';
$string['unknown_question_type'] = 'סוג שאלה לא ידוע';

// Direct Grading Strings
$string['grade_saved_successfully'] = 'הציון נשמר בהצלחה';
$string['grade_save_error'] = 'שגיאה בשמירת ציון: {$a}';
$string['bulk_grades_saved'] = 'נשמרו בהצלחה {$a} ציונים';
$string['bulk_grades_partial'] = 'נשמרו {$a->saved} ציונים, {$a->failed} נכשלו';
$string['grades_cleared_success'] = 'כל הציונים נוקו בהצלחה';
$string['grade_clear_error'] = 'שגיאה בניקוי ציונים: {$a}';
$string['confirm_clear_all_grades'] = 'נקה את כל הציונים';
$string['confirm_clear_all_grades_body'] = 'האם אתה בטוח שברצונך לנקות את כל הציונים? פעולה זו לא ניתנת לביטול.';

// Grade Validation Strings (used in grading_manager.php)
$string['grade_not_numeric'] = 'הציון חייב להיות מספר';
$string['grade_cannot_be_negative'] = 'הציון לא יכול להיות שלילי';
$string['grade_exceeds_maximum'] = 'הציון לא יכול לחרוג מהמקסימום ({$a})';

// Auto-grading Strings
$string['auto_grade_success'] = 'דורגו אוטומטית בהצלחה {$a} סטודנטים על בסיס ציוני חידון';
$string['auto_grade_no_grades'] = 'לא ניתן היה להחיל ציונים. בדוק שסטודנטים השלימו חידונים.';
$string['auto_grade_error'] = 'שגיאה במהלך דירוג אוטומטי: {$a}';
$string['auto_grade_confirmation'] = 'זה יגדיר אוטומטית ציונים על בסיס ציוני חידון לכל הסטודנטים. ציונים קיימים יוחלפו. להמשיך?';

// Final Grade
$string['final_grade'] = 'ציון סופי';

// Missing strings used in JavaScript files
$string['setting_update_error'] = 'שגיאה בעדכון הגדרה: {$a}';
$string['no_instructions_error'] = 'לא נמצאו הוראות לניתוח';
$string['input_validation_error'] = 'שגיאת אימות קלט: {$a}';
$string['cache_hit'] = 'פגיעה במטמון - משתמש בתגובה מוטמנת';
$string['gateway_error'] = 'שגיאת שער';
$string['no_instructions_questions_error'] = 'לא נמצאו הוראות ליצירת שאלות';
$string['question_bank_title'] = 'בנק שאלות';
$string['question_text_required'] = 'נדרש טקסט שאלה';
$string['all_options_required'] = 'כל אפשרויות התשובה נדרשות';
$string['question_saved_success'] = 'השאלה נשמרה בהצלחה';
$string['confirm_delete_question_title'] = 'מחק שאלה';
$string['confirm_delete_question_message'] = 'האם אתה בטוח שברצונך למחוק שאלה זו? פעולה זו לא ניתנת לביטול.';
$string['question_deleted_success'] = 'השאלה נמחקה בהצלחה';
$string['ai_quiz_report'] = 'דוח חידון בינה מלאכותית';

// JavaScript UI Strings
$string['instructor_question'] = 'שאלת מדריך';
$string['based_on_submission'] = 'על בסיס ההגשה שלך';
$string['progress_auto_saved'] = 'ההתקדמות שלך נשמרת אוטומטית. רענון הדף יחדש מהשאלה הזו.';
$string['next_question'] = 'השאלה הבאה →';
$string['submit_final_answers'] = 'הגש תשובות סופיות';
$string['provide_answer_warning'] = 'אנא ספק תשובה לפני המשך. זכור, לא תוכל לחזור לשאלה זו מאוחר יותר.';
$string['quiz_started_notice'] = 'החידון התחיל. זכור: לא תוכל לחזור לשאלות קודמות או להתחיל מחדש את ההערכה הזו.';
$string['failed_start_session'] = 'נכשל בפתיחת מפגש חידון';
$string['dev_tools_blocked'] = 'גישה לכלי פיתוח אינה מותרת במהלך החידון.';
$string['quiz_progress_saved'] = 'התקדמות החידון שלך נשמרת אוטומטית. החידון יתחדש מהמקום שבו עצרת כשתחזור.';
$string['quiz_completed_header'] = 'החידון הושלם';
$string['quiz_completed_message'] = 'ההערכה הפורמלית שלך הוגשה בהצלחה ולא ניתן לחזור עליה.';
$string['your_answer'] = 'התשובה שלך: {$a}';
$string['correct_answer_was'] = 'התשובה הנכונה: {$a}';
$string['explanation'] = 'הסבר:';
$string['final_grade_notice'] = 'זה הציון הסופי שלך להערכה זו.';
$string['integrity_report_header'] = 'דוח יושרה';
$string['integrity_recorded'] = 'מידע זה נרשם לבדיקה.';
$string['integrity_violation_header'] = 'הפרת יושרת הערכה';
$string['quiz_flagged'] = 'ניסיון החידון שלך סומן כפעילות חשודה.';
$string['incident_logged'] = 'התקרית הזו נרשמה ותיבדק על ידי המדריך שלך.';
$string['progress_saved_cannot_continue'] = 'ההתקדמות הנוכחית שלך נשמרה, אבל לא תוכל להמשיך בהערכה.';
$string['failed_save_contact_instructor'] = 'נכשל בשמירת תוצאות סופיות. אנא פנה למדריך שלך.';
$string['understand_start_quiz'] = 'הבנתי - התחל חידון';
$string['important_formal_assessment'] = 'חשוב: הערכה פורמלית';
$string['read_carefully'] = 'אנא קרא בעיון לפני התחלה:';
$string['one_attempt_only'] = 'ניסיון אחד בלבד: יש לך רק ניסיון אחד להשלים חידון זה.';
$string['no_going_back'] = 'אין חזרה אחורה: ברגע שעוברים לשאלה הבאה, לא ניתן לחזור לשאלות קודמות.';
$string['no_restarts'] = 'אין התחלה מחדש: רענון הדף לא יתחיל מחדש את החידון - הוא יתחדש מהמקום שבו עצרת.';
$string['time_limits'] = 'מגבלות זמן: לכל שאלה יש מגבלת זמן קפדנית. החידון יעבור אוטומטית כשהזמן פג.';
$string['no_cheating'] = 'ללא רמאות: זוהי הערכה פורמלית. כל ניסיון לרמות או לחבל בחידון יתגלה.';
$string['stay_focused'] = 'הישאר ממוקד: החלפת חלונות או טאבים יתר על המידה עלולה להיות מסומנת כהתנהגות חשודה.';
$string['cannot_restart_notice'] = 'ברגע שלוחצים על "התחל חידון", לא ניתן להתחיל מחדש או לחזור על הערכה זו.';
$string['enter_answer_placeholder'] = 'הזן את התשובה שלך כאן...';

// Missing JavaScript UI Strings
$string['window_switching_warning'] =
        'אזהרה: החלפת חלונות/טאבים {$a->count} פעמים. מקסימום מותר: {$a->max}. החלפה מוגזמת עלולה לגרום לסיום החידון.';
$string['quiz_progress_complete'] = '{$a}% הושלם';
$string['question_x_of_y'] = 'שאלה {$a->current} מתוך {$a->total}';
$string['time_remaining'] = 'זמן נותר: {$a}';
$string['failed_save_results'] = 'נכשל בשמירת תוצאות חידון: {$a}';
$string['final_score'] = 'ציון סופי: {$a->score}/{$a->total} ({$a->percentage}%)';
$string['window_focus_lost'] = 'פוקוס החלון אבד {$a} פעמים במהלך החידון.';
$string['exceeded_window_switches'] = 'חרגת ממספר החלפות החלונות המקסימלי המותר ({$a}). החידון הופסק.';
$string['setting_updated_success'] = 'הגדרה "{$a}" עודכנה בהצלחה.';
