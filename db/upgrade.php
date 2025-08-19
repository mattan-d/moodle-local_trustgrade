<?php
// This file is part of Moodle - http://moodle.org/

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

    if ($oldversion < 2025081703) {
        $table = new xmldb_table('local_trustgd_quiz_sessions');
        $field = new xmldb_field('archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timecompleted');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update the unique index to allow multiple sessions per user/submission
        $index = new xmldb_index('cmid_submission_user', XMLDB_INDEX_UNIQUE, ['cmid', 'submissionid', 'userid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Add new index that includes archived field to allow multiple active sessions
        $new_index = new xmldb_index('cmid_submission_user_archived', XMLDB_INDEX_NOTUNIQUE, ['cmid', 'submissionid', 'userid', 'archived']);
        if (!$dbman->index_exists($table, $new_index)) {
            $dbman->add_index($table, $new_index);
        }

        upgrade_plugin_savepoint(true, 2025081703, 'local', 'trustgrade');
    }

    return true;
}
