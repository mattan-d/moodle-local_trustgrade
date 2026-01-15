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
 * Adhoc task for processing a single async question generation task.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Process a single async question generation task
 */
class process_async_tasks extends \core\task\adhoc_task {

    /**
     * Execute the task
     */
    public function execute() {
        \core_php_time_limit::raise(300);
        raise_memory_limit(MEMORY_HUGE);
        
        $data = $this->get_custom_data();
        
        if (!isset($data->task_id)) {
            debugging('TrustGrade: Adhoc task missing task_id', DEBUG_DEVELOPER);
            return;
        }

        $manager = new \local_trustgrade\async_task_manager();
        $manager->process_task_by_id($data->task_id);
    }
}
