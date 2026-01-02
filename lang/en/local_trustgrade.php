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
$string['plugindisabled'] = 'TrustGrade plugin is disabled';
$string['trustgradedisabled'] = 'TrustGrade is disabled for this assignment';

// Plugin settings.
$string['plugin_enabled'] = 'Enable TrustGrade Plugin';
$string['plugin_enabled_desc'] = 'Enable or disable the TrustGrade plugin globally. When disabled, all TrustGrade functionality will be hidden from assignment forms and pages.';
$string['default_enabled'] = 'Enable TrustGrade by default for new assignments';
$string['default_enabled_desc'] = 'When enabled, TrustGrade will be automatically enabled for newly created assignments. Instructors can still disable it for individual assignments.';
$string['course_specific'] = 'Enable course-specific availability';
$string['course_specific_desc'] = 'When enabled, TrustGrade will only be available for specific courses selected below. When disabled, TrustGrade is available for all courses.';
$string['enabled_courses'] = 'Enabled courses';
$string['enabled_courses_desc'] = 'Select the courses where TrustGrade should be available. This setting only applies when course-specific availability is enabled.';
$string['enable_course_specific_first'] = 'Please enable "Enable course-specific availability" above to select courses.';
$string['no_courses_available'] = 'No courses available in the system.';
$string['search_courses'] = 'Search courses...';
$string['course_not_enabled'] = 'TrustGrade is not available for this course. Please contact your administrator.';

// Assignment settings.
$string['trustgrade_enabled'] = 'Enable TrustGrade for this assignment';
$string['trustgrade_enabled_desc'] = 'Enable TrustGrade AI features for this specific assignment. When disabled, students will not see AI quizzes or related functionality.';
$string['trustgrade_tab'] = 'TrustGrade';
$string['trustgrade_description'] = 'Use AI Gateway to analyze and get recommendations for improving your assignment instructions.';
$string['save_assignment_first'] = 'Please save the assignment first before generating questions';

// AI Gateway Settings.
$string['gateway_settings'] = 'AI Gateway Settings';
$string['gateway_settings_desc'] = 'Configure external AI Gateway for AI processing. The Gateway handles all AI API configurations including OpenRouter API key and model selection.';
$string['gateway_endpoint'] = 'Gateway Endpoint URL';
$string['gateway_endpoint_desc'] = 'The URL of your external AI Gateway API endpoint (e.g., https://your-gateway.com/api)';
$string['gateway_token'] = 'Gateway Authentication Token';
$string['gateway_token_desc'] = 'Authentication token for secure communication with the Gateway (default: Demo123 for testing)';
$string['gateway_test'] = 'Gateway Connection Test';
$string['test_gateway_connection'] = 'Test Gateway Connection';

// Instruction analysis.
$string['check_instructions'] = 'Check instructions with AI';
$string['ai_recommendation'] = 'AI Recommendation';
$string['processing'] = 'Processing...';
$string['no_instructions'] = 'No instructions found to analyze';
$string['no_instructions_error'] = 'No instructions found to analyze';
$string['no_instructions_or_files'] = 'Either instructions or at least one file must be provided for analysis';
$string['no_instructions_questions_error'] = 'No instructions found to generate questions from';

// Question generation.
$string['generate_questions'] = 'Generate Question Bank with AI';
$string['generated_questions'] = 'Generated Questions';
$string['generating_questions'] = 'Generating questions via Gateway...';
$string['questions_generated_success'] = 'Questions generated and saved successfully!';
$string['questions_generated_successfully'] = 'Questions generated successfully';
$string['error_saving_questions'] = 'Error saving generated questions';
$string['error_generating_questions'] = 'Error generating questions';
$string['processing_question_generation'] = 'Processing Question Generation';
$string['processing_question_generation_message'] = 'Please wait while we generate questions for your assignment...';

// Auto-generate questions.
$string['auto_generate_questions'] = 'Create questions for this assignment';
$string['auto_generate_questions_desc'] = 'Automatically generate questions when the assignment is saved. Questions will be created based on the assignment instructions.';
$string['questions_generation_failed'] = 'Failed to generate questions automatically';
$string['questions_generation_error'] = 'Error occurred during automatic question generation';
$string['questions_will_be_generated'] = 'Questions will be generated automatically for this assignment';

