<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task for processing assignment submissions
 */
class process_submission_adhoc extends \core\task\adhoc_task {

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        
        if (!$data || !isset($data->submission_id)) {
            return;
        }

        try {
            // Get submission data
            $submission = $DB->get_record('assign_submission', ['id' => $data->submission_id]);
            if (!$submission) {
                return;
            }

            // Get assignment data
            $assignment = $DB->get_record('assign', ['id' => $submission->assignment]);
            if (!$assignment) {
                return;
            }

            // Get course module
            $cm = get_coursemodule_from_instance('assign', $assignment->id);
            if (!$cm) {
                return;
            }

            $context = \context_module::instance($cm->id);

            // Get quiz settings to determine how many submission questions to generate
            $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cm->id);
            $questions_to_generate = $quiz_settings['submission_questions'];

            // Extract submission content (text and files)
            $submission_content = \local_trustgrade\submission_processor::extract_submission_content($submission, $context);

            if (empty($submission_content['text']) && empty($submission_content['files'])) {
                return; // No content to analyze
            }

            $assignment_instructions = strip_tags($assignment->intro ?? '');

            // Generate questions based on submission using the configured count
            $result = \local_trustgrade\submission_processor::generate_submission_questions_with_count(
                $submission_content,
                $assignment_instructions,
                $questions_to_generate
            );

            if ($result['success']) {
                // Save submission-based questions
                \local_trustgrade\submission_processor::save_submission_questions($data->submission_id, $cm->id, $result['questions']);

                $this->create_quiz_session_for_submission($cm->id, $data->submission_id, $submission->userid);

                // Mark task as completed in database
                $this->mark_task_completed($data->submission_id, $cm->id);
            }

        } catch (\Exception $e) {
            // Log error and mark task as failed
            error_log('TrustGrade adhoc submission processing error: ' . $e->getMessage());
            $this->mark_task_failed($data->submission_id, $e->getMessage());
        }
    }

    /**
     * Create quiz session specifically for submission update
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     * @param int $userid User ID
     */
    private function create_quiz_session_for_submission($cmid, $submission_id, $userid) {
        \local_trustgrade\quiz_session::create_session_on_submission_update($cmid, $submission_id, $userid);
    }

    /**
     * Mark task as completed
     *
     * @param int $submission_id
     * @param int $cmid
     */
    private function mark_task_completed($submission_id, $cmid) {
        global $DB;

        $record = new \stdClass();
        $record->submission_id = $submission_id;
        $record->cmid = $cmid;
        $record->status = 'completed';
        $record->completed_at = time();

        // Check if record exists
        $existing = $DB->get_record('local_trustgrade_task_status', [
            'submission_id' => $submission_id,
            'cmid' => $cmid
        ]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_trustgrade_task_status', $record);
        } else {
            $DB->insert_record('local_trustgrade_task_status', $record);
        }
    }

    /**
     * Mark task as failed
     *
     * @param int $submission_id
     * @param string $error_message
     */
    private function mark_task_failed($submission_id, $error_message) {
        global $DB;

        $record = new \stdClass();
        $record->submission_id = $submission_id;
        $record->status = 'failed';
        $record->error_message = $error_message;
        $record->completed_at = time();

        $existing = $DB->get_record('local_trustgrade_task_status', ['submission_id' => $submission_id]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_trustgrade_task_status', $record);
        } else {
            $DB->insert_record('local_trustgrade_task_status', $record);
        }
    }
}
