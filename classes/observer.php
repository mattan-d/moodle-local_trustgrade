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
     * Process submission and generate AI questions via adhoc task
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

            self::queue_submission_processing_task($submission_id, $cm->id, $submission->userid);

        } catch (\Exception $e) {
            // Log error but don't break the submission process
            error_log('TrustGrade submission task queuing error: ' . $e->getMessage());
        }
    }

    /**
     * Queue adhoc task for submission processing
     *
     * @param int $submission_id
     * @param int $cmid
     * @param int $userid
     */
    private static function queue_submission_processing_task($submission_id, $cmid, $userid) {
        global $DB;

        $status_record = new \stdClass();
        $status_record->submission_id = $submission_id;
        $status_record->cmid = $cmid;
        $status_record->userid = $userid;
        $status_record->status = 'queued';
        $status_record->created_at = time();

        $DB->insert_record('local_trustgrade_task_status', $status_record);

        $task = new \local_trustgrade\task\process_submission_adhoc();
        $task->set_custom_data((object)[
            'submission_id' => $submission_id,
            'cmid' => $cmid,
            'userid' => $userid
        ]);

        // Queue the task to run as soon as possible
        \core\task\manager::queue_adhoc_task($task);
    }

}
