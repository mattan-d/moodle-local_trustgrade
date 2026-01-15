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
 * Async task manager for handling question generation in background.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class for async question generation tasks
 */
class async_task_manager {

    /**
     * Create a new async task
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     * @param int $userid User ID
     * @param array $submission_content Submission content
     * @param array $assignment_instructions Assignment instructions
     * @param int $questions_count Number of questions to generate
     * @return int Task ID
     */
    public static function create_task($cmid, $submission_id, $userid, $submission_content, $assignment_instructions, $questions_count) {
        global $DB;

        $task = new \stdClass();
        $task->cmid = $cmid;
        $task->submission_id = $submission_id;
        $task->userid = $userid;
        $task->status = 'pending';
        $task->submission_content = json_encode($submission_content);
        $task->assignment_instructions = json_encode($assignment_instructions);
        $task->questions_count = $questions_count;
        $task->attempts = 0;
        $task->timecreated = time();
        $task->timemodified = time();
        $task->next_retry_time = null; // Initialize retry time

        $task_id = $DB->insert_record('local_trustgd_async_tasks', $task);
        
        debugging('TrustGrade: Created async task ID ' . $task_id . ' for submission ' . $submission_id, DEBUG_DEVELOPER);
        
        self::queue_adhoc_task($task_id);
        
        return $task_id;
    }

    /**
     * Queue an adhoc task to process a specific task
     *
     * @param int $task_id Task ID
     */
    private static function queue_adhoc_task($task_id) {
        $task = new \local_trustgrade\task\process_async_tasks();
        $task->set_custom_data([
            'task_id' => $task_id
        ]);
        \core\task\manager::queue_adhoc_task($task);
        
        debugging('TrustGrade: Queued adhoc task for task ID ' . $task_id, DEBUG_DEVELOPER);
    }

    /**
     * Process pending tasks (called by scheduled task)
     *
     * @param int $limit Maximum number of tasks to process
     */
    public function process_pending_tasks($limit = 5) {
        global $DB;

        $tasks = $DB->get_records('local_trustgd_async_tasks', 
            ['status' => 'pending'], 
            'timecreated ASC', 
            '*', 
            0, 
            $limit
        );

        foreach ($tasks as $task) {
            $this->process_task($task);
        }
    }

