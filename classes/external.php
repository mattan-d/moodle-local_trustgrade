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
 * External API for TrustGrade plugin web services.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/trustgrade/classes/grading_manager.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/api.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/question_generator.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/question_editor.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/question_bank_renderer.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/quiz_settings.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/quiz_session.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/async_task_manager.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/instructor_generation_manager.php');
require_once($CFG->libdir . '/externallib.php');

class external extends \external_api {

 /**
  * Validate context for a given course module ID for grading capabilities
  *
  * @param int $cmid
  */
 private static function validate_grading_context($cmid) {
     $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
     $context = \context_module::instance($cm->id);
     self::validate_context($context);
     require_capability('mod/assign:grade', $context);
 }

 /**
  * Validate context for a given course module ID for editing capabilities
  *
  * @param int $cmid
  */
 private static function validate_editing_context($cmid) {
     if ($cmid > 0) {
         $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
         $context = \context_module::instance($cm->id);
         self::validate_context($context);
         require_capability('mod/assign:addinstance', $context);
     }
     // If cmid is 0, it's a new assignment, so we don't check capabilities here.
 }

 /**
  * Validate context for a given course module ID for submission capabilities
  *
  * @param int $cmid
  */
 private static function validate_submission_context($cmid) {
     $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
     $context = \context_module::instance($cm->id);
     self::validate_context($context);
     require_capability('mod/assign:submit', $context);
 }

 /**
  * Build a textual summary of intro attachments (kept for compatibility; not used in Gateway request).
  */
 private static function collect_intro_attachments_text(int $intro_itemid, int $intro_attachments_itemid): string {
     global $USER;
     $parts = [];

     $readDraft = function(int $itemid) use ($USER) {
         if ($itemid <= 0) {
             return [];
         }
         $fs = get_file_storage();
         $userctx = \context_user::instance($USER->id);
         $files = $fs->get_area_files($userctx->id, 'user', 'draft', $itemid, 'sortorder, id', false);
         return $files ?: [];
     };

     $maxFiles = 10;
     $maxPreviewBytes = 8192;

     $all = [];
     foreach ([$intro_itemid, $intro_attachments_itemid] as $did) {
         $all = array_merge($all, $readDraft($did));
     }

     if (empty($all)) {
         return '';
     }

     $count = 0;
     foreach ($all as $file) {
         if ($count >= $maxFiles) {
             $parts[] = '- ...';
             break;
         }
         if ($file->is_directory()) {
             continue;
         }
         $count++;
         $filename = $file->get_filename();
         $mimetype = $file->get_mimetype();
         $size = $file->get_filesize();
         $parts[] = '- ' . $filename . ' (' . $mimetype . ', ' . display_size($size) . ')';

         $isTextLike = (
             strpos($mimetype, 'text/') === 0
             || in_array($mimetype, ['application/json', 'application/xml'])
         );

         if ($isTextLike) {
             try {
                 $content = $file->get_content();
                 if (is_string($content) && $content !== '') {
                     $snippet = mb_substr($content, 0, $maxPreviewBytes);
                     $snippet = preg_replace("/\r\n|\r/", "\n", $snippet);
                     $parts[] = "--- " . $filename . " preview ---\n" . $snippet . (mb_strlen($content) > $maxPreviewBytes ? "\n...[truncated]..." : "") . "\n--- end preview ---";
                 }
             } catch (\Throwable $e) {
                 // Ignore preview errors
             }
         }
     }

     return implode("\n", $parts);
 }