// Question bank.
$string['question_bank'] = 'Question Bank';
$string['question_bank_description'] = 'Manage your AI-generated questions. You can view, edit, delete existing questions, or generate new ones.';
$string['question_bank_title'] = 'Question Bank';
$string['loading_question_bank'] = 'Loading question bank...';
$string['no_questions_available'] = 'No questions are available for this assignment.';
$string['no_questions_found'] = 'No questions found. Generate some questions to get started.';
$string['add_new_question'] = 'Add New Question';

// Question fields.
$string['question'] = 'Question';
$string['question_text'] = 'Question Text';
$string['question_text_required'] = 'Question text is required';
$string['enter_question_text'] = 'Enter question text...';
$string['click_edit_add_question'] = 'Click edit to add question text';
$string['type'] = 'Type';
$string['multiple_choice'] = 'Multiple Choice';
$string['true_false'] = 'True/False';
$string['short_answer'] = 'Short Answer';
$string['options'] = 'Options';
$string['options_label'] = 'Options:';
$string['option_text'] = 'Option text';
$string['optiontext'] = 'Option text';
$string['option_placeholder'] = 'Enter option text...';
$string['all_options_required'] = 'All answer options are required';
$string['click_edit_add_options'] = 'Click edit to add answer options';
$string['correct_answer'] = 'Correct Answer';
$string['correct_answer_required'] = 'At least one correct answer is required';
$string['explanation'] = 'Explanation';
$string['enter_explanation_option'] = 'Enter explanation for this option...';
$string['explanation_for_true'] = 'Explanation for True';
$string['explanation_for_false'] = 'Explanation for False';
$string['points'] = 'Points';
$string['points_help'] = 'Points awarded for this question';
$string['level'] = 'Level';
$string['blooms_level'] = 'Bloom\'s Level';
$string['blooms_level_label'] = 'Bloom\'s Level';

// Bloom's taxonomy levels.
$string['blooms_remembering'] = 'Remember';
$string['blooms_understanding'] = 'Understand';
$string['blooms_applying'] = 'Apply';
$string['blooms_analyzing'] = 'Analyze';
$string['blooms_evaluating'] = 'Evaluate';

$string['blooms_remember'] = 'Remember';
$string['blooms_understand'] = 'Understand';
$string['blooms_apply'] = 'Apply';
$string['blooms_analyze'] = 'Analyze';
$string['blooms_evaluate'] = 'Evaluate';

$string['mandatory_question'] = 'Mandatory Question';
$string['mandatory_question_help'] = 'This question will always appear in student quizzes';
$string['make_mandatory'] = 'Mark as Mandatory';
$string['remove_mandatory'] = 'Remove Mandatory';
$string['question_marked_mandatory'] = 'Question marked as mandatory';
$string['question_unmarked_mandatory'] = 'Question unmarked as mandatory';
$string['error_updating_question'] = 'Error updating question';

// Question source types.
$string['question_source_instructor'] = 'Instructor';
$string['question_source_ai_generated'] = 'AI Generated';
$string['question_source_submission'] = 'Submission Based';
$string['instructor_question'] = 'Instructor Question';
$string['based_on_submission'] = 'Based on Your Submission';

// Question actions.
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['save_changes'] = 'Save Changes';
$string['question_saved_success'] = 'Question saved successfully';
$string['confirm_delete_question'] = 'Are you sure you want to delete this question? This action cannot be undone.';
$string['confirm_delete_question_title'] = 'Delete Question';
$string['confirm_delete_question_message'] = 'Are you sure you want to delete this question? This action cannot be undone.';
$string['question_deleted_success'] = 'Question deleted successfully';
$string['question_deleted_successfully'] = 'Question deleted successfully';
$string['error_deleting_question'] = 'Error deleting question';

