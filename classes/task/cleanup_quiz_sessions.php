<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to cleanup old quiz session records
 */
class cleanup_quiz_sessions extends \core\task\scheduled_task {
    
    /**
     * Get a descriptive name for this task
     * 
     * @return string
     */
    public function get_name() {
        return get_string('cleanup_quiz_sessions', 'local_trustgrade');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        \local_trustgrade\quiz_session::cleanup_old_sessions();
        mtrace('TrustGrade quiz sessions cleanup completed');
    }
}
