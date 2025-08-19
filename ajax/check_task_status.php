<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../../config.php');

defined('MOODLE_INTERNAL') || die();

// Require login
require_login();

// Get parameters
$submission_id = required_param('submission_id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

// Verify user has access to this assignment
$cm = get_coursemodule_from_id('assign', $cmid);
if (!$cm) {
    http_response_code(404);
    die('Assignment not found');
}

$context = context_module::instance($cm->id);
require_capability('mod/assign:submit', $context);

// Check task status
global $DB;
$status = $DB->get_record('local_trustgrade_task_status', [
    'submission_id' => $submission_id,
    'cmid' => $cmid
]);

$response = [
    'status' => $status ? $status->status : 'not_found',
    'completed_at' => $status && $status->completed_at ? $status->completed_at : null,
    'error_message' => $status && $status->error_message ? $status->error_message : null
];

header('Content-Type: application/json');
echo json_encode($response);
