<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'מדד איכות אקדמית';
$string['plugin_enabled'] = 'הפעלת תוסף מדד איכות אקדמית';
$string['plugin_enabled_desc'] = 'הפעלה או השבתה של תוסף מדד איכות אקדמית באופן גלובלי. כאשר מושבת, כל הפונקציונליות של מדד איכות אקדמית תוסתר מטפסי המטלות והעמודים.';
$string['trustgrade_enabled'] = 'הפעלת מדד איכות אקדמית למטלה זו';
$string['trustgrade_enabled_desc'] = 'הפעלת תכונות הבינה המלאכותית של מדד איכות אקדמית למטלה ספציפית זו. כאשר מושבת, הסטודנטים לא יראו חידונים של בינה מלאכותית או פונקציונליות קשורה.';
$string['trustgrade_tab'] = 'מדד איכות אקדמית';
$string['check_instructions'] = 'בדיקת הוראות עם בינה מלאכותית';
$string['ai_recommendation'] = 'המלצת בינה מלאכותית';
$string['processing'] = 'מעבד...';
$string['no_instructions'] = 'לא נמצאו הוראות לניתוח';
$string['trustgrade_description'] = 'השתמש בשער הבינה המלאכותית כדי לנתח ולקבל המלצות לשיפור הוראות המטלה שלך.';
$string['generate_questions'] = 'יצירת בנק שאלות עם בינה מלאכותית';
$string['generated_questions'] = 'שאלות שנוצרו';
$string['generating_questions'] = 'יוצר שאלות דרך השער...';
$string['loading_question_bank'] = 'טוען בנק שאלות...';
$string['questions_generated_success'] = 'השאלות נוצרו ונשמרו בהצלחה!';
$string['error_saving_questions'] = 'שגיאה בשמירת השאלות שנוצרו';
$string['debug_mode'] = 'מצב דיבוג וזיכרון מטמון';
$string['debug_mode_desc'] = 'הפעלת מצב דיבוג לשמירת תגובות השער בזיכרון מטמון ולהימנעות מקריאות API חוזרות. כאשר מופעל, בקשות זהות יחזירו תגובות שמורות במטמון במקום לקרוא לשער. זה משפר ביצועים ומפחית שימוש ב-API במהלך פיתוח ובדיקות.';
$string['cleanup_debug_cache'] = 'ניקוי מטמון דיבוג מדד איכות אקדמית';
$string['cleanup_quiz_sessions'] = 'ניקוי מושבי חידון מדד איכות אקדמית';
$string['ai_quiz_title'] = 'חידון שנוצר בבינה מלאכותית';
$string['no_questions_available'] = 'אין שאלות זמינות למטלה זו.';
$string['next'] = 'הבא';
$string['finish_quiz'] = 'סיום חידון';
$string['quiz_ready_message'] = 'החידון שנוצר בבינה מלאכותית מוכן! החידון הזה יעזור לך להרהר על ההגשה שלך ולחזק את הלמידה שלך.';
$string['edit'] = 'עריכה';
$string['delete'] = 'מחיקה';
$string['add_new_question'] = 'הוספת שאלה חדשה';
$string['seconds'] = 'שניות';

// Quiz Settings
$string['quiz_settings_title'] = 'הגדרות חידון';
$string['questions_to_generate'] = 'מספר שאלות ליצירה';
$string['questions_to_generate_help'] = 'מספר כולל של שאלות לכלול בחידון';
$string['question_distribution'] = 'חלוקת מקורות שאלות';
$string['instructor_questions'] = 'שאלות מבנק המדריך';
$string['instructor_questions_help'] = 'מספר שאלות לבחירה מבנק השאלות של המדריך';
$string['submission_questions'] = 'שאלות מבוססות על הגשות';
$string['submission_questions_help'] = 'מספר שאלות ליצירה על בסיס הגשות הסטודנטים';
$string['randomize_answers'] = 'ערבוב סדר תשובות';
$string['randomize_answers_desc'] = 'ערבוב אקראי של סדר אפשרויות התשובה לשאלות רב-ברירה.';
$string['time_per_question'] = 'זמן לכל שאלה';
$string['time_per_question_help'] = 'זמן מקסימלי המותר לכל שאלה בשניות';
$string['show_countdown'] = 'הצגת טיימר ספירה לאחור';
$string['show_countdown_desc'] = 'הצגת טיימר ספירה לאחור לכל שאלה. כאשר הזמן פוקע, החידון עובר אוטומטית לשאלה הבאה.';

