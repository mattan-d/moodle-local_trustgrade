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
 * Scheduled task to retry failed async question generation tasks.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\task;

use local_trustgrade\async_task_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to retry failed async question generation tasks.
 */
class retry_failed_async_tasks extends \core\task\scheduled_task {

    /**
     * Get task name
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_retry_failed_async_tasks', 'local_trustgrade');
    }

    /**
     * Execute the task
     */
    public function execute() {
        global $DB;

        mtrace('Starting retry of failed async tasks...');

        // Find tasks that are ready for retry
        $now = time();
        $sql = "SELECT * FROM {local_trustgd_async_tasks}
                WHERE status IN ('pending', 'processing')
                  AND error_message IS NOT NULL
                  AND attempts < 3
                  AND (next_retry_time IS NULL OR next_retry_time <= :now)
                ORDER BY timecreated ASC";

        $tasks = $DB->get_records_sql($sql, ['now' => $now]);

        mtrace('Found ' . count($tasks) . ' tasks ready for retry');

        foreach ($tasks as $task) {
            mtrace('Retrying task ID: ' . $task->id . ' (Attempt ' . ($task->attempts + 1) . '/3)');

            try {
                // Queue adhoc task to process this specific task
                $adhoctask = new \local_trustgrade\task\process_async_tasks();
                $adhoctask->set_custom_data([
                    'task_id' => $task->id
                ]);
                \core\task\manager::queue_adhoc_task($adhoctask);

                mtrace('Successfully queued retry for task ID: ' . $task->id);

            } catch (\Exception $e) {
                mtrace('Error queuing retry for task ID ' . $task->id . ': ' . $e->getMessage());
            }
        }

        mtrace('Retry task completed');
    }
}
