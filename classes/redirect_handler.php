<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles redirects to AI quiz after submission.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $cache = \cache::make('local_trustgrade', 'quiz_redirect');
        
        $redirect_data = $cache->get($cmid);
        
        if ($redirect_data === false) {
            return false;
        }
        
        // Check if redirect is still valid (within 5 minutes)
        if (time() - $redirect_data['timestamp'] > 300) {
            $cache->delete($cmid);
            return false;
        }
        
        $submission_id = $redirect_data['submission_id'];
        
        // Check if there are questions available
        $questions = submission_processor::get_all_questions_for_student($cmid, $submission_id);
        
        if (empty($questions)) {
            // No questions available, clear redirect flag
            $cache->delete($cmid);
            return false;
        }
        
        // Clear the redirect flag
        $cache->delete($cmid);
        
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
