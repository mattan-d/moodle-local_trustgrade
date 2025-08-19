<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'TrustGrade';
$string['plugin_enabled'] = 'Enable TrustGrade Plugin';
$string['plugin_enabled_desc'] = 'Enable or disable the TrustGrade plugin globally. When disabled, all TrustGrade functionality will be hidden from assignment forms and pages.';
$string['trustgrade_enabled'] = 'Enable TrustGrade for this assignment';
$string['trustgrade_enabled_desc'] = 'Enable TrustGrade AI features for this specific assignment. When disabled, students will not see AI quizzes or related functionality.';
$string['trustgrade_tab'] = 'TrustGrade';
$string['check_instructions'] = 'Check instructions with AI';
$string['ai_recommendation'] = 'AI Recommendation';
$string['processing'] = 'Processing...';
$string['no_instructions'] = 'No instructions found to analyze';
$string['trustgrade_description'] = 'Use AI Gateway to analyze and get recommendations for improving your assignment instructions.';
$string['generate_questions'] = 'Generate Question Bank with AI';
$string['generated_questions'] = 'Generated Questions';
$string['generating_questions'] = 'Generating questions via Gateway...';
$string['loading_question_bank'] = 'Loading question bank...';
$string['questions_generated_success'] = 'Questions generated and saved successfully!';
$string['error_saving_questions'] = 'Error saving generated questions';
$string['debug_mode'] = 'Debug Mode & Caching';
$string['debug_mode_desc'] = 'Enable debug mode to cache Gateway responses and avoid repeated API calls. When enabled, identical requests will return cached responses instead of calling the Gateway. This improves performance and reduces API usage during development and testing.';
$string['cleanup_debug_cache'] = 'Cleanup TrustGrade debug cache';
$string['cleanup_quiz_sessions'] = 'Cleanup TrustGrade quiz sessions';
$string['ai_quiz_title'] = 'AI-Generated Quiz';
$string['no_questions_available'] = 'No questions are available for this assignment.';
$string['next'] = 'Next';
$string['finish_quiz'] = 'Finish Quiz';
$string['quiz_ready_message'] = 'Your AI-generated quiz is ready! This quiz will help you reflect on your submission and reinforce your learning.';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['add_new_question'] = 'Add New Question';
$string['seconds'] = 'seconds';

// Quiz Settings
$string['quiz_settings_title'] = 'Quiz Settings';
$string['questions_to_generate'] = 'Number of questions to generate';
$string['questions_to_generate_help'] = 'Total number of questions to include in the quiz';
$string['question_distribution'] = 'Question Source Distribution';
$string['instructor_questions'] = 'Questions from instructor bank';
$string['instructor_questions_help'] = 'Number of questions to select from the instructor question bank';
$string['submission_questions'] = 'Questions based on submissions';
$string['submission_questions_help'] = 'Number of questions to generate based on student submissions';
$string['randomize_answers'] = 'Randomize answer order';
$string['randomize_answers_desc'] = 'Randomly shuffle the order of answer choices for multiple choice questions.';
$string['time_per_question'] = 'Time per question';
$string['time_per_question_help'] = 'Maximum time allowed per question in seconds';
$string['show_countdown'] = 'Show countdown timer';
$string['show_countdown_desc'] = 'Display a countdown timer for each question. When time expires, the quiz automatically moves to the next question.';

// Disclosure Settings
$string['disclosure_settings'] = 'Student Disclosure Settings';
$string['disclosure_settings_desc'] = 'Configure how students are informed about AI features in assignments.';
$string['show_disclosure'] = 'Show AI disclosure message';
$string['show_disclosure_desc'] = 'Display a disclosure message to students before they submit assignments, informing them about the AI-powered quiz feature.';
$string['custom_disclosure_message'] = 'Custom disclosure message';
$string['custom_disclosure_message_desc'] = 'Optional custom message to display instead of the default disclosure. Leave empty to use the default message.';

// AI Disclosure Messages
$string['ai_disclosure_title'] = 'AI-Enhanced Learning Experience';
$string['ai_disclosure_message'] = 'This assignment includes an AI-powered learning feature. After you submit your work, an AI system will analyze your submission to create personalized quiz questions that help reinforce your learning. This quiz will be available immediately after submission and is designed to help you reflect on your work and deepen your understanding of the subject matter.';
$string['ai_disclosure_details_toggle'] = 'Learn more about how this works';
$string['ai_disclosure_detail_analysis'] = 'Your submission will be analyzed by AI to understand your approach and reasoning.';
$string['ai_disclosure_detail_questions'] = 'The AI will generate {$a} personalized questions based on your specific submission.';
$string['ai_disclosure_detail_quiz'] = 'You\'ll take a {$a}-question quiz combining instructor-created and personalized questions.';
$string['ai_disclosure_detail_timer'] = 'Each quiz question has a {$a}-second time limit to encourage quick thinking.';
$string['ai_disclosure_detail_privacy'] = 'Your submission data is processed securely and used only for educational purposes.';

