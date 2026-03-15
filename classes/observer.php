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
 * Event observer for TrustGrade plugin assignment submission events.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for TrustGrade plugin
 */
class observer {

    /**
     * Check if submission has already been processed recently to prevent duplicate processing
     *
     * @param int $submission_id Submission ID
     * @param string $event_name Event name for logging
     * @return bool True if already processed, false otherwise
     */
    private static function is_already_processed($submission_id, $event_name) {
        $cache = \cache::make('local_trustgrade', 'quiz_redirect');
        $key = 'processed_' . $submission_id;
        
        $processed_data = $cache->get($key);
        
        if ($processed_data && (time() - $processed_data['timestamp']) < 60) {
            debugging('TrustGrade observer: Duplicate ' . $event_name . ' event detected for submission ID ' . 
                $submission_id . ', skipping processing (already processed ' . 
                (time() - $processed_data['timestamp']) . ' seconds ago by ' . 
                $processed_data['event'] . ')', DEBUG_DEVELOPER);
            return true;
        }
        
        // Mark as processed
        $cache->set($key, [
            'timestamp' => time(),
            'event' => $event_name
        ]);
        
        return false;
    }

    /**
     * Handle submission created event
     *
     * @param \mod_assign\event\submission_created $event
     */
    public static function submission_created(\mod_assign\event\submission_created $event) {
        $submission_id = $event->other['submissionid'];
        debugging('TrustGrade observer: submission_created event triggered for submission ID ' . $submission_id, DEBUG_DEVELOPER);
        
        if (self::is_already_processed($submission_id, 'submission_created')) {
            return;
        }
        
        self::process_submission($event);
    }