// Debug mode and caching.
$string['debug_mode'] = 'Debug Mode & Caching';
$string['debug_mode_desc'] = 'Enable debug mode to cache Gateway responses and avoid repeated API calls. When enabled, identical requests will return cached responses instead of calling the Gateway. This improves performance and reduces API usage during development and testing.';
$string['cleanup_debug_cache'] = 'Cleanup TrustGrade debug cache';
$string['cleanup_quiz_sessions'] = 'Cleanup TrustGrade quiz sessions';
$string['task_retry_failed_async_tasks'] = 'Retry failed TrustGrade async tasks';

// Cache management.
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
$string['cache_hit'] = 'Cache hit - using cached response';

// Cache action results.
$string['cache_cleared_success'] = 'All cached responses have been cleared successfully.';
$string['instructions_cache_cleared'] = 'Instruction analysis cache has been cleared.';
$string['questions_cache_cleared'] = 'Question generation cache has been cleared.';
$string['submissions_cache_cleared'] = 'Submission questions cache has been cleared.';
$string['old_cache_cleaned'] = 'Old cache records have been cleaned up successfully.';
$string['cache_clear_error'] = 'Error clearing cache: {$a}';
$string['invalid_action'] = 'Invalid cache action requested.';

// Quiz settings.
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

// Disclosure settings.
$string['disclosure_settings'] = 'Student Disclosure Settings';
$string['disclosure_settings_desc'] = 'Configure how students are informed about AI features in assignments.';
$string['show_disclosure'] = 'Show AI disclosure message';
$string['show_disclosure_desc'] = 'Display a disclosure message to students before they submit assignments, informing them about the AI-powered quiz feature.';
$string['custom_disclosure_message'] = 'Custom disclosure message';
$string['custom_disclosure_message_desc'] = 'Optional custom message to display instead of the default disclosure. Leave empty to use the default message.';

// AI disclosure messages.
$string['ai_disclosure_title'] = 'AI-Enhanced Learning Experience';
$string['ai_disclosure_message'] = 'This assignment includes an AI-powered learning feature. After you submit your work, an AI system will analyze your submission to create personalized quiz questions that help reinforce your learning. This quiz will be available immediately after submission and is designed to help you reflect on your work and deepen your understanding of the subject matter.';
$string['ai_disclosure_details_toggle'] = 'Learn more about how this works';
$string['ai_disclosure_detail_analysis'] = 'Your submission will be analyzed by AI to understand your approach and reasoning.';
$string['ai_disclosure_detail_questions'] = 'The AI will generate {$a} personalized questions based on your specific submission.';
$string['ai_disclosure_detail_quiz'] = 'You\'ll take a {$a}-question quiz combining instructor-created and personalized questions.';
$string['ai_disclosure_detail_timer'] = 'Each quiz question has a {$a}-second time limit to encourage quick thinking.';
$string['ai_disclosure_detail_privacy'] = 'Your submission data is processed securely and used only for educational purposes.';

// Quiz interface.
$string['ai_quiz_title'] = 'AI-Generated Quiz';
$string['ai_quiz_report'] = 'AI Quiz Report';
$string['quiz_ready_message'] = 'Your AI-generated quiz is ready! This quiz will help you reflect on your submission and reinforce your learning.';
$string['quiz_ready_subject'] = 'Your AI Quiz is Ready';
$string['quiz_ready_message_html'] = 'Your AI-generated quiz is ready! Click <a href="{$a->quizurl}">here</a> to take the quiz.';
$string['quiz_generation_failed_subject'] = 'Quiz Generation Failed';
$string['quiz_generation_failed_message'] = 'We were unable to generate your quiz. Please contact your instructor.';
$string['take_quiz'] = 'Take Quiz';
$string['next'] = 'Next';
$string['next_question'] = 'Next Question →';
$string['finish_quiz'] = 'Finish Quiz';
$string['submit_final_answers'] = 'Submit Final Answers';
$string['seconds'] = 'seconds';
$string['text'] = 'Text';
$string['entertext'] = 'Enter text';
$string['enter_answer_placeholder'] = 'Enter your answer here...';
$string['quiz_preparing'] = 'Your quiz is being prepared...';
$string['quiz_preparing_message'] = 'Generating quiz for {$a}';
$string['quiz_ready'] = 'Your quiz is ready!';
$string['quiz_ready_message'] = 'Click to take the quiz for {$a}';
$string['quiz_ready_subject'] = 'Your AI Quiz is Ready';