    /**
     * Process a single task
     *
     * @param object $task Task record
     */
    public function process_task($task) {
        global $DB;

        \core_php_time_limit::raise(HOURSECS);
        raise_memory_limit(MEMORY_EXTRA);

        try {
            debugging('TrustGrade: Processing async task ID ' . $task->id . ' (attempt ' . ($task->attempts + 1) . '/3)', DEBUG_DEVELOPER);

            $task->status = 'processing';
            $task->attempts++;
            $task->timemodified = time();
            $task->next_retry_time = null; // Clear retry time when processing
            $DB->update_record('local_trustgd_async_tasks', $task);

            // Decode content
            $submission_content = json_decode($task->submission_content, true);
            $assignment_instructions = json_decode($task->assignment_instructions, true);

            // Generate questions
            $result = submission_processor::generate_submission_questions_with_count(
                $submission_content,
                $assignment_instructions,
                $task->questions_count,
                $task->cmid,
                $task->userid
            );

            if ($result['success']) {
                debugging('TrustGrade: Successfully generated questions for task ' . $task->id, DEBUG_DEVELOPER);

                // Save questions
                submission_processor::save_submission_questions(
                    $task->submission_id,
                    $task->cmid,
                    $result['questions']
                );

                // Create quiz session
                quiz_session::create_session_on_submission_update(
                    $task->cmid,
                    $task->submission_id,
                    $task->userid
                );

                $task->status = 'completed';
                $task->result_data = json_encode($result);
                $task->error_message = null; // Clear any previous error
                $task->timecompleted = time();
                $task->timemodified = time();
                $DB->update_record('local_trustgd_async_tasks', $task);

                // Send notification to user
                $this->send_completion_notification($task);

                debugging('TrustGrade: Task ' . $task->id . ' completed successfully', DEBUG_DEVELOPER);
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            debugging('TrustGrade: Task ' . $task->id . ' failed on attempt ' . $task->attempts . ': ' . $e->getMessage(), DEBUG_DEVELOPER);

            if ($task->attempts < 3) {
                // Retry delays: Attempt 1->2: 1 minute, Attempt 2->3: 2 minutes
                $delay = $task->attempts * 60; // 60, 120 seconds
                
                $task->status = 'pending';
                $task->error_message = $e->getMessage();
                $task->next_retry_time = time() + $delay;
                $task->timemodified = time();
                $DB->update_record('local_trustgd_async_tasks', $task);

                debugging('TrustGrade: Task ' . $task->id . ' scheduled for retry (attempt ' . ($task->attempts + 1) . '/3) at ' . userdate($task->next_retry_time), DEBUG_DEVELOPER);
            } else {
                $task->status = 'failed';
                $task->error_message = $e->getMessage();
                $task->next_retry_time = null;
                $task->timemodified = time();
                $DB->update_record('local_trustgd_async_tasks', $task);

                debugging('TrustGrade: Task ' . $task->id . ' failed permanently after 3 attempts', DEBUG_DEVELOPER);
                
                // Send failure notification
                $this->send_failure_notification($task);
            }
        }
    }

    /**
     * Process a task by ID (called by adhoc task)
     *
     * @param int $task_id Task ID
     */
    public function process_task_by_id($task_id) {
        global $DB;

        $task = $DB->get_record('local_trustgd_async_tasks', ['id' => $task_id]);
        
        if (!$task) {
            debugging('TrustGrade: Task ID ' . $task_id . ' not found', DEBUG_DEVELOPER);
            return;
        }

        $this->process_task($task);
    }

    /**
     * Send notification to user when task is completed
     *
     * @param object $task Task record
     */
    private function send_completion_notification($task) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $task->userid]);
        if (!$user) {
            return;
        }

        $cm = get_coursemodule_from_id('assign', $task->cmid);
        if (!$cm) {
            return;
        }

        $message = new \core\message\message();
        $message->component = 'local_trustgrade';
        $message->name = 'quizready';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('quiz_ready_subject', 'local_trustgrade');
        $message->fullmessage = get_string('quiz_ready_message', 'local_trustgrade');
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = get_string('quiz_ready_message_html', 'local_trustgrade', [
            'quizurl' => new \moodle_url('/local/trustgrade/quiz_interface.php', [
                'cmid' => $task->cmid,
                'submissionid' => $task->submission_id
            ])
        ]);
        $message->smallmessage = get_string('quiz_ready_subject', 'local_trustgrade');
        $message->notification = 1;
        $message->contexturl = new \moodle_url('/local/trustgrade/quiz_interface.php', [
            'cmid' => $task->cmid,
            'submissionid' => $task->submission_id
        ]);
        $message->contexturlname = get_string('take_quiz', 'local_trustgrade');

        message_send($message);
    }

    /**
     * Send notification to user when task fails
     *
     * @param object $task Task record
     */
    private function send_failure_notification($task) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $task->userid]);
        if (!$user) {
            return;
        }

        $message = new \core\message\message();
        $message->component = 'local_trustgrade';
        $message->name = 'quizfailed';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('quiz_generation_failed_subject', 'local_trustgrade');
        $message->fullmessage = get_string('quiz_generation_failed_message', 'local_trustgrade');
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = get_string('quiz_generation_failed_message', 'local_trustgrade');
        $message->smallmessage = get_string('quiz_generation_failed_subject', 'local_trustgrade');
        $message->notification = 1;

        message_send($message);
    }

    /**
     * Get task status
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     * @param int $userid User ID
     * @return object|null Task status or null if not found
     */
    public static function get_task_status($cmid, $submission_id, $userid) {
        global $DB;

        $task = $DB->get_record('local_trustgd_async_tasks', [
            'cmid' => $cmid,
            'submission_id' => $submission_id,
            'userid' => $userid
        ], '*', IGNORE_MULTIPLE);

        return $task;
    }

    /**
     * Check if there's a pending or processing task
     *
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID
     * @param int $userid User ID
     * @return bool True if task is in progress
     */
    public static function has_pending_task($cmid, $submission_id, $userid) {
        global $DB;

        return $DB->record_exists_select('local_trustgd_async_tasks',
            'cmid = ? AND submission_id = ? AND userid = ? AND status IN (?, ?)',
            [$cmid, $submission_id, $userid, 'pending', 'processing']
        );
    }

    /**
     * Get pending tasks and incomplete quizzes for current user
     *
     * @return array Array of pending tasks and quizzes
     */
    public static function get_user_pending_tasks() {
        global $DB, $USER;

        $result = [];
        $now = time();
        $twentyfour_hours_ago = $now - (24 * 60 * 60);

        $tasks = $DB->get_records_select('local_trustgd_async_tasks',
            'userid = ? AND status IN (?, ?) AND timecreated > ?',
            [$USER->id, 'pending', 'processing', $twentyfour_hours_ago],
            'timecreated DESC'
        );

        foreach ($tasks as $task) {
            $cm = get_coursemodule_from_id('assign', $task->cmid);
            if ($cm) {
                $result[] = [
                    'id' => $task->id,
                    'cmid' => $task->cmid,
                    'submission_id' => $task->submission_id,
                    'status' => 'preparing',
                    'assignment_name' => $cm->name,
                    'quiz_url' => null,
                    'timecreated' => $task->timecreated
                ];
            }
        }

        $sessions = $DB->get_records_select('local_trustgd_quiz_sessions',
            'userid = ? AND attempt_completed = 0 AND timecreated > ?',
            [$USER->id, $twentyfour_hours_ago],
            'timecreated DESC'
        );

        foreach ($sessions as $session) {
            $cm = get_coursemodule_from_id('assign', $session->cmid);
            if ($cm) {
                // Check if there's already a preparing task for this submission
                $has_preparing = false;
                foreach ($result as $item) {
                    if ($item['submission_id'] == $session->submissionid) {
                        $has_preparing = true;
                        break;
                    }
                }

                // Only add if no preparing task exists
                if (!$has_preparing) {
                    $result[] = [
                        'id' => $session->id,
                        'cmid' => $session->cmid,
                        'submission_id' => $session->submissionid,
                        'status' => 'ready',
                        'assignment_name' => $cm->name,
                        'quiz_url' => (new \moodle_url('/local/trustgrade/quiz_interface.php', [
                            'cmid' => $session->cmid,
                            'submissionid' => $session->submissionid
                        ]))->out(false),
                        'timecreated' => $session->timecreated
                    ];
                }
            }
        }

        usort($result, function($a, $b) {
            return $b['timecreated'] - $a['timecreated'];
        });

        return $result;
    }

    /**
     * Retry failed tasks (called by scheduled task)
     * This method finds tasks with error_message that are ready for retry
     *
     * @return int Number of tasks retried
     */
    public static function retry_failed_tasks() {
        global $DB;

        $now = time();
        
        $sql = "SELECT * FROM {local_trustgd_async_tasks}
                WHERE status = 'pending'
                  AND error_message IS NOT NULL
                  AND attempts > 0
                  AND attempts < 3
                  AND next_retry_time IS NOT NULL
                  AND next_retry_time <= :now
                ORDER BY next_retry_time ASC";
        
        $tasks = $DB->get_records_sql($sql, ['now' => $now]);

        debugging('TrustGrade: Found ' . count($tasks) . ' tasks ready for retry', DEBUG_DEVELOPER);

        foreach ($tasks as $task) {
            debugging('TrustGrade: Queueing adhoc task for retry of task ID ' . $task->id, DEBUG_DEVELOPER);
            
            $adhoctask = new \local_trustgrade\task\process_async_tasks();
            $adhoctask->set_custom_data([
                'task_id' => $task->id
            ]);
            \core\task\manager::queue_adhoc_task($adhoctask);
        }

        return count($tasks);
    }
}