// Disclosure Settings
$string['disclosure_settings'] = 'הגדרות גילוי לסטודנטים';
$string['disclosure_settings_desc'] = 'הגדרת האופן שבו הסטודנטים מקבלים מידע על תכונות הבינה המלאכותית במטלות.';
$string['show_disclosure'] = 'הצגת הודעת גילוי בינה מלאכותית';
$string['show_disclosure_desc'] = 'הצגת הודעת גילוי לסטודנטים לפני הגשת המטלות, להודיע להם על תכונת החידון המופעלת בבינה מלאכותית.';
$string['custom_disclosure_message'] = 'הודעת גילוי מותאמת אישית';
$string['custom_disclosure_message_desc'] = 'הודעה מותאמת אישית אופציונלית להצגה במקום הודעת ברירת המחדל. השאר ריק לשימוש בהודעת ברירת המחדל.';

// AI Disclosure Messages
$string['ai_disclosure_title'] = 'חוויית למידה משופרת בבינה מלאכותית';
$string['ai_disclosure_message'] = 'מטלה זו כוללת תכונת למידה מופעלת בבינה מלאכותית. לאחר הגשת העבודה שלך, מערכת בינה מלאכותית תנתח את ההגשה שלך כדי ליצור שאלות חידון מותאמות אישית שעוזרות לחזק את הלמידה שלך. החידון הזה יהיה זמין מיד לאחר ההגשה ומיועד לעזור לך להרהר על העבודה שלך ולהעמיק את ההבנה שלך בנושא.';
$string['ai_disclosure_details_toggle'] = 'למידע נוסף על איך זה עובד';
$string['ai_disclosure_detail_analysis'] = 'ההגשה שלך תנותח על ידי בינה מלאכותית כדי להבין את הגישה והנימוק שלך.';
$string['ai_disclosure_detail_questions'] = 'הבינה המלאכותית תייצר {$a} שאלות מותאמות אישית על בסיס ההגשה הספציפית שלך.';
$string['ai_disclosure_detail_quiz'] = 'אתה תעבור חידון של {$a} שאלות המשלב שאלות שנוצרו על ידי המדריך ושאלות מותאמות אישית.';
$string['ai_disclosure_detail_timer'] = 'לכל שאלת חידון יש מגבלת זמן של {$a} שניות כדי לעודד חשיבה מהירה.';
$string['ai_disclosure_detail_privacy'] = 'נתוני ההגשה שלך מעובדים בצורה מאובטחת ומשמשים רק למטרות חינוכיות.';

// Gateway Settings
$string['gateway_settings'] = 'הגדרות שער בינה מלאכותית';
$string['gateway_settings_desc'] = 'הגדרת שער בינה מלאכותית חיצוני לעיבוד בינה מלאכותית. השער מטפל בכל הגדרות ה-API של הבינה המלאכותית כולל מפתח OpenRouter API ובחירת מודל.';
$string['gateway_endpoint'] = 'כתובת נקודת קצה של השער';
$string['gateway_endpoint_desc'] = 'הכתובת של נקודת קצה ה-API של השער החיצוני שלך (לדוגמה, https://your-gateway.com/api)';
$string['gateway_token'] = 'אסימון אימות השער';
$string['gateway_token_desc'] = 'אסימון אימות לתקשורת מאובטחת עם השער (ברירת מחדל: Demo123 לבדיקות)';
$string['gateway_test'] = 'בדיקת חיבור השער';
$string['test_gateway_connection'] = 'בדיקת חיבור השער';