// Quiz progress and navigation.
$string['progress_auto_saved'] = 'Your progress is automatically saved. Refreshing the page will resume from this question.';
$string['provide_answer_warning'] = 'Please provide an answer before continuing. Remember, you cannot return to this question later.';
$string['quiz_started_notice'] = 'Quiz started. Remember: you cannot go back to previous questions or restart this assessment.';
$string['failed_start_session'] = 'Failed to start quiz session';
$string['quiz_progress_saved'] = 'Your quiz progress is automatically saved. The quiz will resume from where you left off when you return.';
$string['quiz_progress_complete'] = '{$a}% Complete';
$string['question_x_of_y'] = 'Question {$a->current} of {$a->total}';
$string['time_remaining'] = 'Time Remaining: {$a}';

// Quiz completion.
$string['quiz_completed_header'] = 'Quiz Completed';
$string['quiz_completed_message'] = 'Your formal assessment has been submitted successfully and cannot be retaken.';
$string['your_answer'] = 'Your answer: {$a}';
$string['correct_answer_was'] = 'Correct answer: {$a}';
$string['final_grade_notice'] = 'This is your final grade for this assessment.';
$string['final_score'] = 'Final Score: {$a->score}/{$a->total} ({$a->percentage}%)';
$string['failed_save_results'] = 'Failed to save quiz results: {$a}';
$string['failed_save_contact_instructor'] = 'Failed to save final results. Please contact your instructor.';

// Quiz integrity.
$string['integrity_report_header'] = 'Integrity Report';
$string['integrity_recorded'] = 'This information has been recorded for review.';
$string['integrity_violation_header'] = 'Assessment Integrity Violation';
$string['quiz_flagged'] = 'Your quiz attempt has been flagged for suspicious activity.';
$string['incident_logged'] = 'This incident has been logged and will be reviewed by your instructor.';
$string['progress_saved_cannot_continue'] = 'Your current progress has been saved, but you cannot continue the assessment.';
$string['dev_tools_blocked'] = 'Developer tools access is not allowed during the quiz.';
$string['window_switching_warning'] = 'Warning: You have switched windows/tabs {$a->count} times. Maximum allowed: {$a->max}. Excessive switching may result in quiz termination.';
$string['window_focus_lost'] = 'Window focus was lost {$a} time(s) during the quiz.';
$string['exceeded_window_switches'] = 'You have exceeded the maximum allowed window switches ({$a}). The quiz has been terminated.';

// Quiz instructions.
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

// Quiz report.
$string['quiz_report'] = 'Quiz Report';
$string['quiz_report_assignment_desc'] = 'AI Quiz Report for this assignment';
$string['quiz_report_course_desc'] = 'AI Quiz Report for all assignments in this course';
$string['quiz_report_all_desc'] = 'AI Quiz Report for all assignments across all courses';
$string['back_to_assignment'] = 'Back to Assignment';
$string['back_to_course'] = 'Back to Course';
$string['quiz_score'] = 'Quiz Score';
$string['details'] = 'Details';
$string['view_details'] = 'View Details';

// Quiz details.
$string['quiz_details'] = 'Quiz Details';
$string['session_info'] = 'Session Information';
$string['completed_on'] = 'Completed On';
$string['time_taken'] = 'Time Taken';
$string['student_answer'] = 'Student\'s Answer';
$string['result'] = 'Result';
$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';
$string['no_completed_quizzes'] = 'No students have completed the AI quiz for this assignment yet.';
$string['integrity_summary'] = 'Integrity Summary';
$string['window_blur_events'] = 'Window Blur Events';
$string['no_answer'] = 'No Answer';
$string['true'] = 'True';
$string['false'] = 'False';
$string['not_available'] = 'Not Available';
$string['manual_grading_required'] = 'Manual Grading Required';
$string['integrity_violations_count'] = 'Total integrity violations: {$a}';