// Gateway Settings
$string['gateway_settings'] = 'AI Gateway Settings';
$string['gateway_settings_desc'] = 'Configure external AI Gateway for AI processing. The Gateway handles all AI API configurations including OpenRouter API key and model selection.';
$string['gateway_endpoint'] = 'Gateway Endpoint URL';
$string['gateway_endpoint_desc'] = 'The URL of your external AI Gateway API endpoint (e.g., https://your-gateway.com/api)';
$string['gateway_token'] = 'Gateway Authentication Token';
$string['gateway_token_desc'] = 'Authentication token for secure communication with the Gateway (default: Demo123 for testing)';
$string['gateway_test'] = 'Gateway Connection Test';
$string['test_gateway_connection'] = 'Test Gateway Connection';

// Cache Management - Settings Integration
$string['cache_management'] = 'Cache Management';
$string['cache_management_widget_desc'] = 'View cache statistics and manage cached responses directly from the settings page.';
$string['cache_disabled_message'] = 'Caching is disabled. Enable Debug Mode above to activate response caching.';
$string['cache_stats_error'] = 'Error loading cache statistics: {$a}';
$string['full_management'] = 'Full Management';
$string['clear_all'] = 'Clear All';
$string['clear_instructions'] = 'Clear Instructions';
$string['clear_questions'] = 'Clear Questions';
$string['clear_submissions'] = 'Clear Submissions';
$string['cleanup_old'] = 'Cleanup Old';
$string['confirm_clear_cache'] = 'Are you sure you want to clear all cached responses? This action cannot be undone.';

// Cache Action Results
$string['cache_cleared_success'] = 'All cached responses have been cleared successfully.';
$string['instructions_cache_cleared'] = 'Instruction analysis cache has been cleared.';
$string['questions_cache_cleared'] = 'Question generation cache has been cleared.';
$string['submissions_cache_cleared'] = 'Submission questions cache has been cleared.';
$string['old_cache_cleaned'] = 'Old cache records have been cleaned up successfully.';
$string['cache_clear_error'] = 'Error clearing cache: {$a}';
$string['invalid_action'] = 'Invalid cache action requested.';

// Quiz Report - NEW STRINGS
$string['quiz_report'] = 'Quiz Report';
$string['quiz_report_assignment_desc'] = 'AI Quiz Report for this assignment';
$string['quiz_report_course_desc'] = 'AI Quiz Report for all assignments in this course';
$string['quiz_report_all_desc'] = 'AI Quiz Report for all assignments across all courses';
$string['back_to_assignment'] = 'Back to Assignment';
$string['back_to_course'] = 'Back to Course';
$string['quiz_score'] = 'Quiz Score';
$string['details'] = 'Details';
$string['view_details'] = 'View Details';
$string['save_all_pending'] = 'Save All Pending';
$string['clear_all_grades'] = 'Clear All Grades';
$string['auto_grade_by_quiz'] = 'Auto-grade by Quiz Score';
$string['auto_grade_by_quiz_desc'] = 'Automatically set assignment grades based on quiz scores for all students';
$string['grading_instructions'] = 'Enter grades directly in the table below. Changes are auto-saved after 2 seconds or when you move to another field.';
$string['grade_status'] = 'Grade Status';

// Quiz Details
$string['quiz_details'] = 'Quiz Details';
$string['question'] = 'Question';
$string['student_answer'] = 'Student\'s Answer';
$string['correct_answer'] = 'Correct Answer';
$string['result'] = 'Result';
$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';
$string['no_completed_quizzes'] = 'No students have completed the AI quiz for this assignment yet.';
$string['integrity_summary'] = 'Integrity Summary';
$string['window_blur_events'] = 'Window Blur Events';

// Additional Quiz Report Strings
$string['session_info'] = 'Session Information';
$string['completed_on'] = 'Completed On';
$string['time_taken'] = 'Time Taken';
$string['points'] = 'Points';
$string['no_answer'] = 'No Answer';
$string['true'] = 'True';
$string['false'] = 'False';
$string['not_available'] = 'Not Available';
$string['manual_grading_required'] = 'Manual Grading Required';
$string['integrity_violations_count'] = 'Total integrity violations: {$a}';