// Cache Management - Settings Integration
$string['cache_management'] = 'ניהול מטמון';
$string['cache_management_widget_desc'] = 'צפייה בסטטיסטיקות מטמון וניהול תגובות שמורות במטמון ישירות מעמוד ההגדרות.';
$string['cache_disabled_message'] = 'המטמון מושבת. הפעל מצב דיבוג למעלה כדי להפעיל שמירת תגובות במטמון.';
$string['cache_stats_error'] = 'שגיאה בטעינת סטטיסטיקות מטמון: {$a}';
$string['full_management'] = 'ניהול מלא';
$string['clear_all'] = 'ניקוי הכל';
$string['clear_instructions'] = 'ניקוי הוראות';
$string['clear_questions'] = 'ניקוי שאלות';
$string['clear_submissions'] = 'ניקוי הגשות';
$string['cleanup_old'] = 'ניקוי ישנות';
$string['confirm_clear_cache'] = 'האם אתה בטוח שאתה רוצה לנקות את כל התגובות השמורות במטמון? פעולה זו לא ניתנת לביטול.';

// Cache Action Results
$string['cache_cleared_success'] = 'כל התגובות השמורות במטמון נוקו בהצלחה.';
$string['instructions_cache_cleared'] = 'מטמון ניתוח ההוראות נוקה.';
$string['questions_cache_cleared'] = 'מטמון יצירת השאלות נוקה.';
$string['submissions_cache_cleared'] = 'מטמון שאלות ההגשות נוקה.';
$string['old_cache_cleaned'] = 'רשומות מטמון ישנות נוקו בהצלחה.';
$string['cache_clear_error'] = 'שגיאה בניקוי מטמון: {$a}';
$string['invalid_action'] = 'התבקשה פעולת מטמון לא תקינה.';

// Quiz Report - NEW STRINGS
$string['quiz_report'] = 'דוח חידון';
$string['quiz_report_assignment_desc'] = 'דוח חידון בינה מלאכותית למטלה זו';
$string['quiz_report_course_desc'] = 'דוח חידון בינה מלאכותית לכל המטלות בקורס זה';
$string['quiz_report_all_desc'] = 'דוח חידון בינה מלאכותית לכל המטלות בכל הקורסים';
$string['back_to_assignment'] = 'חזרה למטלה';
$string['back_to_course'] = 'חזרה לקורס';
$string['quiz_score'] = 'ציון חידון';
$string['details'] = 'פרטים';
$string['view_details'] = 'צפייה בפרטים';
$string['save_all_pending'] = 'שמירת כל הממתינים';
$string['clear_all_grades'] = 'ניקוי כל הציונים';
$string['auto_grade_by_quiz'] = 'מתן ציונים אוטומטי לפי ציון חידון';
$string['auto_grade_by_quiz_desc'] = 'קביעה אוטומטית של ציוני מטלות על בסיס ציוני חידון לכל הסטודנטים';
$string['grading_instructions'] = 'הזן ציונים ישירות בטבלה למטה. שינויים נשמרים אוטומטית לאחר 2 שניות או כאשר אתה עובר לשדה אחר.';
$string['grade_status'] = 'סטטוס ציון';

// Quiz Details
$string['quiz_details'] = 'פרטי חידון';
$string['question'] = 'שאלה';
$string['student_answer'] = 'תשובת הסטודנט';
$string['correct_answer'] = 'תשובה נכונה';
$string['result'] = 'תוצאה';
$string['correct'] = 'נכון';
$string['incorrect'] = 'שגוי';
$string['no_completed_quizzes'] = 'עדיין אף סטודנט לא השלים את חידון הבינה המלאכותית למטלה זו.';
$string['integrity_summary'] = 'סיכום יושרה';
$string['window_blur_events'] = 'אירועי טשטוש חלון';