    /**
     * Handle submission updated event
     *
     * @param \mod_assign\event\submission_updated $event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event) {
        $submission_id = $event->other['submissionid'];
        debugging('TrustGrade observer: submission_updated event triggered for submission ID ' . $submission_id, DEBUG_DEVELOPER);
        
        if (self::is_already_processed($submission_id, 'submission_updated')) {
            return;
        }
        
        self::process_submission($event);
    }

    /**
     * Handle assessable submitted event.
     * This fires when the student clicks "Submit assignment" and then "Continue" on the confirmation page
     * (or when submission is submitted for grading). Creates the quiz generation task.
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assessable_submitted(\mod_assign\event\assessable_submitted $event) {
        global $DB;

        $eventdata = $event->get_data();
        $submission_id = $eventdata['objectid'];
        debugging('TrustGrade observer: assessable_submitted event triggered for submission ID ' . $submission_id, DEBUG_DEVELOPER);

        if (self::is_already_processed($submission_id, 'assessable_submitted')) {
            return;
        }

        try {
            $assign = $event->get_assign();
            // Use submission owner (relateduserid when teacher submits for student, else userid).
            $userid = !empty($eventdata['relateduserid']) ? $eventdata['relateduserid'] : $eventdata['userid'];
            if (empty($userid)) {
                $sub = $DB->get_record('assign_submission', ['id' => $submission_id], 'userid');
                $userid = $sub ? $sub->userid : $eventdata['userid'];
            }
            self::process_submission_submitted($assign, $submission_id, $userid);
        } catch (\Throwable $e) {
            debugging('TrustGrade observer: assessable_submitted failed - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade assessable_submitted error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Handle submission status updated event (e.g. when status becomes 'submitted').
     * Ensures quiz is created when "Require students to click the submit button" (submissiondrafts) is enabled.
     *
     * @param \mod_assign\event\submission_status_updated $event
     */
    public static function submission_status_updated(\mod_assign\event\submission_status_updated $event) {
        global $DB;

        $eventdata = $event->get_data();
        $newstatus = isset($eventdata['other']['newstatus']) ? $eventdata['other']['newstatus'] : '';
        if ($newstatus !== 'submitted') {
            return;
        }
        $submission_id = $eventdata['objectid'];
        debugging('TrustGrade observer: submission_status_updated (submitted) for submission ID ' . $submission_id, DEBUG_DEVELOPER);

        if (self::is_already_processed($submission_id, 'submission_status_updated')) {
            return;
        }

        try {
            $assign = $event->get_assign();
            $userid = !empty($eventdata['relateduserid']) ? $eventdata['relateduserid'] : $eventdata['userid'];
            if (empty($userid)) {
                $sub = $DB->get_record('assign_submission', ['id' => $submission_id], 'userid');
                $userid = $sub ? $sub->userid : $eventdata['userid'];
            }
            self::process_submission_submitted($assign, $submission_id, $userid);
        } catch (\Exception $e) {
            debugging('TrustGrade observer: submission_status_updated processing - ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Handle course module created event (for duplicating settings)
     *
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;
        
        try {
            $eventdata = $event->get_data();
            $new_cmid = $eventdata['objectid'];
            
            // Only process assignment modules
            if ($eventdata['other']['modulename'] !== 'assign') {
                return;
            }
            
            debugging('TrustGrade observer: course_module_created event triggered for CM ID ' . $new_cmid, DEBUG_DEVELOPER);
            
            // Check if this is a duplicate by looking for the originalcmid in the event's other data
            // When duplicating, Moodle stores the original CM ID in the context
            $context = $event->get_context();
            
            // Try to find the original course module ID from the session or request
            // In Moodle's duplicate process, we need to look at the course backup/restore data
            $course_id = $eventdata['courseid'];
            
            // Get all assignment CMs in the course ordered by time created (descending)
            $cms = $DB->get_records('course_modules', 
                ['course' => $course_id, 'module' => $eventdata['other']['instanceid']], 
                'added DESC', 
                'id, instance, added', 
                0, 
                10
            );
            
            if (count($cms) < 2) {
                // No source module to copy from
                debugging('TrustGrade observer: No source module found for duplication', DEBUG_DEVELOPER);
                return;
            }
            
            // The new module is the first one (most recent), try to find a source with settings
            $source_cmid = null;
            $cms_array = array_values($cms);
            
            // Skip the first one (new module) and find the first one with TrustGrade settings
            for ($i = 1; $i < count($cms_array); $i++) {
                $potential_source = $cms_array[$i]->id;
                if ($DB->record_exists('local_trustgd_quiz_settings', ['cmid' => $potential_source])) {
                    $source_cmid = $potential_source;
                    debugging('TrustGrade observer: Found source CM ID ' . $source_cmid . ' with settings', DEBUG_DEVELOPER);
                    break;
                }
            }
            
            if (!$source_cmid) {
                debugging('TrustGrade observer: No source module with TrustGrade settings found', DEBUG_DEVELOPER);
                return;
            }
            
            // Copy settings from source to new module
            self::copy_quiz_settings($source_cmid, $new_cmid);
            
        } catch (\Exception $e) {
            debugging('TrustGrade observer: Exception during course_module_created processing - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade course_module_created error: ' . $e->getMessage());
        }
    }

    /**
     * Copy quiz settings from one course module to another
     *
     * @param int $source_cmid Source course module ID
     * @param int $target_cmid Target course module ID
     */
    private static function copy_quiz_settings($source_cmid, $target_cmid) {
        global $DB;
        
        try {
            $source_settings = $DB->get_record('local_trustgd_quiz_settings', ['cmid' => $source_cmid]);
            
            if (!$source_settings) {
                debugging('TrustGrade observer: No settings found for source CM ID ' . $source_cmid, DEBUG_DEVELOPER);
                return;
            }
            
            // Check if target already has settings
            if ($DB->record_exists('local_trustgd_quiz_settings', ['cmid' => $target_cmid])) {
                debugging('TrustGrade observer: Target CM ID ' . $target_cmid . ' already has settings, skipping copy', DEBUG_DEVELOPER);
                return;
            }
            
            // Create new settings record for target
            $new_settings = new \stdClass();
            $new_settings->cmid = $target_cmid;
            $new_settings->enabled = $source_settings->enabled;
            $new_settings->questions_to_generate = $source_settings->questions_to_generate;
            $new_settings->instructor_questions = $source_settings->instructor_questions;
            $new_settings->submission_questions = $source_settings->submission_questions;
            $new_settings->randomize_answers = $source_settings->randomize_answers;
            $new_settings->time_per_question = $source_settings->time_per_question;
            $new_settings->show_countdown = $source_settings->show_countdown;
            $new_settings->timecreated = time();
            $new_settings->timemodified = time();
            
            $DB->insert_record('local_trustgd_quiz_settings', $new_settings);
            
            debugging('TrustGrade observer: Successfully copied settings from CM ID ' . $source_cmid . ' to CM ID ' . $target_cmid, DEBUG_DEVELOPER);
            
        } catch (\Exception $e) {
            debugging('TrustGrade observer: Error copying quiz settings - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade copy_quiz_settings error: ' . $e->getMessage());
        }
    }

    /**
     * Process submission and generate AI questions
     *
     * @param \core\event\base $event
     */
    private static function process_submission(\core\event\base $event) {
        global $DB;

        try {
            // Get event data
            $submission_id = $event->other['submissionid'];
            $context = $event->get_context();
            $cm = get_coursemodule_from_id('assign', $context->instanceid);

            debugging('TrustGrade observer: Processing submission ID ' . $submission_id . 
                ' for CM ID ' . ($cm ? $cm->id : 'unknown'), DEBUG_DEVELOPER);

            if (!$cm) {
                debugging('TrustGrade observer: Course module not found, skipping processing', DEBUG_DEVELOPER);
                return;
            }

            $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cm->id);
            if (empty($quiz_settings['enabled'])) {
                debugging('TrustGrade observer: TrustGrade disabled for CM ID ' . $cm->id . ', skipping processing', DEBUG_DEVELOPER);
                return; // TrustGrade is disabled for this activity, skip processing
            }

            // Get submission data
            $submission = $DB->get_record('assign_submission', ['id' => $submission_id]);
            if (!$submission) {
                debugging('TrustGrade observer: Submission record not found for ID ' . $submission_id, DEBUG_DEVELOPER);
                return;
            }

            // Get assignment data (needed for submissiondrafts check)
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                debugging('TrustGrade observer: Assignment record not found for ID ' . $submission->assignment, DEBUG_DEVELOPER);
                return;
            }

            // When "Require students to click the submit button" (submissiondrafts) is enabled,
            // create the quiz only after they click "Continue" on the confirmation page (assessable_submitted),
            // not from submission_updated (which may fire with draft or in wrong order).
            if (!empty($assignment->submissiondrafts)) {
                debugging('TrustGrade observer: submissiondrafts enabled – skipping submission_updated (quiz created only after confirmation page)', DEBUG_DEVELOPER);
                return;
            }

            // Only process submitted submissions (not drafts)
            if ($event->other['submissionstatus'] !== 'submitted') {
                debugging('TrustGrade observer: Submission status is "' . $event->other['submissionstatus'] . 
                    '", skipping processing (only "submitted" status is processed)', DEBUG_DEVELOPER);
                return;
            }

            // Get quiz settings to determine how many submission questions to generate
            $questions_to_generate = $quiz_settings['submission_questions'];
            debugging('TrustGrade observer: Generating ' . $questions_to_generate . ' questions for submission', DEBUG_DEVELOPER);

            // Extract submission content (text and files)
            $submission_content = submission_processor::extract_submission_content($submission, $context);

            if (empty($submission_content['text']) && empty($submission_content['files'])) {
                debugging('TrustGrade observer: No content to analyze (no text or files)', DEBUG_DEVELOPER);
                return; // No content to analyze
            }

            $assignment_instructions = self::extract_assignment_instructions($assignment, $context);

            debugging('TrustGrade observer: Creating async task for question generation', DEBUG_DEVELOPER);
            
            async_task_manager::create_task(
                $cm->id,
                $submission_id,
                $submission->userid,
                $submission_content,
                $assignment_instructions,
                $questions_to_generate
            );
            
            debugging('TrustGrade observer: Async task queued successfully', DEBUG_DEVELOPER);

        } catch (\Exception $e) {
            // Log error but don't break the submission process
            debugging('TrustGrade observer: Exception during submission processing - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade submission processing error: ' . $e->getMessage());
        }
    }