// Enhanced Answer Display Strings
$string['raw_answer_value'] = 'Raw Answer';
$string['invalid_option_selected'] = 'Invalid option selected';
$string['invalid_boolean_value'] = 'Invalid boolean value';
$string['unknown_question_type'] = 'Unknown question type';

// Direct Grading Strings
$string['grade_saved_successfully'] = 'Grade saved successfully';
$string['grade_save_error'] = 'Error saving grade: {$a}';
$string['bulk_grades_saved'] = 'Successfully saved {$a} grades';
$string['bulk_grades_partial'] = 'Saved {$a->saved} grades, {$a->failed} failed';
$string['grades_cleared_success'] = 'All grades cleared successfully';
$string['grade_clear_error'] = 'Error clearing grades: {$a}';
$string['confirm_clear_all_grades'] = 'Clear All Grades';
$string['confirm_clear_all_grades_body'] = 'Are you sure you want to clear all grades? This action cannot be undone.';

// Grade Validation Strings (used in grading_manager.php)
$string['grade_not_numeric'] = 'Grade must be a number';
$string['grade_cannot_be_negative'] = 'Grade cannot be negative';
$string['grade_exceeds_maximum'] = 'Grade cannot exceed maximum ({$a})';

// Auto-grading Strings
$string['auto_grade_success'] = 'Successfully auto-graded {$a} students based on quiz scores';
$string['auto_grade_no_grades'] = 'No grades could be applied. Check that students have completed quizzes.';
$string['auto_grade_error'] = 'Error during auto-grading: {$a}';
$string['auto_grade_confirmation'] = 'This will automatically set grades based on quiz scores for all students. Existing grades will be overwritten. Continue?';
$string['auto_grading_progress'] = 'Auto-grading...';
$string['auto_grade_button_text'] = 'Auto-grade by Quiz Score';
$string['error_parsing_grades'] = 'Error parsing grades JSON';

// Final Grade
$string['final_grade'] = 'Final Grade';

// Missing strings used in JavaScript files
$string['setting_update_error'] = 'Error updating setting: {$a}';
$string['no_instructions_error'] = 'No instructions found to analyze';
$string['input_validation_error'] = 'Input validation error: {$a}';
$string['cache_hit'] = 'Cache hit - using cached response';
$string['gateway_error'] = 'Gateway error';
$string['no_instructions_questions_error'] = 'No instructions found to generate questions from';
$string['question_bank_title'] = 'Question Bank';
$string['question_text_required'] = 'Question text is required';
$string['all_options_required'] = 'All answer options are required';
$string['question_saved_success'] = 'Question saved successfully';
$string['confirm_delete_question_title'] = 'Delete Question';
$string['confirm_delete_question_message'] = 'Are you sure you want to delete this question? This action cannot be undone.';
$string['question_deleted_success'] = 'Question deleted successfully';
$string['ai_quiz_report'] = 'AI Quiz Report';