// Additional Quiz Report Strings
$string['session_info'] = 'מידע מושב';
$string['completed_on'] = 'הושלם ב';
$string['time_taken'] = 'זמן שנלקח';
$string['points'] = 'נקודות';
$string['no_answer'] = 'אין תשובה';
$string['true'] = 'נכון';
$string['false'] = 'שקר';
$string['not_available'] = 'לא זמין';
$string['manual_grading_required'] = 'נדרש מתן ציונים ידני';
$string['integrity_violations_count'] = 'סה"כ הפרות יושרה: {$a}';

// Enhanced Answer Display Strings
$string['raw_answer_value'] = 'תשובה גולמית';
$string['invalid_option_selected'] = 'נבחרה אפשרות לא תקינה';
$string['invalid_boolean_value'] = 'ערך בוליאני לא תקין';
$string['unknown_question_type'] = 'סוג שאלה לא ידוע';

// Direct Grading Strings
$string['grade_saved_successfully'] = 'הציון נשמר בהצלחה';
$string['grade_save_error'] = 'שגיאה בשמירת ציון: {$a}';
$string['bulk_grades_saved'] = 'נשמרו בהצלחה {$a} ציונים';
$string['bulk_grades_partial'] = 'נשמרו {$a->saved} ציונים, {$a->failed} נכשלו';
$string['grades_cleared_success'] = 'כל הציונים נוקו בהצלחה';
$string['grade_clear_error'] = 'שגיאה בניקוי ציונים: {$a}';
$string['confirm_clear_all_grades'] = 'ניקוי כל הציונים';
$string['confirm_clear_all_grades_body'] = 'האם אתה בטוח שאתה רוצה לנקות את כל הציונים? פעולה זו לא ניתנת לביטול.';

// Grade Validation Strings (used in grading_manager.php)
$string['grade_not_numeric'] = 'הציון חייב להיות מספר';
$string['grade_cannot_be_negative'] = 'הציון לא יכול להיות שלילי';
$string['grade_exceeds_maximum'] = 'הציון לא יכול לעלות על המקסימום ({$a})';

// Auto-grading Strings
$string['auto_grade_success'] = 'ניתנו ציונים אוטומטיים בהצלחה ל{$a} סטודנטים על בסיס ציוני חידון';
$string['auto_grade_no_grades'] = 'לא ניתן היה להחיל ציונים. בדוק שהסטודנטים השלימו חידונים.';
$string['auto_grade_error'] = 'שגיאה במהלך מתן ציונים אוטומטי: {$a}';
$string['auto_grade_confirmation'] = 'זה יקבע אוטומטית ציונים על בסיס ציוני חידון לכל הסטודנטים. ציונים קיימים יוחלפו. להמשיך?';
$string['auto_grading_progress'] = 'נותן ציונים אוטומטיים...';
$string['auto_grade_button_text'] = 'מתן ציונים אוטומטי לפי ציון חידון';
$string['error_parsing_grades'] = 'שגיאה בניתוח JSON של ציונים';

// Final Grade
$string['final_grade'] = 'ציון סופי';

// Missing strings used in JavaScript files
$string['setting_update_error'] = 'שגיאה בעדכון הגדרה: {$a}';
$string['no_instructions_error'] = 'לא נמצאו הוראות לניתוח';
$string['input_validation_error'] = 'שגיאה בתיקוף קלט: {$a}';
$string['cache_hit'] = 'פגיעה במטמון - משתמש בתגובה שמורה במטמון';
$string['gateway_error'] = 'שגיאת שער';
$string['no_instructions_questions_error'] = 'לא נמצאו הוראות ליצירת שאלות מהן';
$string['question_bank_title'] = 'בנק שאלות';
$string['question_text_required'] = 'טקסט השאלה נדרש';
$string['all_options_required'] = 'כל אפשרויות התשובה נדרשות';
$string['question_saved_success'] = 'השאלה נשמרה בהצלחה';
$string['confirm_delete_question_title'] = 'מחיקת שאלה';
$string['confirm_delete_question_message'] = 'האם אתה בטוח שאתה רוצה למחוק את השאלה הזו? פעולה זו לא ניתנת לביטול.';
$string['question_deleted_success'] = 'השאלה נמחקה בהצלחה';
$string['ai_quiz_report'] = 'דוח חידון בינה מלאכותית';