    /**
     * Process a submission that has just been submitted (create quiz generation task).
     * Shared by assessable_submitted and submission_status_updated when status is 'submitted'.
     * Uses the assign instance from the event so cm/context are always correct (works with submissiondrafts).
     *
     * @param \assign $assign The assign instance from the event
     * @param int $submission_id Submission ID
     * @param int $userid User ID (owner of the submission)
     */
    private static function process_submission_submitted($assign, $submission_id, $userid) {
        global $DB;

        try {
            $cm = $assign->get_course_module();
            $context = $assign->get_context();

            debugging('TrustGrade observer: Processing submitted submission ID ' . $submission_id .
                ' for CM ID ' . $cm->id, DEBUG_DEVELOPER);

            $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cm->id);
            if (empty($quiz_settings['enabled'])) {
                debugging('TrustGrade observer: TrustGrade disabled for CM ID ' . $cm->id . ', skipping processing', DEBUG_DEVELOPER);
                return;
            }

            $submission = $DB->get_record('assign_submission', ['id' => $submission_id]);
            if (!$submission) {
                debugging('TrustGrade observer: Submission record not found for ID ' . $submission_id, DEBUG_DEVELOPER);
                return;
            }

            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                debugging('TrustGrade observer: Assignment record not found for ID ' . $submission->assignment, DEBUG_DEVELOPER);
                return;
            }

