<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore plugin for local_trustgrade
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides restore support for local_trustgrade
 */
class restore_local_trustgrade_plugin extends restore_local_plugin {

    /**
     * Returns the paths to be handled by the plugin at course level
     */
    protected function define_course_plugin_structure() {
        $paths = [];
        
        // Course level logs
        $paths[] = new restore_path_element('trustgrade_log',
            $this->get_pathfor('/trustgrade_logs/log'));
        
        return $paths;
    }

    /**
     * Returns the paths to be handled by the plugin at module level
     */
    protected function define_module_plugin_structure() {
        $paths = [];
        
        // Module level data
        $paths[] = new restore_path_element('trustgrade_quiz_settings',
            $this->get_pathfor('/quiz_settings'));
            
        $paths[] = new restore_path_element('trustgrade_question',
            $this->get_pathfor('/questions/question'));
            
        $paths[] = new restore_path_element('trustgrade_submission_question',
            $this->get_pathfor('/submission_questions/submission_question'));
            
        $paths[] = new restore_path_element('trustgrade_quiz_session',
            $this->get_pathfor('/quiz_sessions/quiz_session'));
            
        $paths[] = new restore_path_element('trustgrade_async_task',
            $this->get_pathfor('/async_tasks/async_task'));
        
        return $paths;
    }

    /**
     * Process trustgrade log restoration
     */
    public function process_trustgrade_log($data) {
        global $DB;

        $data = (object)$data;
        $data->cmid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_trustgrade_logs', $data);
    }

    /**
     * Process quiz settings restoration
     */
    public function process_trustgrade_quiz_settings($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cmid = $this->task->get_moduleid();

        // Check if settings already exist for this cmid
        if ($existing = $DB->get_record('local_trustgd_quiz_settings', ['cmid' => $data->cmid])) {
            // Update existing record
            $data->id = $existing->id;
            $DB->update_record('local_trustgd_quiz_settings', $data);
            $newid = $existing->id;
        } else {
            // Insert new record
            unset($data->id);
            $newid = $DB->insert_record('local_trustgd_quiz_settings', $data);
        }

        $this->set_mapping('trustgrade_quiz_settings', $oldid, $newid);
    }

    /**
     * Process question restoration
     */
    public function process_trustgrade_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        unset($data->id);
        $newid = $DB->insert_record('local_trustgrade_questions', $data);
        
        $this->set_mapping('trustgrade_question', $oldid, $newid);
    }

    /**
     * Process submission question restoration
     */
    public function process_trustgrade_submission_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->submission_id = $this->get_mappingid('submission', $data->submission_id);

        unset($data->id);
        $newid = $DB->insert_record('local_trustgd_sub_questions', $data);
        
        $this->set_mapping('trustgrade_submission_question', $oldid, $newid);
    }

    /**
     * Process quiz session restoration.
     * Uses replace (insert or update) to avoid duplicate key on (cmid, submissionid, userid)
     * when duplicating/restoring, since multiple backup rows can map to the same triple
     * (e.g. submissionid 0 / userid 0) or a session may already exist for the new cmid.
     */
    public function process_trustgrade_quiz_session($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cmid = $this->task->get_moduleid();
        $mappeduser = $this->get_mappingid('user', $data->userid);
        $mappedsub = $this->get_mappingid('submission', $data->submissionid);
        $data->userid = ($mappeduser !== false && $mappeduser !== null) ? (int) $mappeduser : 0;
        $data->submissionid = ($mappedsub !== false && $mappedsub !== null) ? (int) $mappedsub : 0;

        $existing = $DB->get_record('local_trustgd_quiz_sessions', [
            'cmid' => $data->cmid,
            'submissionid' => $data->submissionid,
            'userid' => $data->userid,
        ]);

        unset($data->id);
        if ($existing) {
            $data->id = $existing->id;
            $DB->update_record('local_trustgd_quiz_sessions', $data);
            $newid = $existing->id;
        } else {
            $newid = $DB->insert_record('local_trustgd_quiz_sessions', $data);
        }

        $this->set_mapping('trustgrade_quiz_session', $oldid, $newid);
    }

    /**
     * Process async task restoration
     */
    public function process_trustgrade_async_task($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->submission_id = $this->get_mappingid('submission', $data->submission_id);
        
        // Reset async tasks to pending status on restore
        $data->status = 'pending';
        $data->attempts = 0;
        $data->error_message = null;
        $data->timecompleted = null;

        unset($data->id);
        $newid = $DB->insert_record('local_trustgd_async_tasks', $data);
        
        $this->set_mapping('trustgrade_async_task', $oldid, $newid);
    }
}
