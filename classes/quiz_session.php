<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz session manager for maintaining quiz state across page refreshes
 */
class quiz_session {
    
    /**
     * Get an existing session or create a new one if it doesn't exist.
     * This is the primary entry point for starting a quiz.
     *
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @return array|null Session data or null if creation fails (e.g., no questions).
     */
    public static function get_or_create_session($cmid, $submissionid, $userid) {
        // First, try to get an existing session for the user.
        $session = self::get_session($cmid, $submissionid, $userid);
        if ($session) {
            if (self::is_session_expired($session)) {
                // Session expired, delete it and create a new one
                self::delete_session($cmid, $submissionid, $userid);
            } else {
                return $session;
            }
        }

        // If no session exists or expired session was deleted, create a new one.
        global $DB;
        try {
            // Get the quiz settings and generate the questions.
            $settings = quiz_settings::get_settings($cmid);
            $questions = submission_processor::get_quiz_questions_with_settings($cmid, $submissionid, $settings);

            // If no questions are available, we cannot create a session.
            if (empty($questions)) {
                return null;
            }

            // Prepare the new session record.
            $record = new \stdClass();
            $record->cmid = $cmid;
            $record->submissionid = $submissionid;
            $record->userid = $userid;
            $record->questions_data = json_encode($questions);
            $record->settings_data = json_encode($settings);
            $record->current_question = 0;
            $record->answers_data = json_encode([]);
            $record->time_remaining = $settings['time_per_question'] ?? 15;
            $record->window_blur_count = 0;
            $record->attempt_started = 0; // Attempt is created but not officially started by the user yet.
            $record->attempt_completed = 0;
            $record->integrity_violations = json_encode([]);
            $record->timecreated = time();
            $record->timemodified = time();
            $record->session_expires = time() + 300; // 5 minutes = 300 seconds

            // Insert the new record into the database.
            $record->id = $DB->insert_record('local_trustgd_quiz_sessions', $record);

            // Return the newly created session data.
            return self::get_session($cmid, $submissionid, $userid);

        } catch (\Exception $e) {
            error_log('Failed to get or create quiz session: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get existing quiz session
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @return array|null Session data or null if not found
     */
    public static function get_session($cmid, $submissionid, $userid) {
        global $DB;
        
        try {
            $record = $DB->get_record('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            
            if (!$record) {
                return null;
            }
            
            return [
                'id' => $record->id,
                'cmid' => (int)$record->cmid,
                'submissionid' => (int)$record->submissionid,
                'questions' => json_decode($record->questions_data, true),
                'settings' => json_decode($record->settings_data, true),
                'current_question' => (int)$record->current_question,
                'answers' => json_decode($record->answers_data, true) ?: [],
                'time_remaining' => (int)$record->time_remaining,
                'window_blur_count' => (int)$record->window_blur_count,
                'attempt_started' => (bool)$record->attempt_started,
                'attempt_completed' => (bool)$record->attempt_completed,
                'integrity_violations' => json_decode($record->integrity_violations, true) ?: [],
                'timecreated' => $record->timecreated,
                'timemodified' => $record->timemodified,
                'session_expires' => $record->session_expires ?? ($record->timecreated + 300)
            ];
            
        } catch (\Exception $e) {
            error_log('Failed to get quiz session: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update quiz session state
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @param array $updates Updates to apply
     * @return bool Success status
     */
    public static function update_session($cmid, $submissionid, $userid, $updates) {
        global $DB;
        
        try {
            $record = $DB->get_record('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            
            if (!$record) {
                return false;
            }
            
            // Apply updates
            foreach ($updates as $field => $value) {
                switch ($field) {
                    case 'current_question':
                        $record->current_question = (int)$value;
                        break;
                    case 'answers':
                        $record->answers_data = json_encode($value);
                        break;
                    case 'time_remaining':
                        $record->time_remaining = (int)$value;
                        break;
                    case 'window_blur_count':
                        $record->window_blur_count = (int)$value;
                        break;
                    case 'attempt_started':
                        $record->attempt_started = (bool)$value ? 1 : 0;
                        break;
                    case 'attempt_completed':
                        $record->attempt_completed = (bool)$value ? 1 : 0;
                        break;
                    case 'integrity_violations':
                        $record->integrity_violations = json_encode($value);
                        break;
                }
            }
            
            $record->timemodified = time();
            $DB->update_record('local_trustgd_quiz_sessions', $record);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Failed to update quiz session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Complete quiz session
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @param array $final_answers Final answers
     * @param int $final_score Final score
     * @return bool Success status
     */
    public static function complete_session($cmid, $submissionid, $userid, $final_answers, $final_score = 0) {
        global $DB;
        
        try {
            $record = $DB->get_record('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            
            if (!$record) {
                return false;
            }
            
            $record->answers_data = json_encode($final_answers);
            $record->final_score = $final_score;
            $record->attempt_completed = 1;
            $record->timecompleted = time();
            $record->timemodified = time();
            
            $DB->update_record('local_trustgd_quiz_sessions', $record);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Failed to complete quiz session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has already completed the quiz
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @return bool True if completed
     */
    public static function is_completed($cmid, $submissionid, $userid) {
        global $DB;
        
        try {
            $record = $DB->get_record('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            
            return $record && $record->attempt_completed;
            
        } catch (\Exception $e) {
            error_log('Failed to check completion status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log integrity violation
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @param string $violation_type Type of violation
     * @param array $violation_data Additional violation data
     * @return bool Success status
     */
    public static function log_integrity_violation($cmid, $submissionid, $userid, $violation_type, $violation_data = []) {
        global $DB;
        
        try {
            $record = $DB->get_record('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            
            if (!$record) {
                return false;
            }
            
            $violations = json_decode($record->integrity_violations, true) ?: [];
            $violations[] = [
                'type' => $violation_type,
                'data' => $violation_data,
                'timestamp' => time()
            ];
            
            $record->integrity_violations = json_encode($violations);
            $record->timemodified = time();
            
            $DB->update_record('local_trustgd_quiz_sessions', $record);
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Failed to log integrity violation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all completed quiz sessions for a specific assignment
     * 
     * @param int $cmid Course module ID
     * @return array Array of session records with user details
     */
    public static function get_completed_sessions_for_assignment($cmid) {
        global $DB;
        
        $sql = "SELECT s.*, u.firstname, u.lastname, u.email
                FROM {local_trustgd_quiz_sessions} s
                JOIN {user} u ON s.userid = u.id
                WHERE s.cmid = :cmid AND s.attempt_completed = 1
                ORDER BY u.lastname, u.firstname";
        
        try {
            $sessions = $DB->get_records_sql($sql, ['cmid' => $cmid]);
            
            foreach ($sessions as $session) {
                $session->questions_data = json_decode($session->questions_data);
                $session->answers_data = json_decode($session->answers_data);
                $session->integrity_violations = json_decode($session->integrity_violations);
            }
            
            return $sessions;
            
        } catch (\Exception $e) {
            error_log('Failed to get completed quiz sessions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old quiz sessions (older than specified days)
     * 
     * @param int $days Number of days to keep (default: 30)
     * @return bool Success status
     */
    public static function cleanup_old_sessions($days = 30) {
        global $DB;
        
        try {
            $cutoff_time = time() - ($days * 24 * 60 * 60);
            $DB->delete_records_select('local_trustgd_quiz_sessions', 'timecreated < ?', [$cutoff_time]);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to cleanup old quiz sessions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a quiz session has expired (older than 5 minutes)
     * 
     * @param array $session Session data
     * @return bool True if session has expired
     */
    public static function is_session_expired($session) {
        $current_time = time();
        $session_expires = $session['session_expires'] ?? ($session['timecreated'] + 300);
        return $current_time > $session_expires;
    }

    /**
     * Delete a quiz session
     * 
     * @param int $cmid Course module ID
     * @param int $submissionid Submission ID
     * @param int $userid User ID
     * @return bool Success status
     */
    public static function delete_session($cmid, $submissionid, $userid) {
        global $DB;
        
        try {
            $DB->delete_records('local_trustgd_quiz_sessions', [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'userid' => $userid
            ]);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to delete quiz session: ' . $e->getMessage());
            return false;
        }
    }
}
