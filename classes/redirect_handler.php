<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles redirects to AI quiz after submission
 */
class redirect_handler {
    
    /**
     * Check if user should be redirected to quiz and handle redirect
     * 
     * @param int $cmid Course module ID
     * @return bool True if redirect was handled
     */
    public static function check_and_handle_redirect($cmid) {
        global $SESSION;
        
        if (!isset($SESSION->trustgrade_quiz_redirect[$cmid])) {
            return false;
        }
        
        $redirect_data = $SESSION->trustgrade_quiz_redirect[$cmid];
        
        // Check if redirect is still valid (within 5 minutes)
        if (time() - $redirect_data['timestamp'] > 300) {
            unset($SESSION->trustgrade_quiz_redirect[$cmid]);
            return false;
        }
        
        $submission_id = $redirect_data['submission_id'];
        
        // Check if there are questions available
        $questions = submission_processor::get_all_questions_for_student($cmid, $submission_id);
        
        if (empty($questions)) {
            // No questions available, clear redirect flag
            unset($SESSION->trustgrade_quiz_redirect[$cmid]);
            return false;
        }
        
        // Clear the redirect flag
        unset($SESSION->trustgrade_quiz_redirect[$cmid]);
        
        // Perform immediate redirect to quiz
        self::redirect_to_quiz($cmid, $submission_id);
        
        return true;
    }
    
    /**
     * Redirect immediately to quiz interface
     * 
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     */
    private static function redirect_to_quiz($cmid, $submission_id) {
        global $CFG;
        
        $quiz_url = new \moodle_url('/local/trustgrade/quiz_interface.php', [
            'cmid' => $cmid,
            'submissionid' => $submission_id
        ]);
        
        // Add success notification for display on quiz page
        \core\notification::add(
            get_string('quiz_ready_message', 'local_trustgrade'), 
            \core\notification::SUCCESS
        );
        
        // Perform redirect
        redirect($quiz_url);
    }
}
