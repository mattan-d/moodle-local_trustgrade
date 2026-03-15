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
     * Cache key for redirect by user (used when async task sets redirect for next page load).
     *
     * @param int $userid User ID
     * @param int $cmid Course module ID
     * @return string Cache key
     */
    private static function redirect_cache_key($userid, $cmid) {
        return (int) $userid . '_' . (int) $cmid;
    }

    /**
     * Set redirect flag so the user is sent to the quiz on next assignment view.
     * Call this when quiz questions are ready (e.g. after async task completes).
     * Works even when "Require students to click the submit button" is enabled.
     *
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID (owner of the submission)
     */
    public static function set_redirect_flag($cmid, $submissionid, $userid) {
        $cache = \cache::make('local_trustgrade', 'quiz_redirect_by_user');
        $key = self::redirect_cache_key($userid, $cmid);
        $cache->set($key, [
            'submission_id' => (int) $submissionid,
            'timestamp' => time(),
        ]);
    }

    /**
     * Check if user should be redirected to quiz and handle redirect
     * 
     * @param int $cmid Course module ID
     * @return bool True if redirect was handled
     */
    public static function check_and_handle_redirect($cmid) {
        global $USER;

        // Prefer per-user redirect (set when async task completes; works with "submit button required").
        $cache_by_user = \cache::make('local_trustgrade', 'quiz_redirect_by_user');
        $key_by_user = self::redirect_cache_key($USER->id, $cmid);
        $redirect_data = $cache_by_user->get($key_by_user);

        if ($redirect_data === false) {
            // Fallback: session cache (set during same request/session).
            $cache = \cache::make('local_trustgrade', 'quiz_redirect');
            $redirect_data = $cache->get($cmid);
        }

        if ($redirect_data === false) {
            return false;
        }

        // Check if redirect is still valid (within 5 minutes)
        if (time() - $redirect_data['timestamp'] > 300) {
            $cache_by_user->delete($key_by_user);
            $cache = \cache::make('local_trustgrade', 'quiz_redirect');
            $cache->delete($cmid);
            return false;
        }

        $submission_id = $redirect_data['submission_id'];

        // Check if there are questions available
        $questions = submission_processor::get_all_questions_for_student($cmid, $submission_id);

        if (empty($questions)) {
            $cache_by_user->delete($key_by_user);
            $cache = \cache::make('local_trustgrade', 'quiz_redirect');
            $cache->delete($cmid);
            return false;
        }

        // Clear the redirect flag
        $cache_by_user->delete($key_by_user);
        $cache = \cache::make('local_trustgrade', 'quiz_redirect');
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
