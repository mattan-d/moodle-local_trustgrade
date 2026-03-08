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
 * Database upgrade script for the TrustGrade plugin.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_trustgrade_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025081702) {
        $table = new xmldb_table('local_trustgd_quiz_settings');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'cmid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025081702, 'local', 'trustgrade');
    }

    if ($oldversion < 2025120402) {
        $table = new xmldb_table('local_trustgrade_questions');
        $field = new xmldb_field('is_mandatory', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'question_data');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for is_mandatory field
        $index = new xmldb_index('is_mandatory', XMLDB_INDEX_NOTUNIQUE, ['is_mandatory']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025120402, 'local', 'trustgrade');
    }

    if ($oldversion < 2025122901) {
        $table = new xmldb_table('local_trustgd_async_tasks');
        
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('submission_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
            $table->add_field('submission_content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('assignment_instructions', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('questions_count', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '3');
            $table->add_field('result_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('attempts', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
            $table->add_key('submission_id', XMLDB_KEY_FOREIGN, ['submission_id'], 'assign_submission', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $table->add_index('cmid_submission', XMLDB_INDEX_NOTUNIQUE, ['cmid', 'submission_id']);
            $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025122901, 'local', 'trustgrade');
    }

    if ($oldversion < 2026010200) {
        $table = new xmldb_table('local_trustgd_async_tasks');
        $field = new xmldb_field('next_retry_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'attempts');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for next_retry_time field
        $index = new xmldb_index('next_retry_time', XMLDB_INDEX_NOTUNIQUE, ['next_retry_time']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026010200, 'local', 'trustgrade');
    }

    if ($oldversion < 2026010201) {
        // Drop the debug cache table if it exists
        $table = new xmldb_table('local_trustgrade_debug');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Remove debug_mode configuration setting
        unset_config('debug_mode', 'local_trustgrade');

        upgrade_plugin_savepoint(true, 2026010201, 'local', 'trustgrade');
    }

    if ($oldversion < 2026021001) {
        $table = new xmldb_table('local_trustgd_inst_gen_tasks');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('instructions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('questions_count', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '5');
        $table->add_field('files_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('cmid_userid', XMLDB_INDEX_NOTUNIQUE, ['cmid', 'userid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026021001, 'local', 'trustgrade');
    }

    // Ensure require_quiz_completion exists (e.g. if upgrade 2026021700 was skipped or DB was from before that block).
    if ($oldversion < 2026030800) {
        $table = new xmldb_table('local_trustgd_quiz_settings');
        $field = new xmldb_field('require_quiz_completion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'show_countdown');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026030800, 'local', 'trustgrade');
    }

    return true;
}
