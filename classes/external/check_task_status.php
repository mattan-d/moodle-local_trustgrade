<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_module;
use invalid_parameter_exception;
use moodle_exception;

/**
 * External API for checking task status
 */
class check_task_status extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'submission_id' => new external_value(PARAM_INT, 'Submission ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    /**
     * Check task status
     * @param int $submission_id
     * @param int $cmid
     * @return array
     */
    public static function execute($submission_id, $cmid) {
        global $DB;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'submission_id' => $submission_id,
            'cmid' => $cmid
        ]);

        error_log("TrustGrade: Checking task status for submission_id={$params['submission_id']}, cmid={$params['cmid']}");

        // Verify user has access to this assignment
        $cm = get_coursemodule_from_id('assign', $params['cmid']);
        if (!$cm) {
            throw new invalid_parameter_exception('Assignment not found');
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:submit', $context);

        // Check task status
        $status = $DB->get_record('local_trustgrade_task_status', [
            'submission_id' => $params['submission_id'],
            'cmid' => $params['cmid']
        ]);

        if ($status) {
            error_log("TrustGrade: Found task status: {$status->status}");
        } else {
            error_log("TrustGrade: No task status found, checking all records for this submission_id");
            $all_records = $DB->get_records('local_trustgrade_task_status', ['submission_id' => $params['submission_id']]);
            error_log("TrustGrade: Found " . count($all_records) . " records for submission_id {$params['submission_id']}");
            foreach ($all_records as $record) {
                error_log("TrustGrade: Record - submission_id: {$record->submission_id}, cmid: {$record->cmid}, status: {$record->status}");
            }
        }

        return [
            'status' => $status ? $status->status : 'not_found',
            'completed_at' => $status && $status->completed_at ? (int)$status->completed_at : null,
            'error_message' => $status && $status->error_message ? $status->error_message : null
        ];
    }

    /**
     * Returns description of method return value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Task status'),
            'completed_at' => new external_value(PARAM_INT, 'Completion timestamp', VALUE_OPTIONAL),
            'error_message' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_OPTIONAL)
        ]);
    }
}
