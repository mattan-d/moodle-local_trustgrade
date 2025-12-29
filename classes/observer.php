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
     * Handle assessable submitted event
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assessable_submitted(\mod_assign\event\assessable_submitted $event) {
        $eventdata = $event->get_data();
        $submission_id = $eventdata['objectid'];
        debugging('TrustGrade observer: assessable_submitted event triggered for submission ID ' . $submission_id, DEBUG_DEVELOPER);
        
        if (self::is_already_processed($submission_id, 'assessable_submitted')) {
            return;
        }
        
        self::process_assessable_submission($event);
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

            // Only process submitted submissions (not drafts)
            if ($event->other['submissionstatus'] !== 'submitted') {
                debugging('TrustGrade observer: Submission status is "' . $event->other['submissionstatus'] . 
                    '", skipping processing (only "submitted" status is processed)', DEBUG_DEVELOPER);
                return;
            }

            // Get assignment data
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                debugging('TrustGrade observer: Assignment record not found for ID ' . $submission->assignment, DEBUG_DEVELOPER);
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
     * Process assessable submission and generate AI questions
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    private static function process_assessable_submission(\mod_assign\event\assessable_submitted $event) {
        global $DB;

        try {
            $eventdata = $event->get_data();
            
            // Map event data fields correctly for assessable_submitted
            $submission_id = $eventdata['objectid']; // objectid is the submission ID
            $assignment_id = $eventdata['contextinstanceid']; // contextinstanceid is the assignment ID
            $user_id = $eventdata['userid'];
            $context = $event->get_context();

            debugging('TrustGrade observer: Processing assessable submission ID ' . $submission_id . 
                ' for assignment ID ' . $assignment_id, DEBUG_DEVELOPER);

            // Get course module
            $cm = get_coursemodule_from_id('assign', $assignment_id);
            if (!$cm) {
                debugging('TrustGrade observer: Course module not found for assignment ID ' . $assignment_id, DEBUG_DEVELOPER);
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

            // Get assignment data
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                debugging('TrustGrade observer: Assignment record not found for ID ' . $submission->assignment, DEBUG_DEVELOPER);
                return;
            }

            // Get quiz settings to determine how many submission questions to generate
            $questions_to_generate = $quiz_settings['submission_questions'];
            debugging('TrustGrade observer: Generating ' . $questions_to_generate . ' questions for assessable submission', DEBUG_DEVELOPER);

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
                $user_id,
                $submission_content,
                $assignment_instructions,
                $questions_to_generate
            );
            
            debugging('TrustGrade observer: Async task queued successfully', DEBUG_DEVELOPER);

        } catch (\Exception $e) {
            // Log error but don't break the submission process
            debugging('TrustGrade observer: Exception during assessable submission processing - ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log('TrustGrade assessable submission processing error: ' . $e->getMessage());
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
