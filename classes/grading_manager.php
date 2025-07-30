<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/trustgrade/classes/quiz_session.php');

/**
 * Grading manager for handling direct grading from quiz reports
 */
class grading_manager {
    
    /** @var int Course module ID */
    private $cmid;
    
    /** @var object Course module object */
    private $cm;
    
    /** @var object Assignment object */
    private $assignment;
    
    /** @var float Maximum grade for the assignment */
    private $max_grade;
    
    /**
     * Constructor
     * @param int $cmid Course module ID
     */
    public function __construct($cmid = 0) {
        global $DB;
        
        $this->cmid = $cmid;
        
        if ($cmid > 0) {
            $this->cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
            $this->assignment = $DB->get_record('assign', ['id' => $this->cm->instance], '*', MUST_EXIST);
            $this->max_grade = floatval($this->assignment->grade);
        } else {
            // Default values when no specific assignment context
            $this->max_grade = 100.0;
        }
    }
    
    /**
     * Validate a grade value
     * 
     * @param mixed $grade Grade value to validate
     * @return array Validation result with 'valid' boolean and 'grade' or 'error'
     */
    public function validate_grade($grade) {
        // Handle empty grade (ungraded)
        if ($grade === '' || $grade === null) {
            return ['valid' => true, 'grade' => null];
        }
        
        // Convert to float and validate
        if (!is_numeric($grade)) {
            return ['valid' => false, 'error' => get_string('grade_not_numeric', 'local_trustgrade')];
        }
        
        $grade = floatval($grade);
        
        // Check grade bounds
        if ($grade < 0) {
            return ['valid' => false, 'error' => get_string('grade_cannot_be_negative', 'local_trustgrade')];
        }
        
        if ($grade > $this->max_grade) {
            return ['valid' => false, 'error' => get_string('grade_exceeds_maximum', 'local_trustgrade', $this->max_grade)];
        }
        
        return ['valid' => true, 'grade' => $grade];
    }
    
    /**
     * Save a grade for a student
     * 
     * @param int $userid User ID
     * @param float|null $grade Grade value (null for ungraded)
     * @return array Result with success status and message
     */
    public function save_grade($userid, $grade) {
        global $DB, $USER;
        
        try {
            // Validate grade
            $validation = $this->validate_grade($grade);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['error']];
            }
            
            if ($this->cmid > 0) {
                // Save to assignment grades table
                $existing_grade = $DB->get_record('assign_grades', [
                    'assignment' => $this->assignment->id,
                    'userid' => $userid
                ]);
                
                if ($existing_grade) {
                    // Update existing grade
                    $existing_grade->grade = $grade;
                    $existing_grade->grader = $USER->id;
                    $existing_grade->timemodified = time();
                    $DB->update_record('assign_grades', $existing_grade);
                } else {
                    // Insert new grade
                    $grade_record = new \stdClass();
                    $grade_record->assignment = $this->assignment->id;
                    $grade_record->userid = $userid;
                    $grade_record->grade = $grade;
                    $grade_record->grader = $USER->id;
                    $grade_record->timecreated = time();
                    $grade_record->timemodified = time();
                    $DB->insert_record('assign_grades', $grade_record);
                }
                
                // Update gradebook
                $this->update_gradebook($userid, $grade);
            }
            