 /**
  * Collect intro attachments as an array of files for the Gateway payload.
  * Each entry: ['filename' => ..., 'mimetype' => ..., 'size' => ..., 'content' => base64-string]
  */
 public static function collect_intro_files(int $intro_itemid, int $intro_attachments_itemid): array {
     global $USER;
     $filesOut = [];

     $readDraft = function(int $itemid) use ($USER) {
         if ($itemid <= 0) {
             return [];
         }
         $fs = get_file_storage();
         $userctx = \context_user::instance($USER->id);
         return $fs->get_area_files($userctx->id, 'user', 'draft', $itemid, 'sortorder, id', false) ?: [];
     };

     $readAssignmentFiles = function(int $cmid) {
         if ($cmid <= 0) {
             return [];
         }
         try {
             $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
             $context = \context_module::instance($cm->id);
             $fs = get_file_storage();
             
             // Get files from both intro and introattachment file areas
             $introFiles = $fs->get_area_files($context->id, 'mod_assign', 'intro', 0, 'sortorder, id', false) ?: [];
             $attachmentFiles = $fs->get_area_files($context->id, 'mod_assign', 'introattachment', 0, 'sortorder, id', false) ?: [];
             
             return array_merge($introFiles, $attachmentFiles);
         } catch (\Throwable $e) {
             return [];
         }
     };

     $all = [];
     
     // First try to get files from draft areas (for unsaved assignments)
     foreach ([$intro_itemid, $intro_attachments_itemid] as $did) {
         $all = array_merge($all, $readDraft($did));
     }
     
     if (empty($all)) {
         // Try to get cmid from current context or URL parameters
         $cmid = optional_param('cmid', 0, PARAM_INT);
         if ($cmid <= 0) {
             $cmid = optional_param('id', 0, PARAM_INT);
         }
         if ($cmid <= 0) {
             $cmid = optional_param('update', 0, PARAM_INT);
         }
         
         if ($cmid > 0) {
             $all = array_merge($all, $readAssignmentFiles($cmid));
         }
     }

     if (empty($all)) {
         return $filesOut;
     }

     foreach ($all as $file) {
         if ($file->is_directory()) {
             continue;
         }
         try {
             $content = $file->get_content();
             if ($content === false) {
                 continue;
             }
             // Always use base64 for safe JSON transport (PDF, images, etc.)
             $filesOut[] = [
                 'filename' => $file->get_filename(),
                 'mimetype' => $file->get_mimetype(),
                 'size' => (int)$file->get_filesize(),
                 'content' => base64_encode($content),
             ];
         } catch (\Throwable $e) {
             // Skip file on error
             continue;
         }
     }

     return $filesOut;
 }

 /********************************************************************
  * Grading Service Functions
  ********************************************************************/

