<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for TrustGrade plugin
 */
class observer {

    /**
     * Handle submission created event
     *
     * @param \mod_assign\event\submission_created $event
     */
    public static function submission_created(\mod_assign\event\submission_created $event) {
        self::process_submission($event);
    }

    /**
     * Handle submission updated event
     *
     * @param \mod_assign\event\submission_updated $event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event) {
        self::process_submission($event);
    }

    /**
     * Handle assessable submitted event
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assessable_submitted(\mod_assign\event\assessable_submitted $event) {
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

            if (!$cm) {
                return;
            }

            // Get submission data
            $submission = $DB->get_record('assign_submission', ['id' => $submission_id]);
            if (!$submission) {
                return;
            }

            // Only process submitted submissions (not drafts)
            if ($event->other['submissionstatus'] !== 'submitted') {
                return;
            }

            // Get assignment data
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                return;
            }

            // Get quiz settings to determine how many submission questions to generate
            $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cm->id);
            $questions_to_generate = $quiz_settings['submission_questions'];

            // Extract submission content (text and files)
            $submission_content = submission_processor::extract_submission_content($submission, $context);

            if (empty($submission_content['text']) && empty($submission_content['files'])) {
                return; // No content to analyze
            }

            $assignment_instructions = self::extract_assignment_instructions($assignment, $context);

            // Generate questions based on submission using the configured count
            $result = submission_processor::generate_submission_questions_with_count(
                $submission_content,
                $assignment_instructions,
                $questions_to_generate,
                $cm->id,
                $submission->userid
            );

            if ($result['success']) {
                // Save submission-based questions
                submission_processor::save_submission_questions($submission_id, $cm->id, $result['questions']);

                self::create_quiz_session_for_submission($cm->id, $submission_id, $submission->userid);

                // Set session flag to redirect to quiz
                self::set_quiz_redirect_flag($cm->id, $submission_id);
            }

        } catch (\Exception $e) {
            // Log error but don't break the submission process
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

            // Get course module
            $cm = get_coursemodule_from_id('assign', $assignment_id);
            if (!$cm) {
                return;
            }

            // Get submission data
            $submission = $DB->get_record('assign_submission', ['id' => $submission_id]);
            if (!$submission) {
                return;
            }

            // Get assignment data
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                return;
            }

            // Get quiz settings to determine how many submission questions to generate
            $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cm->id);
            $questions_to_generate = $quiz_settings['submission_questions'];

            // Extract submission content (text and files)
            $submission_content = submission_processor::extract_submission_content($submission, $context);

            if (empty($submission_content['text']) && empty($submission_content['files'])) {
                return; // No content to analyze
            }

            $assignment_instructions = self::extract_assignment_instructions($assignment, $context);

            // Generate questions based on submission using the configured count
            $result = submission_processor::generate_submission_questions_with_count(
                $submission_content,
                $assignment_instructions,
                $questions_to_generate,
                $cm->id,
                $user_id
            );

            if ($result['success']) {
                // Save submission-based questions
                submission_processor::save_submission_questions($submission_id, $cm->id, $result['questions']);

                self::create_quiz_session_for_submission($cm->id, $submission_id, $user_id);

                // Set session flag to redirect to quiz
                self::set_quiz_redirect_flag($cm->id, $submission_id);
            }

        } catch (\Exception $e) {
            // Log error but don't break the submission process
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
        quiz_session::create_session_on_submission_update($cmid, $submission_id, $userid);
    }

    /**
     * Set flag in session to redirect user to quiz
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     */
    private static function set_quiz_redirect_flag($cmid, $submission_id) {
        global $SESSION;

        if (!isset($SESSION->trustgrade_quiz_redirect)) {
            $SESSION->trustgrade_quiz_redirect = [];
        }

        $SESSION->trustgrade_quiz_redirect[$cmid] = [
            'submission_id' => $submission_id,
            'timestamp' => time()
        ];
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

        $assignment_context = \context_module::instance($context->instanceid);
        
        // Get files attached to assignment intro
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $assignment_context->id,
            'mod_assign',
            'intro',
            0, // itemid is 0 for intro files
            'timemodified',
            false
        );

        error_log('TrustGrade: Looking for intro files in context ' . $assignment_context->id . ', found ' . count($files) . ' files');

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $file_content = $file->get_content();
            if (!empty($file_content)) {
                error_log('TrustGrade: Found intro file: ' . $file->get_filename() . ' (' . $file->get_filesize() . ' bytes)');
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