// Answer display strings.
$string['raw_answer_value'] = 'Raw Answer';
$string['invalid_option_selected'] = 'Invalid option selected';
$string['invalid_boolean_value'] = 'Invalid boolean value';
$string['unknown_question_type'] = 'Unknown question type';

// Grading.
$string['final_grade'] = 'Final Grade';
$string['save_all_pending'] = 'Save All Pending';
$string['clear_all_grades'] = 'Clear All Grades';
$string['grading_instructions'] = 'Enter grades directly in the table below. Changes are auto-saved after 2 seconds or when you move to another field.';
$string['grade_status'] = 'Grade Status';
$string['grade_saved_successfully'] = 'Grade saved successfully';
$string['grade_save_error'] = 'Error saving grade: {$a}';
$string['grade_pending_save'] = 'Grade pending save';
$string['saving_grade'] = 'Saving grade...';
$string['grade_saved'] = 'Grade saved';
$string['error_saving_grade'] = 'Error saving grade';
$string['error_saving_grade_user'] = 'Error saving grade for user {$a}';
$string['user_label'] = 'User';
$string['error_calculate_grade_from_quiz'] = 'Could not calculate grade from quiz score';
$string['unsaved_changes'] = '{$a} unsaved changes';
$string['unsaved_changes_single'] = '1 unsaved change';
$string['no_pending_grades'] = 'No pending grades to save.';
$string['saving_grades'] = 'Saving...';
$string['grades_saved_success'] = '{$a} grades saved successfully';
$string['error_saving_grades'] = 'Error saving grades';
$string['clearing_grades'] = 'Clearing...';

// Bulk grading.
$string['bulk_grades_saved'] = 'Successfully saved {$a} grades';
$string['bulk_grades_partial'] = 'Saved {$a->saved} grades, {$a->failed} failed';
$string['grades_cleared_success'] = 'All grades cleared successfully';
$string['grade_clear_error'] = 'Error clearing grades: {$a}';
$string['confirm_clear_all_grades'] = 'Clear All Grades';
$string['confirm_clear_all_grades_body'] = 'Are you sure you want to clear all grades? This action cannot be undone.';

// Grade validation.
$string['grade_not_numeric'] = 'Grade must be a number';
$string['grade_cannot_be_negative'] = 'Grade cannot be negative';
$string['grade_exceeds_maximum'] = 'Grade cannot exceed maximum ({$a})';

// Auto-grading.
$string['auto_grade_by_quiz'] = 'Auto-grade by Quiz Score';
$string['auto_grade_by_quiz_desc'] = 'Automatically set assignment grades based on quiz scores for all students';
$string['auto_grade_button_text'] = 'Auto-grade by Quiz Score';
$string['auto_grade_success'] = '{$a} students auto-graded based on quiz scores';
$string['auto_grade_no_grades'] = 'No grades could be applied. Check that students have completed quizzes.';
$string['auto_grade_error'] = 'Error auto-grading students';
$string['auto_grade_confirmation'] = 'This will automatically set grades based on quiz scores for all students. Existing grades will be overwritten. Continue?';
$string['auto_grading_progress'] = 'Auto-grading...';
$string['error_parsing_grades'] = 'Error parsing grades JSON';

// Submission processing.
$string['processing_submission'] = 'Processing Your Submission';
$string['processing_submission_message'] = 'Please wait while we process your assignment submission...';
$string['processing_please_wait'] = 'Please wait...';
$string['error_trustgrade_not_enabled'] = 'TrustGrade is not enabled for this activity';
$string['error_submission_must_be_array'] = 'Submission content must be a structured array';
$string['error_submission_text_or_file_required'] = 'Either submission text or at least one file is required';

// UI Messages.
$string['setting_update_error'] = 'Error updating setting: {$a}';
$string['setting_updated_success'] = 'Setting "{$a}" updated successfully.';
$string['input_validation_error'] = 'Input validation error: {$a}';
$string['gateway_error'] = 'Gateway error';