 public static function save_grade_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'userid' => new \external_value(PARAM_INT, 'User ID'),
             'grade' => new \external_value(PARAM_RAW, 'Grade value (can be float or empty string)'),
     ]);
 }

 public static function save_grade_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'message' => new \external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
             'formatted_grade' => new \external_value(PARAM_RAW, 'Formatted grade value', VALUE_OPTIONAL),
     ]);
 }

 public static function save_grade($cmid, $userid, $grade) {
     self::validate_grading_context($cmid);
     $manager = new grading_manager($cmid);
     return $manager->save_grade($userid, $grade);
 }

 public static function save_bulk_grades_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'grades' => new \external_value(PARAM_RAW, 'JSON encoded grades object'),
     ]);
 }

 public static function save_bulk_grades_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'saved_count' => new \external_value(PARAM_INT, 'Number of grades saved'),
             'message' => new \external_value(PARAM_TEXT, 'Status message'),
     ]);
 }

 public static function save_bulk_grades($cmid, $grades) {
     self::validate_grading_context($cmid);
     $grades_array = json_decode($grades, true);
     if (!is_array($grades_array)) {
         throw new \moodle_exception('Invalid grades data');
     }
     $manager = new grading_manager($cmid);
     return $manager->save_bulk_grades($grades_array);
 }

 public static function clear_all_grades_parameters() {
     return new \external_function_parameters(['cmid' => new \external_value(PARAM_INT, 'Course module ID')]);
 }

 public static function clear_all_grades_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'message' => new \external_value(PARAM_TEXT, 'Status message'),
     ]);
 }

 public static function clear_all_grades($cmid) {
     self::validate_grading_context($cmid);
     $manager = new grading_manager($cmid);
     return $manager->clear_all_grades();
 }

 public static function get_current_grades_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'userids' => new \external_multiple_structure(new \external_value(PARAM_INT, 'User ID')),
     ]);
 }

 public static function get_current_grades_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'grades' => new \external_value(PARAM_RAW, 'JSON encoded grades object', VALUE_OPTIONAL),
     ]);
 }

 public static function get_current_grades($cmid, $userids) {
     self::validate_grading_context($cmid);
     $manager = new grading_manager($cmid);
     $grades = $manager->get_current_grades($userids);
     return ['success' => true, 'grades' => json_encode($grades)];
 }

 public static function auto_grade_by_quiz_parameters() {
     return new \external_function_parameters(['cmid' => new \external_value(PARAM_INT, 'Course module ID')]);
 }

 public static function auto_grade_by_quiz_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'graded_count' => new \external_value(PARAM_INT, 'Number of students graded', VALUE_OPTIONAL),
             'grades' => new \external_value(PARAM_RAW, 'JSON encoded grades object', VALUE_OPTIONAL),
             'message' => new \external_value(PARAM_TEXT, 'Status message'),
             'errors' => new \external_multiple_structure(new \external_value(PARAM_TEXT, 'Error message'), 'List of errors',
                     VALUE_OPTIONAL),
     ]);
 }

 public static function auto_grade_by_quiz($cmid) {
     self::validate_grading_context($cmid);
     $manager = new grading_manager($cmid);
     $result = $manager->auto_grade_by_quiz_score();
     if (isset($result['grades'])) {
         $result['grades'] = json_encode($result['grades']);
     }
     return $result;
 }

 /********************************************************************
  * Main TrustGrade Functions
  ********************************************************************/

 public static function check_instructions_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'instructions' => new \external_value(PARAM_RAW, 'Assignment instructions'),
             'intro_itemid' => new \external_value(PARAM_INT, 'Draft itemid for intro editor files', VALUE_DEFAULT, 0),
             'intro_attachments_itemid' => new \external_value(PARAM_INT, 'Draft itemid for intro attachments filemanager', VALUE_DEFAULT, 0),
     ]);
 }

 public static function check_instructions_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'recommendation' => new \external_value(PARAM_RAW, 'AI recommendation text', VALUE_OPTIONAL),
             'from_cache' => new \external_value(PARAM_BOOL, 'Whether the response was from cache', VALUE_OPTIONAL),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function check_instructions($cmid, $instructions, $intro_itemid = 0, $intro_attachments_itemid = 0) {
     self::validate_editing_context($cmid);
     $instructions = strip_tags(trim((string) $instructions));
     
     $files = self::collect_intro_files((int)$intro_itemid, (int)$intro_attachments_itemid);
     
     if (empty($instructions) && empty($files)) {
         return ['success' => false, 'error' => get_string('no_instructions_or_files', 'local_trustgrade')];
     }

     $response = api::check_instructions($instructions, $files);
     if (!empty($response['error'])) {
         return ['success' => false, 'error' => $response['error']];
     }

     return $response;
 }

 public static function generate_questions_parameters() {
      return new \external_function_parameters([
              'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
              'instructions' => new \external_value(PARAM_RAW, 'Assignment instructions'),
              'intro_itemid' => new \external_value(PARAM_INT, 'Draft itemid for intro editor files', VALUE_DEFAULT, 0),
              'intro_attachments_itemid' => new \external_value(PARAM_INT, 'Draft itemid for intro attachments filemanager', VALUE_DEFAULT, 0),
      ]);
  }

 public static function generate_questions_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'questions' => new \external_value(PARAM_RAW, 'JSON encoded array of questions', VALUE_OPTIONAL),
             'message' => new \external_value(PARAM_TEXT, 'Success message', VALUE_OPTIONAL),
             'from_cache' => new \external_value(PARAM_BOOL, 'Whether the response was from cache', VALUE_OPTIONAL),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function generate_questions($cmid, $instructions, $intro_itemid = 0, $intro_attachments_itemid = 0) {
    if ($cmid <= 0) {
        return [
            'success' => false, 
            'error' => get_string('save_assignment_first', 'local_trustgrade')
        ];
    }
    
    self::validate_editing_context($cmid);
    $instructions = strip_tags(trim((string) $instructions));
    
    $files = self::collect_intro_files((int)$intro_itemid, (int)$intro_attachments_itemid);
    
    if (empty($instructions) && empty($files)) {
        return ['success' => false, 'error' => get_string('no_instructions_or_files', 'local_trustgrade')];
    }

    $quiz_settings = quiz_settings::get_settings($cmid);
    $questions_to_generate = $quiz_settings['instructor_questions'];

    // If we have files, send them to the Gateway alongside the instructions.
    if (!empty($files)) {
        try {
            $gateway = new gateway_client();
            $gwResult = $gateway->generateQuestions($instructions, $questions_to_generate, $files);
            if (!$gwResult['success']) {
                return ['success' => false, 'error' => $gwResult['error'] ?? 'Gateway error'];
            }

            $data = $gwResult['data'] ?? [];
            $questions = $data['questions'] ?? [];

            if (empty($questions) || !is_array($questions)) {
                return ['success' => false, 'error' => get_string('error_saving_questions', 'local_trustgrade')];
            }

            $save_success = question_generator::save_questions($cmid, $questions);
            if ($save_success) {
                return [
                    'success' => true,
                    'message' => get_string('questions_generated_success', 'local_trustgrade'),
                    'questions' => json_encode($questions)
                ];
            } else {
                return ['success' => false, 'error' => get_string('error_saving_questions', 'local_trustgrade')];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Gateway error: ' . $e->getMessage()];
        }
    }

    // Fallback to existing flow when there are no files.
    $result = question_generator::generate_questions_with_count($instructions, $questions_to_generate);
    if ($result['success']) {
        $save_success = question_generator::save_questions($cmid, $result['questions']);
        if ($save_success) {
            $result['message'] = get_string('questions_generated_success', 'local_trustgrade');
            $result['questions'] = json_encode($result['questions']);
        } else {
            $result['error'] = get_string('error_saving_questions', 'local_trustgrade');
            $result['success'] = false;
        }
    } else {
        $result['error'] = $result['error'];
        $result['success'] = false;
    }
    return $result;
  }

 public static function save_question_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'question_index' => new \external_value(PARAM_INT, 'Index of the question to save'),
             'question_data' => new \external_value(PARAM_RAW, 'JSON encoded question data'),
     ]);
 }

 public static function save_question_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function save_question($cmid, $question_index, $question_data) {
     self::validate_editing_context($cmid);
     $question_data_decoded = json_decode($question_data, true);
     if (!$question_data_decoded) {
         return ['success' => false, 'error' => 'Invalid question data'];
     }
     return question_editor::save_question($cmid, $question_index, $question_data_decoded);
 }

 public static function delete_question_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'question_index' => new \external_value(PARAM_INT, 'Index of the question to delete'),
     ]);
 }

 public static function delete_question_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function delete_question($cmid, $question_index) {
     self::validate_editing_context($cmid);
     return question_editor::delete_question($cmid, $question_index);
 }

 public static function get_question_bank_parameters() {
     return new \external_function_parameters(['cmid' => new \external_value(PARAM_INT, 'Course module ID')]);
 }

 public static function get_question_bank_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'questions' => new \external_value(PARAM_RAW, 'JSON encoded array of questions', VALUE_OPTIONAL),
             'html' => new \external_value(PARAM_RAW, 'Rendered HTML of the question bank', VALUE_OPTIONAL),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function get_question_bank($cmid) {
     self::validate_editing_context($cmid);
     $existing_questions = question_generator::get_questions($cmid);
     if (!empty($existing_questions)) {
         $question_bank_html = question_bank_renderer::render_editable_questions($existing_questions, $cmid);
         return [
                 'success' => true,
                 'questions' => json_encode($existing_questions),
                 'html' => $question_bank_html
         ];
     } else {
         return ['success' => true, 'questions' => '[]', 'html' => ''];
     }
 }

 public static function update_quiz_setting_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'setting_name' => new \external_value(PARAM_TEXT, 'Name of the setting'),
             'setting_value' => new \external_value(PARAM_RAW, 'Value of the setting'),
     ]);
 }

 public static function update_quiz_setting_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function update_quiz_setting($cmid, $setting_name, $setting_value) {
     self::validate_editing_context($cmid);
     $allowed_settings = [
             'questions_to_generate', 'instructor_questions', 'submission_questions',
             'randomize_answers', 'time_per_question', 'show_countdown'
     ];
     if (!in_array($setting_name, $allowed_settings)) {
         return ['success' => false, 'error' => 'Invalid setting name provided.'];
     }
     $settings = quiz_settings::get_settings($cmid);
     $settings[$setting_name] = $setting_value;
     $result = quiz_settings::save_settings($cmid, $settings);
     if ($result) {
         return ['success' => true];
     } else {
         return ['success' => false, 'error' => 'Failed to update setting'];
     }
 }

 /********************************************************************
  * Quiz Session Functions
  ********************************************************************/

 public static function start_quiz_attempt_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'submissionid' => new \external_value(PARAM_INT, 'Submission ID'),
     ]);
 }

 public static function start_quiz_attempt_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function start_quiz_attempt($cmid, $submissionid) {
     global $USER;
     self::validate_submission_context($cmid);
     $success = quiz_session::update_session($cmid, $submissionid, $USER->id, ['attempt_started' => 1]);
     if ($success) {
         return ['success' => true];
     } else {
         return ['success' => false, 'error' => 'Failed to start attempt'];
     }
 }

 public static function update_quiz_session_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'submissionid' => new \external_value(PARAM_INT, 'Submission ID'),
             'updates' => new \external_value(PARAM_RAW, 'JSON encoded session updates'),
     ]);
 }

 public static function update_quiz_session_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function update_quiz_session($cmid, $submissionid, $updates) {
     global $USER;
     self::validate_submission_context($cmid);
     $updates_decoded = json_decode($updates, true);
     if (!$updates_decoded) {
         return ['success' => false, 'error' => 'Invalid updates data'];
     }
     $success = quiz_session::update_session($cmid, $submissionid, $USER->id, $updates_decoded);
     if ($success) {
         return ['success' => true];
     } else {
         return ['success' => false, 'error' => 'Failed to update session'];
     }
 }

 public static function complete_quiz_session_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'submissionid' => new \external_value(PARAM_INT, 'Submission ID'),
             'final_answers' => new \external_value(PARAM_RAW, 'JSON encoded final answers'),
             'final_score' => new \external_value(PARAM_INT, 'Final calculated score'),
     ]);
 }

 public static function complete_quiz_session_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'message' => new \external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function complete_quiz_session($cmid, $submissionid, $final_answers, $final_score) {
     global $USER;
     self::validate_submission_context($cmid);
     $final_answers_decoded = json_decode($final_answers, true);
     if (is_null($final_answers_decoded)) {
         return ['success' => false, 'error' => 'Invalid answers data'];
     }
     $success = quiz_session::complete_session($cmid, $submissionid, $USER->id, $final_answers_decoded, $final_score);
     if ($success) {
         return ['success' => true, 'message' => 'Quiz completed successfully'];
     } else {
         return ['success' => false, 'error' => 'Failed to complete session'];
     }
 }

 public static function log_integrity_violation_parameters() {
     return new \external_function_parameters([
             'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
             'submissionid' => new \external_value(PARAM_INT, 'Submission ID'),
             'violation_type' => new \external_value(PARAM_TEXT, 'Type of violation'),
             'violation_data' => new \external_value(PARAM_RAW, 'JSON encoded violation data', VALUE_OPTIONAL),
     ]);
 }

 public static function log_integrity_violation_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function log_integrity_violation($cmid, $submissionid, $violation_type, $violation_data = '{}') {
     global $USER;
     self::validate_submission_context($cmid);
     $violation_data_decoded = json_decode($violation_data, true) ?: [];
     $success = quiz_session::log_integrity_violation($cmid, $submissionid, $USER->id, $violation_type, $violation_data_decoded);
     if ($success) {
         return ['success' => true];
     } else {
         return ['success' => false, 'error' => 'Failed to log violation'];
     }
 }

 public static function get_pending_tasks_parameters() {
     return new \external_function_parameters([]);
 }

 public static function get_pending_tasks_returns() {
     return new \external_single_structure([
             'success' => new \external_value(PARAM_BOOL, 'True if successful'),
             'tasks' => new \external_value(PARAM_RAW, 'JSON encoded array of pending tasks', VALUE_OPTIONAL),
             'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
         ]);
     }

 public static function get_pending_tasks() {
     global $USER;
     $context = \context_system::instance();
     self::validate_context($context);
     
     try {
         $tasks = async_task_manager::get_user_pending_tasks();
         return [
             'success' => true,
             'tasks' => json_encode($tasks)
         ];
     } catch (\Exception $e) {
         return [
             'success' => false,
             'error' => $e->getMessage()
         ];
     }
 }

 public static function has_pending_tasks_parameters() {
     return new \external_function_parameters([]);
 }
 
 public static function has_pending_tasks_returns() {
     return new \external_single_structure([
         'success' => new \external_value(PARAM_BOOL, 'True if successful'),
         'has_tasks' => new \external_value(PARAM_BOOL, 'True if user has pending tasks'),
         'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }
 
 public static function has_pending_tasks() {
     global $USER, $DB;
     $context = \context_system::instance();
     self::validate_context($context);
     
     try {
         $now = time();
         $twentyfour_hours_ago = $now - (24 * 60 * 60);
         
         // Check for pending/processing/failed tasks
         $has_tasks = $DB->record_exists_select('local_trustgd_async_tasks',
             'userid = ? AND status IN (?, ?, ?) AND timecreated > ?',
             [$USER->id, 'pending', 'processing', 'failed', $twentyfour_hours_ago]
         );
         
         // If no pending tasks, check for incomplete quiz sessions
         if (!$has_tasks) {
             $has_tasks = $DB->record_exists_select('local_trustgd_quiz_sessions',
                 'userid = ? AND attempt_completed = 0 AND timecreated > ?',
                 [$USER->id, $twentyfour_hours_ago]
             );
         }
         
         return [
             'success' => true,
             'has_tasks' => $has_tasks
         ];
     } catch (\Exception $e) {
         return [
             'success' => false,
             'has_tasks' => false,
             'error' => $e->getMessage()
         ];
     }
 }

 public static function get_instructor_generation_status_parameters() {
     return new \external_function_parameters([
         'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
     ]);
 }

 public static function get_instructor_generation_status_returns() {
     return new \external_single_structure([
         'has_task' => new \external_value(PARAM_BOOL, 'Whether there is a task for this cmid'),
         'status' => new \external_value(PARAM_ALPHA, 'pending, processing, ready, or failed'),
         'message' => new \external_value(PARAM_TEXT, 'Display message'),
         'error' => new \external_value(PARAM_TEXT, 'Error message if failed', VALUE_OPTIONAL),
     ]);
 }

 public static function get_instructor_generation_status($cmid) {
     self::validate_parameters(self::get_instructor_generation_status_parameters(), ['cmid' => $cmid]);
     self::validate_editing_context($cmid);
     return instructor_generation_manager::get_status_for_cmid($cmid);
 }

 public static function get_quiz_grades_for_grading_parameters() {
     return new \external_function_parameters([
         'cmid' => new \external_value(PARAM_INT, 'Course module ID (assignment)'),
     ]);
 }

 public static function get_quiz_grades_for_grading_returns() {
     return new \external_single_structure([
         'success' => new \external_value(PARAM_BOOL, 'True if successful'),
         'grades' => new \external_value(PARAM_RAW, 'Quiz grades data (JSON encoded)'),
         'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function get_quiz_grades_for_grading($cmid) {
     global $DB;

     $params = self::validate_parameters(self::get_quiz_grades_for_grading_parameters(), [
         'cmid' => $cmid,
     ]);

     try {
         // Check if plugin is globally enabled
         if (!get_config('local_trustgrade', 'plugin_enabled')) {
             return [
                 'success' => false,
                 'grades' => '{}',
                 'error' => 'TrustGrade plugin is not enabled'
             ];
         }

         $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
         $context = \context_module::instance($cm->id);
         self::validate_context($context);
         require_capability('mod/assign:grade', $context);

         // Check if TrustGrade is enabled for this assignment
         $settings = \local_trustgrade\quiz_settings::get_settings($params['cmid']);
         if (empty($settings['enabled'])) {
             return [
                 'success' => false,
                 'grades' => '{}',
                 'error' => 'TrustGrade is not enabled for this assignment'
             ];
         }

         // Get all completed quiz sessions for this assignment using the same method as quiz_report
         $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_assignment($params['cmid']);

         $grades = [];
         foreach ($sessions as $session) {
             // Only keep the latest session per user
             if (!isset($grades[$session->userid])) {
                 // Calculate total points from questions (same as report_renderer)
                 $questions = (array) $session->questions_data;
                 $total_points = 0;
                 foreach ($questions as $question) {
                     $total_points += isset($question->points) ? $question->points : 10;
                 }
                 
                 // final_score is the earned points
                 $earned_points = $session->final_score;
                 $percentage = $total_points > 0 ? round(($earned_points / $total_points) * 100) : 0;
                 
                 // Calculate duration
                 $completed_date = $session->timecompleted ?: $session->timemodified;
                 $duration = $completed_date - $session->timecreated;
                 
                 $grades[$session->userid] = [
                     'userid' => (int)$session->userid,
                     'earned_points' => (int)$earned_points,
                     'total_points' => (int)$total_points,
                     'percentage' => (int)$percentage,
                     'duration' => (int)$duration,
                     'completed' => (int)$completed_date
                 ];
             }
         }

         return [
             'success' => true,
             'grades' => json_encode($grades)
         ];
     } catch (\Exception $e) {
         return [
             'success' => false,
             'grades' => '{}',
             'error' => $e->getMessage()
         ];
     }
 }

 public static function toggle_mandatory_question_parameters() {
     return new \external_function_parameters([
         'questionid' => new \external_value(PARAM_INT, 'Question ID'),
         'is_mandatory' => new \external_value(PARAM_BOOL, 'New mandatory status'),
     ]);
 }

 public static function toggle_mandatory_question_returns() {
     return new \external_single_structure([
         'success' => new \external_value(PARAM_BOOL, 'True if successful'),
         'is_mandatory' => new \external_value(PARAM_BOOL, 'New mandatory status'),
         'error' => new \external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
     ]);
 }

 public static function toggle_mandatory_question($questionid, $is_mandatory) {
     global $DB;

     $params = self::validate_parameters(self::toggle_mandatory_question_parameters(), [
         'questionid' => $questionid,
         'is_mandatory' => $is_mandatory,
     ]);

     // Get question to verify access
     $question = $DB->get_record('local_trustgrade_questions', ['id' => $params['questionid']], '*', MUST_EXIST);
     
     // Verify context and permissions
     $cm = get_coursemodule_from_id('assign', $question->cmid, 0, false, MUST_EXIST);
     $context = \context_module::instance($cm->id);
     self::validate_context($context);
     require_capability('mod/assign:addinstance', $context);

     // Update mandatory status
     $question->is_mandatory = $params['is_mandatory'] ? 1 : 0;
     $question->timemodified = time();
     
     $success = $DB->update_record('local_trustgrade_questions', $question);

     if ($success) {
         return [
             'success' => true,
             'is_mandatory' => (bool)$question->is_mandatory,
         ];
     } else {
         return [
             'success' => false,
             'is_mandatory' => (bool)$question->is_mandatory,
             'error' => get_string('error_updating_question', 'local_trustgrade'),
         ];
     }
 }
}
