<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = required_param('action', PARAM_TEXT);

// Verify sesskey for security
require_sesskey();

$redirect_url = new moodle_url('/admin/settings.php', ['section' => 'local_trustgrade']);

switch ($action) {
    case 'clear_cache':
        try {
            // Clear all cache
            \local_trustgrade\debug_cache::cleanup_old_records(0); // Clear all records
            
            // Add success notification
            \core\notification::add(
                get_string('cache_cleared_success', 'local_trustgrade'), 
                \core\notification::SUCCESS
            );
            
        } catch (Exception $e) {
            // Add error notification
            \core\notification::add(
                get_string('cache_clear_error', 'local_trustgrade', $e->getMessage()), 
                \core\notification::ERROR
            );
        }
        break;
        
    case 'clear_instructions_cache':
        try {
            \local_trustgrade\debug_cache::clear_cache_by_type('check_instructions');
            \core\notification::add(
                get_string('instructions_cache_cleared', 'local_trustgrade'), 
                \core\notification::SUCCESS
            );
        } catch (Exception $e) {
            \core\notification::add(
                get_string('cache_clear_error', 'local_trustgrade', $e->getMessage()), 
                \core\notification::ERROR
            );
        }
        break;
        
    case 'clear_questions_cache':
        try {
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_questions');
            \core\notification::add(
                get_string('questions_cache_cleared', 'local_trustgrade'), 
                \core\notification::SUCCESS
            );
        } catch (Exception $e) {
            \core\notification::add(
                get_string('cache_clear_error', 'local_trustgrade', $e->getMessage()), 
                \core\notification::ERROR
            );
        }
        break;
        
    case 'clear_submissions_cache':
        try {
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_submission_questions');
            \core\notification::add(
                get_string('submissions_cache_cleared', 'local_trustgrade'), 
                \core\notification::SUCCESS
            );
        } catch (Exception $e) {
            \core\notification::add(
                get_string('cache_clear_error', 'local_trustgrade', $e->getMessage()), 
                \core\notification::ERROR
            );
        }
        break;
        
    case 'cleanup_old_cache':
        try {
            \local_trustgrade\debug_cache::cleanup_old_records(7); // Keep only 7 days
            \core\notification::add(
                get_string('old_cache_cleaned', 'local_trustgrade'), 
                \core\notification::SUCCESS
            );
        } catch (Exception $e) {
            \core\notification::add(
                get_string('cache_clear_error', 'local_trustgrade', $e->getMessage()), 
                \core\notification::ERROR
            );
        }
        break;
        
    default:
        \core\notification::add(
            get_string('invalid_action', 'local_trustgrade'), 
            \core\notification::ERROR
        );
        break;
}

// Redirect back to settings page
redirect($redirect_url);