// Recommendation rendering.
$string['criteria_evaluation'] = 'Criteria Evaluation';
$string['criterion'] = 'Criterion';
$string['met'] = 'Met';
$string['suggestions'] = 'Suggestions';
$string['evaluation'] = 'Evaluation';
$string['improved_assignment'] = 'Improved Assignment';
$string['no_criteria_provided'] = 'No criteria provided.';
$string['recommendation_error'] = 'Error displaying recommendation.';

// Privacy API metadata descriptions.
$string['privacy:metadata:local_trustgrade_logs'] = 'Stores logs of TrustGrade instruction analysis requests and AI recommendations.';
$string['privacy:metadata:local_trustgrade_logs:userid'] = 'The ID of the user who requested the instruction analysis.';
$string['privacy:metadata:local_trustgrade_logs:cmid'] = 'The course module ID where the analysis was performed.';
$string['privacy:metadata:local_trustgrade_logs:instructions'] = 'The assignment instructions that were analyzed.';
$string['privacy:metadata:local_trustgrade_logs:recommendation'] = 'The AI-generated recommendation for the instructions.';
$string['privacy:metadata:local_trustgrade_logs:timecreated'] = 'The time when the analysis request was made.';

$string['privacy:metadata:local_trustgrade_questions'] = 'Stores AI-generated questions for assignments created by instructors.';
$string['privacy:metadata:local_trustgrade_questions:cmid'] = 'The course module ID for which questions were generated.';
$string['privacy:metadata:local_trustgrade_questions:userid'] = 'The ID of the instructor who generated the questions.';
$string['privacy:metadata:local_trustgrade_questions:question_data'] = 'The question data including text, type, options, and correct answers.';
$string['privacy:metadata:local_trustgrade_questions:timecreated'] = 'The time when the questions were created.';
$string['privacy:metadata:local_trustgrade_questions:timemodified'] = 'The time when the questions were last modified.';

$string['privacy:metadata:local_trustgd_sub_questions'] = 'Stores AI-generated questions based on individual student submissions.';
$string['privacy:metadata:local_trustgd_sub_questions:submission_id'] = 'The submission ID that the questions are based on.';
$string['privacy:metadata:local_trustgd_sub_questions:cmid'] = 'The course module ID associated with the submission.';
$string['privacy:metadata:local_trustgd_sub_questions:userid'] = 'The ID of the student whose submission was analyzed.';
$string['privacy:metadata:local_trustgd_sub_questions:question_data'] = 'The personalized question data generated from the submission.';
$string['privacy:metadata:local_trustgd_sub_questions:timecreated'] = 'The time when the questions were generated.';
$string['privacy:metadata:local_trustgd_sub_questions:timemodified'] = 'The time when the questions were last modified.';

$string['privacy:metadata:local_trustgrade_debug'] = 'Stores debug information and cached API responses for development purposes.';
$string['privacy:metadata:local_trustgrade_debug:userid'] = 'The ID of the user who triggered the API request.';
$string['privacy:metadata:local_trustgrade_debug:cmid'] = 'The course module ID associated with the request.';
$string['privacy:metadata:local_trustgrade_debug:request_type'] = 'The type of API request made.';
$string['privacy:metadata:local_trustgrade_debug:request_data'] = 'The data sent in the API request.';
$string['privacy:metadata:local_trustgrade_debug:raw_response'] = 'The raw response received from the API.';
$string['privacy:metadata:local_trustgrade_debug:parsed_response'] = 'The parsed API response data.';
$string['privacy:metadata:local_trustgrade_debug:timecreated'] = 'The time when the request was made.';

