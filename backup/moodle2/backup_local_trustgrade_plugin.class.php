<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup plugin for local_trustgrade
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides backup support for local_trustgrade
 */
class backup_local_trustgrade_plugin extends backup_local_plugin {

    /**
     * Returns the paths to be handled by the plugin at course level
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        
        // Create wrapper element
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        
        // Connect wrapper to the plugin
        $plugin->add_child($pluginwrapper);
        
        // Define trustgrade_logs table
        $logs = new backup_nested_element('trustgrade_logs');
        $log = new backup_nested_element('log', ['id'], [
            'userid', 'cmid', 'instructions', 'recommendation', 'timecreated'
        ]);
        $logs->add_child($log);
        $pluginwrapper->add_child($logs);
        
        // Set source for logs
        $log->set_source_table('local_trustgrade_logs', ['cmid' => backup::VAR_COURSEID]);
        
        // Annotate user
        $log->annotate_ids('user', 'userid');
        
        return $plugin;
    }

    /**
     * Returns the paths to be handled by the plugin at module level (for assign module)
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();
        
        // Create wrapper element
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        
        // Connect wrapper to the plugin
        $plugin->add_child($pluginwrapper);
        
        // Define quiz_settings table
        $settings = new backup_nested_element('quiz_settings', ['id'], [
            'cmid', 'enabled', 'questions_to_generate', 'total_quiz_questions',
            'instructor_questions', 'submission_questions', 'randomize_answers',
            'time_per_question', 'show_countdown', 'timecreated', 'timemodified'
        ]);
        $pluginwrapper->add_child($settings);
        
        // Define questions table
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'cmid', 'userid', 'question_data', 'is_mandatory', 'timecreated', 'timemodified'
        ]);
        $questions->add_child($question);
        $pluginwrapper->add_child($questions);
        
        // Define submission_questions table
        $subquestions = new backup_nested_element('submission_questions');
        $subquestion = new backup_nested_element('submission_question', ['id'], [
            'submission_id', 'cmid', 'userid', 'question_data', 'timecreated', 'timemodified'
        ]);
        $subquestions->add_child($subquestion);
        $pluginwrapper->add_child($subquestions);
        
        // Define quiz_sessions table
        $sessions = new backup_nested_element('quiz_sessions');
        $session = new backup_nested_element('quiz_session', ['id'], [
            'cmid', 'submissionid', 'userid', 'questions_data', 'settings_data',
            'current_question', 'answers_data', 'time_remaining', 'window_blur_count',
            'attempt_started', 'attempt_completed', 'integrity_violations', 'final_score',
            'timecreated', 'timemodified', 'timecompleted'
        ]);
        $sessions->add_child($session);
        $pluginwrapper->add_child($sessions);
        
        // Define async_tasks table
        $tasks = new backup_nested_element('async_tasks');
        $task = new backup_nested_element('async_task', ['id'], [
            'cmid', 'submission_id', 'userid', 'status', 'submission_content',
            'assignment_instructions', 'questions_count', 'result_data', 'error_message',
            'attempts', 'next_retry_time', 'timecreated', 'timemodified', 'timecompleted'
        ]);
        $tasks->add_child($task);
        $pluginwrapper->add_child($tasks);
        
        // Set sources
        $settings->set_source_table('local_trustgd_quiz_settings', 
            ['cmid' => backup::VAR_MODID]);
            
        $question->set_source_table('local_trustgrade_questions', 
            ['cmid' => backup::VAR_MODID]);
            
        $subquestion->set_source_table('local_trustgd_sub_questions', 
            ['cmid' => backup::VAR_MODID]);
            
        $session->set_source_table('local_trustgd_quiz_sessions', 
            ['cmid' => backup::VAR_MODID]);
            
        $task->set_source_table('local_trustgd_async_tasks', 
            ['cmid' => backup::VAR_MODID]);
        
        // Annotate user IDs
        $question->annotate_ids('user', 'userid');
        $subquestion->annotate_ids('user', 'userid');
        $session->annotate_ids('user', 'userid');
        $task->annotate_ids('user', 'userid');
        
        return $plugin;
    }
}