// JavaScript UI Strings
$string['instructor_question'] = 'שאלת מדריך';
$string['based_on_submission'] = 'מבוסס על ההגשה שלך';
$string['progress_auto_saved'] = 'ההתקדמות שלך נשמרת אוטומטיט. רענון העמוד יחדש מהשאלה הזו.';
$string['next_question'] = 'שאלה הבאה ←';
$string['submit_final_answers'] = 'הגשת תשובות סופיות';
$string['provide_answer_warning'] = 'אנא ספק תשובה לפני המשך. זכור, אתה לא יכול לחזור לשאלה הזו מאוחר יותר.';
$string['quiz_started_notice'] = 'החידון החל. זכור: אתה לא יכול לחזור לשאלות קודמות או להתחיל מחדש את ההערכה הזו.';
$string['failed_start_session'] = 'כשל בהתחלת מושב חידון';
$string['dev_tools_blocked'] = 'גישה לכלי מפתחים אינה מותרת במהלך החידון.';
$string['quiz_progress_saved'] = 'התקדמות החידון שלך נשמרת אוטומטית. החידון יתחדש מהמקום שבו עצרת כשתחזור.';
$string['quiz_completed_header'] = 'החידון הושלם';
$string['quiz_completed_message'] = 'ההערכה הרשמית שלך הוגשה בהצלחה ולא ניתן לבצעה שוב.';
$string['your_answer'] = 'התשובה שלך: {$a}';
$string['correct_answer_was'] = 'התשובה הנכונה: {$a}';
$string['explanation'] = 'הסבר';
$string['final_grade_notice'] = 'זה הציון הסופי שלך להערכה זו.';
$string['integrity_report_header'] = 'דוח יושרה';
$string['integrity_recorded'] = 'המידע הזה נרשם לבדיקה.';
$string['integrity_violation_header'] = 'הפרת יושרה בהערכה';
$string['quiz_flagged'] = 'ניסיון החידון שלך סומן בגלל פעילות חשודה.';
$string['incident_logged'] = 'התקרית הזו נרשמה ותיבדק על ידי המדריך שלך.';
$string['progress_saved_cannot_continue'] = 'ההתקדמות הנוכחית שלך נשמרה, אבל אתה לא יכול להמשיך בהערכה.';
$string['failed_save_contact_instructor'] = 'כשל בשמירת תוצאות סופיות. אנא צור קשר עם המדריך שלך.';
$string['understand_start_quiz'] = 'אני מבין - התחלת חידון';
$string['important_formal_assessment'] = 'חשוב: הערכה רשמית';
$string['read_carefully'] = 'אנא קרא בעיון לפני ההתחלה:';
$string['one_attempt_only'] = 'ניסיון אחד בלבד: יש לך רק ניסיון אחד להשלמת החידון הזה.';
$string['no_going_back'] = 'אין חזרה: ברגע שאתה עובר לשאלה הבאה, אתה לא יכול לחזור לשאלות קודמות.';
$string['no_restarts'] = 'אין התחלות מחדש: רענון העמוד לא יתחיל מחדש את החידון - הוא יתחדש מהמקום שבו עצרת.';
$string['time_limits'] = 'מגבלות זמן: לכל שאלה יש מגבלת זמן קפדנית. החידון יעבור אוטומטית כאשר הזמן פוקע.';
$string['no_cheating'] = 'בלי רמאות: זו הערכה רשמית. כל ניסיון לרמות או לחבל בחידון ייתפס.';
$string['stay_focused'] = 'הישאר ממוקד: החלפת חלונות או לשוניות יתר על המידה עלולה להיות מסומנת כהתנהגות חשודה.';
$string['cannot_restart_notice'] = 'ברגע שאתה לוחץ על "התחלת חידון", אתה לא יכול להתחיל מחדש או לחזור על ההערכה הזו.';
$string['enter_answer_placeholder'] = 'הזן את התשובה שלך כאן...';