// JavaScript UI Strings
$string['instructor_question'] = 'Instructor Question';
$string['based_on_submission'] = 'Based on Your Submission';
$string['progress_auto_saved'] = 'Your progress is automatically saved. Refreshing the page will resume from this question.';
$string['next_question'] = 'Next Question →';
$string['submit_final_answers'] = 'Submit Final Answers';
$string['provide_answer_warning'] = 'Please provide an answer before continuing. Remember, you cannot return to this question later.';
$string['quiz_started_notice'] = 'Quiz started. Remember: you cannot go back to previous questions or restart this assessment.';
$string['failed_start_session'] = 'Failed to start quiz session';
$string['dev_tools_blocked'] = 'Developer tools access is not allowed during the quiz.';
$string['quiz_progress_saved'] = 'Your quiz progress is automatically saved. The quiz will resume from where you left off when you return.';
$string['quiz_completed_header'] = 'Quiz Completed';
$string['quiz_completed_message'] = 'Your formal assessment has been submitted successfully and cannot be retaken.';
$string['your_answer'] = 'Your answer: {$a}';
$string['correct_answer_was'] = 'Correct answer: {$a}';
$string['explanation'] = 'Explanation';
$string['final_grade_notice'] = 'This is your final grade for this assessment.';
$string['integrity_report_header'] = 'Integrity Report';
$string['integrity_recorded'] = 'This information has been recorded for review.';
$string['integrity_violation_header'] = 'Assessment Integrity Violation';
$string['quiz_flagged'] = 'Your quiz attempt has been flagged for suspicious activity.';
$string['incident_logged'] = 'This incident has been logged and will be reviewed by your instructor.';
$string['progress_saved_cannot_continue'] = 'Your current progress has been saved, but you cannot continue the assessment.';
$string['failed_save_contact_instructor'] = 'Failed to save final results. Please contact your instructor.';
$string['understand_start_quiz'] = 'I Understand - Start Quiz';
$string['important_formal_assessment'] = 'Important: Formal Assessment';
$string['read_carefully'] = 'Please read carefully before starting:';
$string['one_attempt_only'] = 'One Attempt Only: You have only ONE attempt to complete this quiz.';
$string['no_going_back'] = 'No Going Back: Once you move to the next question, you cannot return to previous questions.';
$string['no_restarts'] = 'No Restarts: Refreshing the page will NOT restart the quiz - it will resume from where you left off.';
$string['time_limits'] = 'Time Limits: Each question has a strict time limit. The quiz will automatically advance when time expires.';
$string['no_cheating'] = 'No Cheating: This is a formal assessment. Any attempt to cheat or tamper with the quiz will be detected.';
$string['stay_focused'] = 'Stay Focused: Switching windows or tabs excessively may be flagged as suspicious behavior.';
$string['cannot_restart_notice'] = 'Once you click "Start Quiz", you cannot restart or retake this assessment.';
$string['enter_answer_placeholder'] = 'Enter your answer here...';

// Missing JavaScript UI Strings
$string['window_switching_warning'] = 'Warning: You have switched windows/tabs {$a->count} times. Maximum allowed: {$a->max}. Excessive switching may result in quiz termination.';
$string['quiz_progress_complete'] = '{$a}% Complete';
$string['question_x_of_y'] = 'Question {$a->current} of {$a->total}';
$string['time_remaining'] = 'Time Remaining: {$a}';
$string['failed_save_results'] = 'Failed to save quiz results: {$a}';
$string['final_score'] = 'Final Score: {$a->score}/{$a->total} ({$a->percentage}%)';
$string['window_focus_lost'] = 'Window focus was lost {$a} time(s) during the quiz.';
$string['exceeded_window_switches'] = 'You have exceeded the maximum allowed window switches ({$a}). The quiz has been terminated.';
$string['setting_updated_success'] = 'Setting "{$a}" updated successfully.';

// Localization strings for recommendation rendering
$string['criteria_evaluation'] = 'Criteria Evaluation';
$string['criterion'] = 'Criterion';
$string['met'] = 'Met';
$string['suggestions'] = 'Suggestions';
$string['evaluation'] = 'Evaluation';
$string['improved_assignment'] = 'Improved Assignment';
$string['no_criteria_provided'] = 'No criteria provided.';
$string['recommendation_error'] = 'Error displaying recommendation.';

$string['no_instructions_or_files'] = 'Either instructions or at least one file must be provided for analysis';

// Additional strings for code functionality
$string['type'] = 'Type';
$string['options'] = 'Options';
$string['text'] = 'Text';
$string['entertext'] = 'Enter text';
$string['points_help'] = 'Points awarded for this question';
$string['level'] = 'Level';
$string['optiontext'] = 'Option text';
$string['option_placeholder'] = 'Enter option text...';
$string['correct_answer_required'] = 'At least one correct answer is required';

$string['save_assignment_first'] = 'Please save the assignment first before generating questions';

// Question Bank Functionality Strings
$string['question_bank'] = 'Question Bank';
$string['question_bank_description'] = 'Manage your AI-generated questions. You can view, edit, delete existing questions, or generate new ones.';
$string['no_questions_found'] = 'No questions found. Generate some questions to get started.';
$string['question_text'] = 'Question Text';
$string['questions_generated_successfully'] = 'Questions generated successfully';
$string['error_generating_questions'] = 'Error generating questions';
$string['confirm_delete_question'] = 'Are you sure you want to delete this question? This action cannot be undone.';
$string['question_deleted_successfully'] = 'Question deleted successfully';
$string['error_deleting_question'] = 'Error deleting question';
$string['plugindisabled'] = 'TrustGrade plugin is disabled';
$string['trustgradedisabled'] = 'TrustGrade is disabled for this assignment';

$string['quiz_completed'] = 'Quiz Completed';
$string['resubmit_quiz'] = 'Retake Quiz';

?>
