<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to cleanup old debug cache records
 */
class cleanup_debug_cache extends \core\task\scheduled_task {
    
    /**
     * Get a descriptive name for this task
     * 
     * @return string
     */
    public function get_name() {
        return get_string('cleanup_debug_cache', 'local_trustgrade');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        \local_trustgrade\debug_cache::cleanup_old_records();
        mtrace('TrustGrade debug cache cleanup completed');
    }
}