// Missing JavaScript UI Strings
$string['window_switching_warning'] = 'אזהרה: החלפת חלונות/לשוניות {$a->count} פעמים. מקסימום מותר: {$a->max}. החלפה יתר על המידה עלולה לגרום לסיום החידון.';
$string['quiz_progress_complete'] = '{$a}% הושלם';
$string['question_x_of_y'] = 'שאלה {$a->current} מתוך {$a->total}';
$string['time_remaining'] = 'זמן נותר: {$a}';
$string['failed_save_results'] = 'כשל בשמירת תוצאות חידון: {$a}';
$string['final_score'] = 'ציון סופי: {$a->score}/{$a->total} ({$a->percentage}%)';
$string['window_focus_lost'] = 'המיקוד בחלון אבד {$a} פעם(ים) במהלך החידון.';
$string['exceeded_window_switches'] = 'חרגת ממספר החלפות החלונות המותר ({$a}). החידון הופסק.';
$string['setting_updated_success'] = 'ההגדרה "{$a}" עודכנה בהצלחה.';

// Localization strings for recommendation rendering
$string['criteria_evaluation'] = 'הערכת קריטריונים';
$string['criterion'] = 'קריטריון';
$string['met'] = 'התקיים';
$string['suggestions'] = 'הצעות';
$string['evaluation'] = 'הערכה';
$string['improved_assignment'] = 'מטלה משופרת';
$string['no_criteria_provided'] = 'לא סופקו קריטריונים.';
$string['recommendation_error'] = 'שגיאה בהצגת המלצה.';

$string['no_instructions_or_files'] = 'יש לספק הוראות או לפחות קובץ אחד לניתוח';

// Additional strings for code functionality
$string['type'] = 'סוג';
$string['options'] = 'אפשרויות';
$string['text'] = 'טקסט';
$string['entertext'] = 'הזן טקסט';
$string['points_help'] = 'נקודות המוענקות לשאלה זו';
$string['level'] = 'רמה';
$string['optiontext'] = 'טקסט אפשרות';
$string['option_placeholder'] = 'הזן טקסט אפשרות...';
$string['correct_answer_required'] = 'נדרשת לפחות תשובה נכונה אחת';

$string['save_assignment_first'] = 'אנא שמור את המטלה תחילה לפני יצירת שאלות';

// Question Bank Functionality Strings
$string['question_bank'] = 'בנק שאלות';
$string['question_bank_description'] = 'נהל את השאלות שנוצרו בבינה מלאכותית. אתה יכול לצפות, לערוך, למחוק שאלות קיימות, או ליצור חדשות.';
$string['no_questions_found'] = 'לא נמצאו שאלות. צור שאלות כדי להתחיל.';
$string['question_text'] = 'טקסט שאלה';
$string['questions_generated_successfully'] = 'השאלות נוצרו בהצלחה';
$string['error_generating_questions'] = 'שגיאה ביצירת שאלות';
$string['confirm_delete_question'] = 'האם אתה בטוח שאתה רוצה למחוק את השאלה הזו? פעולה זו לא ניתנת לביטול.';
$string['question_deleted_successfully'] = 'השאלה נמחקה בהצלחה';
$string['error_deleting_question'] = 'שגיאה במחיקת שאלה';
$string['plugindisabled'] = 'תוסף מדד איכות אקדמית מושבת';
$string['trustgradedisabled'] = 'מדד איכות אקדמית מושבת למטלה זו';

?>