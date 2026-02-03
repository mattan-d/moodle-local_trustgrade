<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_trustgrade\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an async task status changes
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_status_changed extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_trustgd_async_tasks';
    }

    /**
     * Returns localised general event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_task_status_changed', 'local_trustgrade');
    }

    /**
     * Returns description of what happened
     *
     * @return string
     */
    public function get_description() {
        return "Task {$this->objectid} status changed to {$this->other['status']} for user {$this->relateduserid}";
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['status'])) {
            throw new \coding_exception('The \'status\' value must be set in other.');
        }

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }
}