            $questions_to_generate = $quiz_settings['submission_questions'];
            debugging('TrustGrade observer: Generating ' . $questions_to_generate . ' questions for submitted submission', DEBUG_DEVELOPER);

            $submission_content = submission_processor::extract_submission_content($submission, $context);
            if (empty($submission_content['text']) && empty($submission_content['files'])) {
                debugging('TrustGrade observer: No content to analyze (no text or files)', DEBUG_DEVELOPER);
                return;
            }

            $assignment_instructions = self::extract_assignment_instructions($assignment, $context);

            debugging('TrustGrade observer: Creating async task for question generation', DEBUG_DEVELOPER);
            async_task_manager::create_task(
                $cm->id,
                $submission_id,
                $userid,
                $submission_content,
                $assignment_instructions,
                $questions_to_generate
            );
            debugging('TrustGrade observer: Async task queued successfully', DEBUG_DEVELOPER);

        } catch (\Exception $e) {
            debugging('TrustGrade observer: Exception during submission_submitted processing - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade submission_submitted processing error: ' . $e->getMessage());
        }
    }

    /**
     * Create quiz session specifically for submission update
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     * @param int $userid User ID
     */
    private static function create_quiz_session_for_submission($cmid, $submission_id, $userid) {
        debugging('TrustGrade observer: Creating quiz session for CM ID ' . $cmid . 
            ', submission ID ' . $submission_id . ', user ID ' . $userid, DEBUG_DEVELOPER);
        quiz_session::create_session_on_submission_update($cmid, $submission_id, $userid);
    }

    /**
     * Set flag in cache to redirect user to quiz
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     */
    private static function set_quiz_redirect_flag($cmid, $submission_id) {
        debugging('TrustGrade observer: Setting quiz redirect flag for CM ID ' . $cmid . 
            ', submission ID ' . $submission_id, DEBUG_DEVELOPER);
        
        $cache = \cache::make('local_trustgrade', 'quiz_redirect');
        
        $cache->set($cmid, [
            'submission_id' => $submission_id,
            'timestamp' => time()
        ]);
    }

    /**
     * Extract assignment instructions including text and attached files
     *
     * @param object $assignment Assignment object
     * @param \context $context Assignment context
     * @return array Instructions content including text and files
     */
    private static function extract_assignment_instructions($assignment, $context) {
        $instructions = [
            'text' => strip_tags($assignment->intro ?? ''),
            'files' => []
        ];

        $fs = get_file_storage();
        
        // Get files from intro area
        $intro_files = $fs->get_area_files(
            $context->id,
            'mod_assign',
            'intro',
            0, // itemid is 0 for intro files
            'timemodified',
            false
        );
        
        // Get files from introattachment area
        $attachment_files = $fs->get_area_files(
            $context->id,
            'mod_assign',
            'introattachment',
            0, // itemid is 0 for intro attachment files
            'timemodified',
            false
        );
        
        // Combine both file arrays
        $all_files = array_merge($intro_files ?: [], $attachment_files ?: []);

        foreach ($all_files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $file_content = $file->get_content();
            if (!empty($file_content)) {
                $instructions['files'][] = [
                    'filename' => $file->get_filename(),
                    'mimetype' => $file->get_mimetype(),
                    'size' => $file->get_filesize(),
                    'content' => base64_encode($file_content)
                ];
            }
        }

        return $instructions;
    }
}
