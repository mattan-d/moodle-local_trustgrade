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
 * Grading manager for handling direct grading from quiz reports.
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
     *
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
        global $DB, $USER, $CFG;

        try {
            // Validate grade
            $validation = $this->validate_grade($grade);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['error']];
            }

            if ($this->cmid > 0) {
                require_once($CFG->libdir . '/gradelib.php');

                $grade_item = \grade_item::fetch([
                        'courseid' => $this->cm->course,
                        'itemtype' => 'mod',
                        'itemmodule' => 'assign',
                        'iteminstance' => $this->assignment->id
                ]);

                if ($grade_item) {
                    $grade_grade = new \grade_grade(['itemid' => $grade_item->id, 'userid' => $userid], false);

                    // If grade exists and is overridden, don't update it
                    if ($grade_grade->id && $grade_grade->overridden != 0) {
                        return [
                                'success' => false,
                                'message' => get_string('grade_is_overridden', 'local_trustgrade')
                        ];
                    }
                }
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
     *
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
                $errors[] = get_string('user_label', 'local_trustgrade') . " {$userid}: " . $result['message'];
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
     *
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
     *
     * @return array Result array with success status and grades applied
     */
    public function auto_grade_by_quiz_score() {
        global $CFG;

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

            require_once($CFG->libdir . '/gradelib.php');
            $overridden_userids = [];

            if ($this->cmid > 0) {
                $grade_item = \grade_item::fetch([
                        'courseid' => $this->cm->course,
                        'itemtype' => 'mod',
                        'itemmodule' => 'assign',
                        'iteminstance' => $this->assignment->id
                ]);

                if ($grade_item) {
                    foreach ($sessions as $session) {
                        $grade_grade = new \grade_grade(['itemid' => $grade_item->id, 'userid' => $session->userid], false);
                        if ($grade_grade->id && $grade_grade->overridden != 0) {
                            $overridden_userids[] = $session->userid;
                        }
                    }
                }
            }

            $graded_count = 0;
            $applied_grades = [];
            $errors = [];
            $skipped_count = 0;

            foreach ($sessions as $session) {
                if (in_array($session->userid, $overridden_userids)) {
                    $skipped_count++;
                    $errors[] = get_string('user_label', 'local_trustgrade') . " {$session->userid}: " .
                            get_string('grade_is_overridden', 'local_trustgrade');
                    continue;
                }

                // Calculate grade based on quiz score
                $quiz_grade = $this->calculate_grade_from_quiz_score($session);

                if ($quiz_grade !== null) {
                    $result = $this->save_grade($session->userid, $quiz_grade);

                    if ($result['success']) {
                        $graded_count++;
                        // Store as associative array for JSON encoding
                        $applied_grades[(string) $session->userid] = (float) $quiz_grade;
                    } else {
                        $errors[] = get_string('user_label', 'local_trustgrade') . " {$session->userid}: " . $result['message'];
                    }
                } else {
                    $errors[] = get_string('user_label', 'local_trustgrade') . " {$session->userid}: " .
                            get_string('error_calculate_grade_from_quiz', 'local_trustgrade');
                }
            }

            $message = get_string('auto_grade_success', 'local_trustgrade', $graded_count);
            if ($skipped_count > 0) {
                $message .= ' ' . get_string('auto_grade_skipped_overridden', 'local_trustgrade', $skipped_count);
            }

            if ($graded_count > 0) {
                return [
                        'success' => true,
                        'graded_count' => $graded_count,
                        'skipped_count' => $skipped_count,
                        'grades' => $applied_grades,
                        'message' => $message,
                        'errors' => $errors
                ];
            } else {
                return [
                        'success' => false,
                        'message' => get_string('auto_grade_no_grades', 'local_trustgrade'),
                        'graded_count' => 0,
                        'skipped_count' => $skipped_count,
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
     * Calculate assignment grade from quiz score.
     * Grade is based on correct answers / total questions (not points).
     *
     * @param \stdClass $session Quiz session object
     * @return float|null Calculated grade or null if cannot calculate
     */
    private function calculate_grade_from_quiz_score($session) {
        $questions = (array) $session->questions_data;
        $answers = (array) $session->answers_data;

        if (empty($questions)) {
            return null;
        }

        $total_questions = count($questions);
        $correct_count = $this->count_session_correct_answers($questions, $answers);

        return $this->grade_from_ratio($correct_count, $total_questions);
    }

    /**
     * Get plain-text answer and correctness for one question (for export).
     *
     * @param \stdClass $question Question object
     * @param mixed $user_answer User's answer
     * @return array{answer_text: string, is_correct: bool}
     */
    public function get_question_answer_export_info($question, $user_answer) {
        $result = ['answer_text' => '', 'is_correct' => false];
        if ($user_answer === null || $user_answer === '') {
            return $result;
        }
        $type = isset($question->type) ? $question->type : 'multiple_choice';
        if ($type === 'multiple_choice') {
            $raw_options = isset($question->options) ? $question->options : [];
            if (is_object($raw_options)) {
                $raw_options = (array) $raw_options;
            }
            $raw_options = array_values($raw_options);
            $opt_count = count($raw_options);
            if ($opt_count === 0) {
                return $result;
            }
            $order = $this->get_session_display_order($question, $user_answer, $opt_count);
            $selected_display = is_object($user_answer) || is_array($user_answer)
                ? ($user_answer['index'] ?? $user_answer['selectedIndex'] ?? $user_answer['answer'] ?? null)
                : $user_answer;
            if (!is_numeric($selected_display)) {
                return $result;
            }
            $selected_display = (int) $selected_display;
            $base_index = isset($order[$selected_display]) ? (int) $order[$selected_display] : null;
            if ($base_index === null || $base_index < 0 || $base_index >= $opt_count) {
                return $result;
            }
            $opt = is_array($raw_options[$base_index]) ? (object) $raw_options[$base_index] : $raw_options[$base_index];
            $result['answer_text'] = isset($opt->text) ? (string) $opt->text : (isset($opt->label) ? (string) $opt->label : '');
            $result['is_correct'] = !empty($opt->is_correct) || !empty($opt->correct) || !empty($opt->isCorrect);
            return $result;
        }
        if ($type === 'true_false') {
            $result['is_correct'] = $this->is_session_answer_correct($question, $user_answer);
            if ($user_answer === true || $user_answer === 'true' || $user_answer === 1 || $user_answer === '1') {
                $result['answer_text'] = get_string('true', 'local_trustgrade');
            } else if ($user_answer === false || $user_answer === 'false' || $user_answer === 0 || $user_answer === '0') {
                $result['answer_text'] = get_string('false', 'local_trustgrade');
            }
            return $result;
        }
        $result['is_correct'] = $this->is_session_answer_correct($question, $user_answer);
        $result['answer_text'] = is_string($user_answer) ? $user_answer : (string) json_encode($user_answer);
        return $result;
    }

    /**
     * Get correct count and total questions for a session (for display/API).
     *
     * @param \stdClass $session Quiz session object
     * @return array{correct: int, total: int}
     */
    public function get_session_score_ratio($session) {
        $questions = (array) $session->questions_data;
        $answers = (array) $session->answers_data;
        $total = count($questions);
        $correct = $this->count_session_correct_answers($questions, $answers);
        return ['correct' => $correct, 'total' => $total];
    }

    /**
     * Convert correct/total ratio to assignment grade.
     *
     * @param int $correct_count
     * @param int $total_questions
     * @return float|null
     */
    private function grade_from_ratio($correct_count, $total_questions) {
        if ($total_questions <= 0) {
            return null;
        }
        $percentage = $correct_count / $total_questions;
        $assignment_grade = $percentage * $this->max_grade;
        $assignment_grade = round($assignment_grade, 2);
        return max(0, min($assignment_grade, $this->max_grade));
    }

    /**
     * Count correct answers from questions and answers arrays (session data).
     *
     * @param array $questions Array of question objects
     * @param array $answers Array of answers keyed by question index
     * @return int Number of correct answers
     */
    private function count_session_correct_answers($questions, $answers) {
        $correct = 0;
        foreach ($questions as $index => $question) {
            $q = is_array($question) ? (object) $question : $question;
            $user_answer = isset($answers[$index]) ? $answers[$index] : null;
            if ($this->is_session_answer_correct($q, $user_answer)) {
                $correct++;
            }
        }
        return $correct;
    }

    /**
     * Check if a single answer is correct (matches report_renderer logic for grading).
     * Supports display order (shuffled options) from question or user_answer.
     *
     * @param \stdClass $question Question object
     * @param mixed $user_answer User's answer (display index or object with index/selectedIndex/answer)
     * @return bool
     */
    private function is_session_answer_correct($question, $user_answer) {
        if ($user_answer === null || $user_answer === '') {
            return false;
        }
        $type = isset($question->type) ? $question->type : 'multiple_choice';
        if ($type === 'multiple_choice') {
            $raw_options = isset($question->options) ? $question->options : [];
            if (is_object($raw_options)) {
                $raw_options = (array) $raw_options;
            }
            $raw_options = array_values($raw_options);
            $opt_count = count($raw_options);
            if ($opt_count === 0) {
                return false;
            }
            $order = $this->get_session_display_order($question, $user_answer, $opt_count);
            $selected_display = is_object($user_answer) || is_array($user_answer)
                ? ($user_answer['index'] ?? $user_answer['selectedIndex'] ?? $user_answer['answer'] ?? null)
                : $user_answer;
            if (!is_numeric($selected_display)) {
                return false;
            }
            $selected_display = (int) $selected_display;
            $base_index = isset($order[$selected_display]) ? (int) $order[$selected_display] : null;
            if ($base_index === null || $base_index < 0 || $base_index >= $opt_count) {
                return false;
            }
            $opt = is_array($raw_options[$base_index]) ? (object) $raw_options[$base_index] : $raw_options[$base_index];
            return !empty($opt->is_correct) || !empty($opt->correct) || !empty($opt->isCorrect);
        }
        if ($type === 'true_false') {
            $user_bool = $user_answer === true || $user_answer === 'true' || $user_answer === 1 || $user_answer === '1'
                ? true : ($user_answer === false || $user_answer === 'false' || $user_answer === 0 || $user_answer === '0' ? false : null);
            return $user_bool !== null && isset($question->correct_answer) && $user_bool === (bool) $question->correct_answer;
        }
        return false;
    }

    /**
     * Get display order for options (display index -> base index). Matches report_renderer get_display_order.
     *
     * @param \stdClass $question
     * @param mixed $user_answer
     * @param int $count
     * @return array<int,int>
     */
    private function get_session_display_order($question, $user_answer, $count) {
        $order = null;
        if (is_object($user_answer) || is_array($user_answer)) {
            $ua = is_array($user_answer) ? (object) $user_answer : $user_answer;
            if (isset($ua->order) && is_array($ua->order)) {
                $order = $ua->order;
            } else if (isset($ua->options_order) && is_array($ua->options_order)) {
                $order = $ua->options_order;
            } else if (isset($ua->shuffled_order) && is_array($ua->shuffled_order)) {
                $order = $ua->shuffled_order;
            }
        }
        if ($order === null && is_object($question)) {
            if (isset($question->order) && is_array($question->order)) {
                $order = $question->order;
            } else if (isset($question->options_order) && is_array($question->options_order)) {
                $order = $question->options_order;
            } else if (isset($question->shuffled_order) && is_array($question->shuffled_order)) {
                $order = $question->shuffled_order;
            }
        }
        if (!is_array($order) || empty($order)) {
            $order = [];
            for ($i = 0; $i < $count; $i++) {
                $order[$i] = $i;
            }
        }
        $order = array_values(array_map('intval', $order));
        foreach ($order as $i => $v) {
            if ($v < 0 || $v >= $count) {
                $order[$i] = $i;
            }
        }
        return $order;
    }

    /**
     * Get current grades for specified users
     *
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
            if ($grade->grade >= 0) {
                // Store as associative array for JSON encoding
                $result[(string) $grade->userid] = (float) $grade->grade;
            }
            // If grade is < 0, don't include it in the result (will be empty/null)
        }

        return $result;
    }

    /**
     * Update gradebook with new grade
     *
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
            $feedback = get_string('grade_feedback_trustgrade', 'local_trustgrade');
            $grade_item->update_raw_grade($userid, $grade, 'trustgrade', $feedback);
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
     *
     * @param int $cmid Course module ID
     * @param array $userids Array of user IDs
     * @return array Array of grades indexed by user ID
     */
    public static function get_current_grades_static($cmid, $userids = []) {
        $manager = new self($cmid);
        return $manager->get_current_grades($userids);
    }
}
