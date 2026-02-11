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
 * Web service definitions for the TrustGrade plugin.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Grading service functions
    'local_trustgrade_save_grade' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'save_grade',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Save a single grade for a student.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
    'local_trustgrade_save_bulk_grades' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'save_bulk_grades',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Save multiple grades in bulk.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
    'local_trustgrade_clear_all_grades' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'clear_all_grades',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Clear all grades for an assignment.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
    'local_trustgrade_get_current_grades' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'get_current_grades',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Get current grades for a list of users.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
    'local_trustgrade_auto_grade_by_quiz' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'auto_grade_by_quiz',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Auto-grade all students based on quiz scores.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],

    // Main TrustGrade functions
    'local_trustgrade_check_instructions' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'check_instructions',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Check assignment instructions with AI.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_generate_questions' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'generate_questions',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Generate quiz questions from instructions.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_save_question' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'save_question',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Save a single question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_delete_question' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'delete_question',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Delete a single question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_get_question_bank' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'get_question_bank',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Get the question bank for an assignment.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_update_quiz_setting' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'update_quiz_setting',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Update a single quiz setting.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],
    'local_trustgrade_toggle_mandatory_question' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'toggle_mandatory_question',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Toggle mandatory status for a question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:addinstance',
    ],

    // Quiz session functions
    'local_trustgrade_start_quiz_attempt' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'start_quiz_attempt',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Starts a quiz attempt for the current user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],
    'local_trustgrade_update_quiz_session' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'update_quiz_session',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Updates the state of the current quiz session.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],
    'local_trustgrade_complete_quiz_session' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'complete_quiz_session',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Completes the quiz session and saves final answers.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],
    'local_trustgrade_log_integrity_violation' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'log_integrity_violation',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Logs an academic integrity violation during a quiz.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],

    'local_trustgrade_get_pending_tasks' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'get_pending_tasks',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Get pending async tasks for current user.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],
    'local_trustgrade_has_pending_tasks' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'has_pending_tasks',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Check if current user has any pending async tasks.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
    ],
    'local_trustgrade_get_quiz_grades_for_grading' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'get_quiz_grades_for_grading',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Get TrustGrade quiz grades for assignment grading table.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
    'local_trustgrade_get_instructor_generation_status' => [
        'classname' => 'local_trustgrade\external',
        'methodname' => 'get_instructor_generation_status',
        'classpath' => 'local/trustgrade/classes/external.php',
        'description' => 'Get status of instructor question generation from files for question bank.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:grade',
    ],
];

$services = [
    'TrustGrade Service' => [
        'functions' => [
            // Grading functions
            'local_trustgrade_save_grade',
            'local_trustgrade_save_bulk_grades',
            'local_trustgrade_clear_all_grades',
            'local_trustgrade_get_current_grades',
            'local_trustgrade_auto_grade_by_quiz',
            // Main AI functions
            'local_trustgrade_check_instructions',
            'local_trustgrade_generate_questions',
            'local_trustgrade_save_question',
            'local_trustgrade_delete_question',
            'local_trustgrade_get_question_bank',
            'local_trustgrade_update_quiz_setting',
            // Quiz session functions
            'local_trustgrade_start_quiz_attempt',
            'local_trustgrade_update_quiz_session',
            'local_trustgrade_complete_quiz_session',
            'local_trustgrade_log_integrity_violation',
            'local_trustgrade_get_pending_tasks',
            'local_trustgrade_has_pending_tasks',
            'local_trustgrade_get_quiz_grades_for_grading',
            'local_trustgrade_get_instructor_generation_status',
            'local_trustgrade_toggle_mandatory_question',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_trustgrade_service',
    ]
];
