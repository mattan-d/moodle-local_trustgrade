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
 * Privacy Subsystem implementation for local_trustgrade.
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the TrustGrade plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Table: local_trustgrade_logs
        $collection->add_database_table(
            'local_trustgrade_logs',
            [
                'userid' => 'privacy:metadata:local_trustgrade_logs:userid',
                'cmid' => 'privacy:metadata:local_trustgrade_logs:cmid',
                'instructions' => 'privacy:metadata:local_trustgrade_logs:instructions',
                'recommendation' => 'privacy:metadata:local_trustgrade_logs:recommendation',
                'timecreated' => 'privacy:metadata:local_trustgrade_logs:timecreated',
            ],
            'privacy:metadata:local_trustgrade_logs'
        );

        // Table: local_trustgrade_questions
        $collection->add_database_table(
            'local_trustgrade_questions',
            [
                'cmid' => 'privacy:metadata:local_trustgrade_questions:cmid',
                'userid' => 'privacy:metadata:local_trustgrade_questions:userid',
                'question_data' => 'privacy:metadata:local_trustgrade_questions:question_data',
                'timecreated' => 'privacy:metadata:local_trustgrade_questions:timecreated',
                'timemodified' => 'privacy:metadata:local_trustgrade_questions:timemodified',
            ],
            'privacy:metadata:local_trustgrade_questions'
        );

        // Table: local_trustgd_sub_questions
        $collection->add_database_table(
            'local_trustgd_sub_questions',
            [
                'submission_id' => 'privacy:metadata:local_trustgd_sub_questions:submission_id',
                'cmid' => 'privacy:metadata:local_trustgd_sub_questions:cmid',
                'userid' => 'privacy:metadata:local_trustgd_sub_questions:userid',
                'question_data' => 'privacy:metadata:local_trustgd_sub_questions:question_data',
                'timecreated' => 'privacy:metadata:local_trustgd_sub_questions:timecreated',
                'timemodified' => 'privacy:metadata:local_trustgd_sub_questions:timemodified',
            ],
            'privacy:metadata:local_trustgd_sub_questions'
        );

        // Table: local_trustgrade_debug
        $collection->add_database_table(
            'local_trustgrade_debug',
            [
                'userid' => 'privacy:metadata:local_trustgrade_debug:userid',
                'cmid' => 'privacy:metadata:local_trustgrade_debug:cmid',
                'request_type' => 'privacy:metadata:local_trustgrade_debug:request_type',
                'request_data' => 'privacy:metadata:local_trustgrade_debug:request_data',
                'raw_response' => 'privacy:metadata:local_trustgrade_debug:raw_response',
                'parsed_response' => 'privacy:metadata:local_trustgrade_debug:parsed_response',
                'timecreated' => 'privacy:metadata:local_trustgrade_debug:timecreated',
            ],
            'privacy:metadata:local_trustgrade_debug'
        );

        // Table: local_trustgd_quiz_sessions
        $collection->add_database_table(
            'local_trustgd_quiz_sessions',
            [
                'cmid' => 'privacy:metadata:local_trustgd_quiz_sessions:cmid',
                'submissionid' => 'privacy:metadata:local_trustgd_quiz_sessions:submissionid',
                'userid' => 'privacy:metadata:local_trustgd_quiz_sessions:userid',
                'questions_data' => 'privacy:metadata:local_trustgd_quiz_sessions:questions_data',
                'settings_data' => 'privacy:metadata:local_trustgd_quiz_sessions:settings_data',
                'current_question' => 'privacy:metadata:local_trustgd_quiz_sessions:current_question',
                'answers_data' => 'privacy:metadata:local_trustgd_quiz_sessions:answers_data',
                'time_remaining' => 'privacy:metadata:local_trustgd_quiz_sessions:time_remaining',
                'window_blur_count' => 'privacy:metadata:local_trustgd_quiz_sessions:window_blur_count',
                'attempt_started' => 'privacy:metadata:local_trustgd_quiz_sessions:attempt_started',
                'attempt_completed' => 'privacy:metadata:local_trustgd_quiz_sessions:attempt_completed',
                'integrity_violations' => 'privacy:metadata:local_trustgd_quiz_sessions:integrity_violations',
                'final_score' => 'privacy:metadata:local_trustgd_quiz_sessions:final_score',
                'timecreated' => 'privacy:metadata:local_trustgd_quiz_sessions:timecreated',
                'timemodified' => 'privacy:metadata:local_trustgd_quiz_sessions:timemodified',
                'timecompleted' => 'privacy:metadata:local_trustgd_quiz_sessions:timecompleted',
            ],
            'privacy:metadata:local_trustgd_quiz_sessions'
        );

        // External location: AI Gateway
        $collection->add_external_location_link(
            'ai_gateway',
            [
                'userid' => 'privacy:metadata:ai_gateway:userid',
                'instructions' => 'privacy:metadata:ai_gateway:instructions',
                'submission_text' => 'privacy:metadata:ai_gateway:submission_text',
                'files' => 'privacy:metadata:ai_gateway:files',
                'metadata' => 'privacy:metadata:ai_gateway:metadata',
            ],
            'privacy:metadata:ai_gateway'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get contexts from logs table
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_trustgrade_logs} l ON l.cmid = cm.id
                 WHERE l.userid = :userid1";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
        ]);

        // Get contexts from questions table
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_trustgrade_questions} q ON q.cmid = cm.id
                 WHERE q.userid = :userid2";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid2' => $userid,
        ]);

        // Get contexts from submission questions table
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_trustgd_sub_questions} sq ON sq.cmid = cm.id
                 WHERE sq.userid = :userid3";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid3' => $userid,
        ]);

        // Get contexts from quiz sessions table
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_trustgd_quiz_sessions} qs ON qs.cmid = cm.id
                 WHERE qs.userid = :userid4";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid4' => $userid,
        ]);

        // Get contexts from debug table
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {local_trustgrade_debug} d ON d.cmid = cm.id
                 WHERE d.userid = :userid5";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid5' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Get users from logs table
        $sql = "SELECT l.userid
                  FROM {course_modules} cm
                  JOIN {local_trustgrade_logs} l ON l.cmid = cm.id
                 WHERE cm.id = :cmid1";

        $userlist->add_from_sql('userid', $sql, ['cmid1' => $context->instanceid]);

        // Get users from questions table
        $sql = "SELECT q.userid
                  FROM {course_modules} cm
                  JOIN {local_trustgrade_questions} q ON q.cmid = cm.id
                 WHERE cm.id = :cmid2";

        $userlist->add_from_sql('userid', $sql, ['cmid2' => $context->instanceid]);

        // Get users from submission questions table
        $sql = "SELECT sq.userid
                  FROM {course_modules} cm
                  JOIN {local_trustgd_sub_questions} sq ON sq.cmid = cm.id
                 WHERE cm.id = :cmid3";

        $userlist->add_from_sql('userid', $sql, ['cmid3' => $context->instanceid]);

        // Get users from quiz sessions table
        $sql = "SELECT qs.userid
                  FROM {course_modules} cm
                  JOIN {local_trustgd_quiz_sessions} qs ON qs.cmid = cm.id
                 WHERE cm.id = :cmid4";

        $userlist->add_from_sql('userid', $sql, ['cmid4' => $context->instanceid]);

        // Get users from debug table
        $sql = "SELECT d.userid
                  FROM {course_modules} cm
                  JOIN {local_trustgrade_debug} d ON d.cmid = cm.id
                 WHERE cm.id = :cmid5";

        $userlist->add_from_sql('userid', $sql, ['cmid5' => $context->instanceid]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cmid = $context->instanceid;

            // Export logs data
            $logs = $DB->get_records('local_trustgrade_logs', ['userid' => $userid, 'cmid' => $cmid]);
            if ($logs) {
                $logsdata = array_map(function($log) {
                    return (object)[
                        'instructions' => $log->instructions,
                        'recommendation' => $log->recommendation,
                        'timecreated' => \core_privacy\local\request\transform::datetime($log->timecreated),
                    ];
                }, $logs);

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:logs', 'local_trustgrade')],
                    (object)['logs' => $logsdata]
                );
            }

            // Export questions data
            $questions = $DB->get_records('local_trustgrade_questions', ['userid' => $userid, 'cmid' => $cmid]);
            if ($questions) {
                $questionsdata = array_map(function($question) {
                    return (object)[
                        'question_data' => $question->question_data,
                        'timecreated' => \core_privacy\local\request\transform::datetime($question->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($question->timemodified),
                    ];
                }, $questions);

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:questions', 'local_trustgrade')],
                    (object)['questions' => $questionsdata]
                );
            }

            // Export submission questions data
            $subquestions = $DB->get_records('local_trustgd_sub_questions', ['userid' => $userid, 'cmid' => $cmid]);
            if ($subquestions) {
                $subquestionsdata = array_map(function($sq) {
                    return (object)[
                        'submission_id' => $sq->submission_id,
                        'question_data' => $sq->question_data,
                        'timecreated' => \core_privacy\local\request\transform::datetime($sq->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($sq->timemodified),
                    ];
                }, $subquestions);

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:submission_questions', 'local_trustgrade')],
                    (object)['submission_questions' => $subquestionsdata]
                );
            }

            // Export quiz sessions data
            $quizsessions = $DB->get_records('local_trustgd_quiz_sessions', ['userid' => $userid, 'cmid' => $cmid]);
            if ($quizsessions) {
                $quizsessionsdata = array_map(function($qs) {
                    return (object)[
                        'submissionid' => $qs->submissionid,
                        'questions_data' => $qs->questions_data,
                        'settings_data' => $qs->settings_data,
                        'current_question' => $qs->current_question,
                        'answers_data' => $qs->answers_data,
                        'time_remaining' => $qs->time_remaining,
                        'window_blur_count' => $qs->window_blur_count,
                        'attempt_started' => $qs->attempt_started,
                        'attempt_completed' => $qs->attempt_completed,
                        'integrity_violations' => $qs->integrity_violations,
                        'final_score' => $qs->final_score,
                        'timecreated' => \core_privacy\local\request\transform::datetime($qs->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($qs->timemodified),
                        'timecompleted' => $qs->timecompleted ? \core_privacy\local\request\transform::datetime($qs->timecompleted) : null,
                    ];
                }, $quizsessions);

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:quiz_sessions', 'local_trustgrade')],
                    (object)['quiz_sessions' => $quizsessionsdata]
                );
            }

            // Export debug data
            $debugdata = $DB->get_records('local_trustgrade_debug', ['userid' => $userid, 'cmid' => $cmid]);
            if ($debugdata) {
                $debugdataarray = array_map(function($debug) {
                    return (object)[
                        'request_type' => $debug->request_type,
                        'request_data' => $debug->request_data,
                        'raw_response' => $debug->raw_response,
                        'parsed_response' => $debug->parsed_response,
                        'timecreated' => \core_privacy\local\request\transform::datetime($debug->timecreated),
                    ];
                }, $debugdata);

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:debug', 'local_trustgrade')],
                    (object)['debug_data' => $debugdataarray]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;

        // Delete all data related to this course module
        $DB->delete_records('local_trustgrade_logs', ['cmid' => $cmid]);
        $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);
        $DB->delete_records('local_trustgd_sub_questions', ['cmid' => $cmid]);
        $DB->delete_records('local_trustgd_quiz_sessions', ['cmid' => $cmid]);
        $DB->delete_records('local_trustgrade_debug', ['cmid' => $cmid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cmid = $context->instanceid;

            // Delete user data from all tables
            $DB->delete_records('local_trustgrade_logs', ['userid' => $userid, 'cmid' => $cmid]);
            $DB->delete_records('local_trustgrade_questions', ['userid' => $userid, 'cmid' => $cmid]);
            $DB->delete_records('local_trustgd_sub_questions', ['userid' => $userid, 'cmid' => $cmid]);
            $DB->delete_records('local_trustgd_quiz_sessions', ['userid' => $userid, 'cmid' => $cmid]);
            $DB->delete_records('local_trustgrade_debug', ['userid' => $userid, 'cmid' => $cmid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cmid = $context->instanceid;
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete data for specified users
        $DB->delete_records_select('local_trustgrade_logs', "cmid = :cmid AND userid {$usersql}", 
            array_merge(['cmid' => $cmid], $userparams));
        $DB->delete_records_select('local_trustgrade_questions', "cmid = :cmid AND userid {$usersql}", 
            array_merge(['cmid' => $cmid], $userparams));
        $DB->delete_records_select('local_trustgd_sub_questions', "cmid = :cmid AND userid {$usersql}", 
            array_merge(['cmid' => $cmid], $userparams));
        $DB->delete_records_select('local_trustgd_quiz_sessions', "cmid = :cmid AND userid {$usersql}", 
            array_merge(['cmid' => $cmid], $userparams));
        $DB->delete_records_select('local_trustgrade_debug', "cmid = :cmid AND userid {$usersql}", 
            array_merge(['cmid' => $cmid], $userparams));
    }
}