$string['privacy:metadata:local_trustgd_quiz_sessions'] = 'Stores quiz session state including student answers and integrity monitoring data.';
$string['privacy:metadata:local_trustgd_quiz_sessions:cmid'] = 'The course module ID for the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:submissionid'] = 'The submission ID associated with this quiz session.';
$string['privacy:metadata:local_trustgd_quiz_sessions:userid'] = 'The ID of the student taking the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:questions_data'] = 'The questions presented in the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:settings_data'] = 'The quiz settings applied for this session.';
$string['privacy:metadata:local_trustgd_quiz_sessions:current_question'] = 'The current question number in the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:answers_data'] = 'The student\'s answers to quiz questions.';
$string['privacy:metadata:local_trustgd_quiz_sessions:time_remaining'] = 'The time remaining for the current question.';
$string['privacy:metadata:local_trustgd_quiz_sessions:window_blur_count'] = 'The number of times the student switched windows/tabs during the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:attempt_started'] = 'Whether the quiz attempt has been started.';
$string['privacy:metadata:local_trustgd_quiz_sessions:attempt_completed'] = 'Whether the quiz attempt has been completed.';
$string['privacy:metadata:local_trustgd_quiz_sessions:integrity_violations'] = 'Recorded integrity violations during the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:final_score'] = 'The final score achieved on the quiz.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timecreated'] = 'The time when the quiz session was created.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timemodified'] = 'The time when the quiz session was last modified.';
$string['privacy:metadata:local_trustgd_quiz_sessions:timecompleted'] = 'The time when the quiz was completed.';

$string['privacy:metadata:ai_gateway'] = 'Personal data is sent to an external AI Gateway service for processing assignment instructions and generating personalized questions.';
$string['privacy:metadata:ai_gateway:userid'] = 'The user ID may be included in metadata for tracking purposes.';
$string['privacy:metadata:ai_gateway:instructions'] = 'Assignment instructions are sent to the AI Gateway for analysis.';
$string['privacy:metadata:ai_gateway:submission_text'] = 'Student submission text is sent to generate personalized quiz questions.';
$string['privacy:metadata:ai_gateway:files'] = 'File attachments may be sent for analysis and question generation.';
$string['privacy:metadata:ai_gateway:metadata'] = 'Additional contextual information (course ID, course name, module ID) is sent with requests.';

// Privacy export paths.
$string['privacy:path:logs'] = 'TrustGrade Instruction Analysis Logs';
$string['privacy:path:questions'] = 'TrustGrade Generated Questions';
$string['privacy:path:submission_questions'] = 'TrustGrade Submission-Based Questions';
$string['privacy:path:quiz_sessions'] = 'TrustGrade Quiz Sessions';
$string['privacy:path:debug'] = 'TrustGrade Debug Data';

// Cache management page strings
// Cache management page.
$string['cache_management_title'] = 'TrustGrade Cache Management';
$string['cache_management_heading'] = 'TrustGrade Cache Management';
$string['debug_mode_disabled_info'] = 'Debug mode is currently disabled. Enable debug mode in plugin settings to use caching features.';
$string['debug_mode_enabled_info'] = 'Debug mode is enabled. Gateway responses are being cached to improve performance.';
$string['cache_statistics'] = 'Cache Statistics';
$string['total_cached_responses'] = 'Total Cached Responses';
$string['last_24_hours'] = 'Last 24 Hours';
$string['cacheable_responses'] = 'Cacheable Responses';
$string['cache_efficiency'] = 'Cache Efficiency';
$string['cache_by_type'] = 'Cache by Request Type';
$string['request_type'] = 'Request Type';
$string['cached_responses'] = 'Cached Responses';
$string['actions'] = 'Actions';
$string['clear'] = 'Clear';
$string['recent_cache_activity'] = 'Recent Cache Activity';
$string['time'] = 'Time';
$string['status'] = 'Status';
$string['cached'] = 'Cached';
$string['not_cached'] = 'Not Cached';
$string['error_loading_cache_stats'] = 'Error loading cache statistics: {$a}';
$string['cache_management_actions'] = 'Cache Management Actions';
$string['clear_all_cache_title'] = 'Clear All Cache';
$string['clear_all_cache_desc'] = 'Remove all cached Gateway responses. This will force fresh requests to the Gateway.';
$string['clear_all_cache_button'] = 'Clear All Cache';
$string['cleanup_old_records_title'] = 'Cleanup Old Records';
$string['cleanup_old_records_desc'] = 'Remove cache records older than 7 days to free up database space.';
$string['cleanup_old_records_button'] = 'Cleanup Old Records';
$string['related_pages'] = 'Related Pages';
$string['plugin_settings'] = 'Plugin Settings';
$string['all_cache_cleared'] = 'All cache cleared successfully';
$string['instruction_cache_cleared'] = 'Instruction analysis cache cleared';
$string['question_cache_cleared'] = 'Question generation cache cleared';
$string['submission_cache_cleared'] = 'Submission questions cache cleared';
$string['old_cache_cleaned_up'] = 'Old cache records cleaned up';

