<?php
// This file is part of Moodle - http://moodle.org/

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
    'local_trustgrade_check_task_status' => [
        'classname' => 'local_trustgrade\external\check_task_status',
        'methodname' => 'execute',
        'classpath' => 'local/trustgrade/classes/external/check_task_status.php',
        'description' => 'Check the status of a submission processing task.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/assign:submit',
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
            'local_trustgrade_check_task_status',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_trustgrade_service',
    ]
];
