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
 * Manager for instructor question generation from files (adhoc, with status).
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates and processes "generate questions from files" tasks for the question bank.
 */
class instructor_generation_manager {

    /**
     * Create a new instructor generation task and queue adhoc.
     *
     * @param int $cmid Course module ID
     * @param int $userid User ID
     * @param string $instructions Optional instructions
     * @param int $questionscount Number of questions
     * @param array $files Array of ['filename', 'mimetype', 'size', 'content' (base64)]
     * @return int Task ID
     */
    public static function create_task($cmid, $userid, $instructions, $questionscount, array $files) {
        global $DB;

        $task = new \stdClass();
        $task->cmid = $cmid;
        $task->userid = $userid;
        $task->status = 'pending';
        $task->instructions = $instructions;
        $task->questions_count = max(1, min(50, (int) $questionscount));
        $task->files_data = json_encode($files);
        $task->error_message = null;
        $task->timecreated = time();
        $task->timemodified = time();
        $task->timecompleted = null;

        $taskid = $DB->insert_record('local_trustgd_inst_gen_tasks', $task);
        self::queue_adhoc_task($taskid);
        return $taskid;
    }

    /**
     * Queue adhoc task to process this instructor generation.
     *
     * @param int $taskid Task ID
     */
    private static function queue_adhoc_task($taskid) {
        $adhoc = new \local_trustgrade\task\process_instructor_generation();
        $adhoc->set_custom_data(['task_id' => $taskid]);
        \core\task\manager::queue_adhoc_task($adhoc);
    }

    /**
     * Process a task by ID (called by adhoc task).
     *
     * @param int $taskid Task ID
     */
    public static function process_task_by_id($taskid) {
        global $DB;

        $task = $DB->get_record('local_trustgd_inst_gen_tasks', ['id' => $taskid]);
        if (!$task) {
            return;
        }

        $DB->set_field('local_trustgd_inst_gen_tasks', 'status', 'processing', ['id' => $taskid]);
        $DB->set_field('local_trustgd_inst_gen_tasks', 'timemodified', time(), ['id' => $taskid]);

        $files = json_decode($task->files_data, true);
        if (!is_array($files)) {
            $files = [];
        }

        try {
            $gateway = new gateway_client();
            $result = $gateway->generateQuestions(
                $task->instructions ?? '',
                $task->questions_count,
                $files
            );

            if (empty($result['success']) || empty($result['data']['questions'])) {
                $err = $result['error'] ?? get_string('error_generating_questions', 'local_trustgrade');
                throw new \Exception($err);
            }

            $saved = question_generator::save_questions($task->cmid, $result['data']['questions']);
            if (!$saved) {
                throw new \Exception(get_string('error_saving_questions', 'local_trustgrade'));
            }

            $DB->update_record('local_trustgd_inst_gen_tasks', (object)[
                'id' => $taskid,
                'status' => 'ready',
                'error_message' => null,
                'timecompleted' => time(),
                'timemodified' => time(),
            ]);
        } catch (\Throwable $e) {
            $DB->update_record('local_trustgd_inst_gen_tasks', (object)[
                'id' => $taskid,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'timemodified' => time(),
            ]);
        }
    }

    /**
     * Get the latest instructor generation status for this cmid and current user.
     *
     * @param int $cmid Course module ID
     * @return array { has_task, status, message, error }
     */
    public static function get_status_for_cmid($cmid) {
        global $DB, $USER;

        $task = $DB->get_record_sql(
            "SELECT * FROM {local_trustgd_inst_gen_tasks}
             WHERE cmid = ? AND userid = ?
             ORDER BY timecreated DESC",
            [$cmid, $USER->id],
            0,
            1
        );

        if (!$task) {
            return [
                'has_task' => false,
                'status' => '',
                'message' => '',
                'error' => null,
            ];
        }

        $status = $task->status;
        $message = '';
        $error = $task->error_message;

        if ($status === 'pending' || $status === 'processing') {
            $message = get_string('generation_status_preparing', 'local_trustgrade');
        } elseif ($status === 'ready') {
            $message = get_string('questions_generated_success', 'local_trustgrade');
        } elseif ($status === 'failed') {
            $message = get_string('error_generating_questions', 'local_trustgrade');
        }

        return [
            'has_task' => true,
            'status' => $status,
            'message' => $message,
            'error' => $error,
        ];
    }
}