// Gateway test page strings
// Gateway test page.
$string['gateway_test_title'] = 'AI Gateway Test';
$string['gateway_test_heading'] = 'AI Gateway Connection Test';
$string['gateway_connection_success'] = 'Connection successful';
$string['gateway_connection_failed'] = 'Connection failed: {$a}';
$string['gateway_configuration'] = 'Gateway Configuration';
$string['gateway_endpoint_label'] = 'Endpoint';
$string['gateway_token_label'] = 'Token';
$string['gateway_token_configured'] = 'Configured';
$string['gateway_token_not_configured'] = 'Not configured';
$string['gateway_openrouter_note'] = 'Note: OpenRouter API Key and Model are configured in the Gateway server, not in the plugin.';
$string['gateway_troubleshooting'] = 'Troubleshooting';
$string['gateway_verify_url'] = 'Verify the Gateway endpoint URL is correct and accessible';
$string['gateway_check_token'] = 'Check that the Gateway authentication token is valid';
$string['gateway_ensure_running'] = 'Ensure the Gateway server is running and responding';
$string['gateway_verify_apikey'] = 'Verify the Gateway has a valid OpenRouter API key configured';
$string['gateway_config_error'] = 'Configuration error: {$a}';
$string['gateway_config_required'] = 'Configuration Required';
$string['gateway_config_endpoint'] = 'Configure the Gateway endpoint URL in plugin settings';
$string['gateway_config_token'] = 'Set the Gateway authentication token (use "Demo123" for testing)';
$string['gateway_config_openrouter'] = 'Ensure the Gateway server has OpenRouter API key configured';
$string['configure_gateway_settings'] = 'Configure Gateway Settings';

// Question editor validation strings
// Question editor validation.
$string['question_saved_successfully_msg'] = 'Question saved successfully';

$string['question_saved_successfully'] = 'Question saved successfully';

$string['failed_save_question'] = 'Failed to save question: {$a}';
$string['question_not_found_error'] = 'Question not found';
$string['question_deleted_successfully_msg'] = 'Question deleted successfully';
$string['failed_delete_question'] = 'Failed to delete question: {$a}';
$string['invalid_json_error'] = 'Invalid JSON: {$a}';
$string['question_data_must_be_array'] = 'Question data must be an associative array';
$string['question_type_required'] = 'Question type is required';
$string['invalid_question_type'] = 'Invalid question type';
$string['question_text_field_required'] = 'Question text (field "text") is required';
$string['options_must_be_array'] = 'Options must be provided as an array';
$string['at_least_2_options_required'] = 'At least 2 options are required';
$string['option_must_be_object'] = 'Each option must be an object';
$string['option_non_numeric_id'] = 'Option at index {$a} has non-numeric id';
$string['option_text_required'] = 'Option at index {$a} must include non-empty \'text\'';
$string['option_is_correct_required'] = 'Option at index {$a} must include \'is_correct\'';
$string['option_is_correct_invalid'] = 'Option at index {$a} has invalid \'is_correct\' (must be boolean)';
$string['option_explanation_invalid'] = 'Option at index {$a} has invalid \'explanation\' (must be string)';
$string['at_least_one_correct_option'] = 'Multiple choice questions must have at least one correct option';
$string['metadata_must_be_object'] = 'Metadata must be an object';
$string['points_must_be_1_to_100'] = 'Points must be between 1 and 100';
$string['blooms_level_must_be_string'] = 'Metadata \'blooms_level\' must be a string';

$string['defaultcoursestudent'] = 'Student';
$string['mins_secs'] = '{$a->minutes}m {$a->seconds}s';
$string['secs_only'] = '{$a}s';

$string['cachedef_quiz_redirect'] = 'Stores quiz redirect URLs for temporary session management';
$string['nopermission'] = 'You do not have permission to access this page';

?>