            return [
                'success' => true,
                'message' => get_string('grade_saved_successfully', 'local_trustgrade')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('grade_save_error', 'local_trustgrade', $e->getMessage())
            ];
        }
    }
    
    /**
     * Save multiple grades in bulk
     * @param array $grades Array of grade objects with userid and grade properties
     * @return array Result array with success status and counts
     */
    public function save_bulk_grades($grades) {
        $saved_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($grades as $userid => $grade) {
            $result = $this->save_grade($userid, $grade);
            
            if ($result['success']) {
                $saved_count++;
            } else {
                $failed_count++;
                $errors[] = "User {$userid}: " . $result['message'];
            }
        }
        
        if ($failed_count === 0) {
            return [
                'success' => true,
                'saved_count' => $saved_count,
                'message' => get_string('bulk_grades_saved', 'local_trustgrade', $saved_count)
            ];
        } else {
            return [
                'success' => false,
                'saved_count' => $saved_count,
                'failed_count' => $failed_count,
                'message' => get_string('bulk_grades_partial', 'local_trustgrade', [
                    'saved' => $saved_count,
                    'failed' => $failed_count
                ]),
                'errors' => $errors
            ];
        }
    }
    
    /**
     * Clear all grades for the assignment
     * @return array Result array with success status
     */
    public function clear_all_grades() {
        global $DB;
        
        try {
            if ($this->cmid > 0) {
                // Delete from assign_grades table
                $DB->delete_records('assign_grades', ['assignment' => $this->assignment->id]);
                
                // Update gradebook to remove grades
                $this->clear_gradebook_grades();
            }
            
            return [
                'success' => true,
                'message' => get_string('grades_cleared_success', 'local_trustgrade')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('grade_clear_error', 'local_trustgrade', $e->getMessage())
            ];
        }
    }
    
    /**
     * Auto-grade all students based on their quiz scores
     * @return array Result array with success status and grades applied
     */
    public function auto_grade_by_quiz_score() {
        try {
            // Get all completed quiz sessions for this assignment
            $sessions = quiz_session::get_completed_sessions_for_assignment($this->cmid);
            
            if (empty($sessions)) {
                return [
                    'success' => false,
                    'message' => get_string('no_completed_quizzes', 'local_trustgrade'),
                    'graded_count' => 0,
                    'grades' => [],
                    'errors' => []
                ];
            }
            
            $graded_count = 0;
            $applied_grades = [];
            $errors = [];
            
            foreach ($sessions as $session) {
                // Calculate grade based on quiz score
                $quiz_grade = $this->calculate_grade_from_quiz_score($session);
                
                if ($quiz_grade !== null) {
                    $result = $this->save_grade($session->userid, $quiz_grade);
                    
                    if ($result['success']) {
                        $graded_count++;
                        // Store as associative array for JSON encoding
                        $applied_grades[(string)$session->userid] = (float)$quiz_grade;
                    } else {
                        $errors[] = "User {$session->userid}: " . $result['message'];
                    }
                } else {
                    $errors[] = "User {$session->userid}: Could not calculate grade from quiz score";
                }
            }
            
            if ($graded_count > 0) {
                return [
                    'success' => true,
                    'graded_count' => $graded_count,
                    'grades' => $applied_grades,
                    'message' => get_string('auto_grade_success', 'local_trustgrade', $graded_count),
                    'errors' => $errors
                ];
            } else {
                return [
                    'success' => false,
                    'message' => get_string('auto_grade_no_grades', 'local_trustgrade'),
                    'graded_count' => 0,
                    'grades' => [],
                    'errors' => $errors
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('auto_grade_error', 'local_trustgrade', $e->getMessage()),
                'graded_count' => 0,
                'grades' => [],
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Calculate assignment grade from quiz score
     * @param \stdClass $session Quiz session object
     * @return float|null Calculated grade or null if cannot calculate
     */
    private function calculate_grade_from_quiz_score($session) {
        // Get quiz data
        $questions = (array)$session->questions_data;
        $final_score = floatval($session->final_score);
        
        if (empty($questions) || $final_score < 0) {
            return null;
        }
        
        // Calculate total possible points
        $total_points = 0;
        foreach ($questions as $question) {
            if (is_object($question)) {
                $total_points += isset($question->points) ? floatval($question->points) : 10;
            } else if (is_array($question)) {
                $total_points += isset($question['points']) ? floatval($question['points']) : 10;
            } else {
                // Default points if question structure is unexpected
                $total_points += 10;
            }
        }
        
        if ($total_points <= 0) {
            return null;
        }
        
        // Calculate percentage and convert to assignment grade scale
        $percentage = $final_score / $total_points;
        $assignment_grade = $percentage * $this->max_grade;
        
        // Round to 2 decimal places and ensure within bounds
        $assignment_grade = round($assignment_grade, 2);
        $assignment_grade = max(0, min($assignment_grade, $this->max_grade));
        
        return $assignment_grade;
    }
    
    /**
     * Get current grades for specified users
     * @param array $userids Array of user IDs (optional)
     * @return array Array of grades indexed by user ID
     */
    public function get_current_grades($userids = []) {
        global $DB;
        
        if ($this->cmid <= 0) {
            return [];
        }
        
        $params = ['assignment' => $this->assignment->id];
        $where_clause = 'assignment = :assignment';
        
        if (!empty($userids)) {
            list($in_sql, $in_params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $where_clause .= ' AND userid ' . $in_sql;
            $params = array_merge($params, $in_params);
        }
        
        $grades = $DB->get_records_select('assign_grades', $where_clause, $params, '', 'userid, grade');
        
        $result = [];
        foreach ($grades as $grade) {
            // Store as associative array for JSON encoding
            $result[(string)$grade->userid] = (float)$grade->grade;
        }
        
        return $result;
    }
    
    /**
     * Update gradebook with new grade
     * @param int $userid User ID
     * @param float $grade Grade value
     */
    private function update_gradebook($userid, $grade) {
        global $CFG;
        
        require_once($CFG->libdir . '/gradelib.php');
        
        $grade_item = \grade_item::fetch([
            'courseid' => $this->cm->course,
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $this->assignment->id
        ]);
        
        if ($grade_item) {
            $grade_item->update_final_grade($userid, $grade);
        }
    }
    
    /**
     * Clear all grades from gradebook
     */
    private function clear_gradebook_grades() {
        global $CFG;
        
        require_once($CFG->libdir . '/gradelib.php');
        
        $grade_item = \grade_item::fetch([
            'courseid' => $this->cm->course,
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $this->assignment->id
        ]);
        
        if ($grade_item) {
            $grade_item->delete_all_grades();
        }
    }
    
    /**
     * Static method to get current grades for multiple users
     * @param int $cmid Course module ID
     * @param array $userids Array of user IDs
     * @return array Array of grades indexed by user ID
     */
    public static function get_current_grades_static($cmid, $userids = []) {
        $manager = new self($cmid);
        return $manager->get_current_grades($userids);
    }
}
