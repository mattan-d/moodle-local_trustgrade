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
 * Enforces "require quiz completion" – blocks access to other Moodle pages until the quiz is completed (up to 24 hours).
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles blocking access until TrustGrade quiz is completed.
 */
class require_quiz_completion_handler {

    /** Block duration in seconds (24 hours). */
    const BLOCK_DURATION_SECONDS = 86400;

    /**
     * Get assignment cmids that require quiz completion and the user has not completed.
     * Only includes assignments where the user can view but cannot grade (student).
     *
     * @param int $userid User ID
     * @return int[] List of course module IDs
     */
    public static function get_pending_required_cmids($userid) {
        global $DB;

        // Skip if column not yet present (e.g. before upgrade has run).
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_trustgd_quiz_settings');
        $field = new \xmldb_field('require_quiz_completion', \XMLDB_TYPE_INTEGER, '1', null, \XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            return [];
        }

        $sql = "SELECT qs.cmid
                  FROM {local_trustgd_quiz_settings} qs
                  JOIN {course_modules} cm ON cm.id = qs.cmid
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                  WHERE qs.enabled = 1 AND qs.require_quiz_completion = 1";
        $all = $DB->get_records_sql($sql);
        if (empty($all)) {
            return [];
        }

        $pending = [];
        foreach ($all as $row) {
            $cmid = (int) $row->cmid;
            try {
                $cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
                if (!$cm) {
                    continue;
                }
                $context = \context_module::instance($cm->id);
                if (!has_capability('mod/assign:view', $context, $userid)) {
                    continue;
                }
                if (has_capability('mod/assign:grade', $context, $userid)) {
                    continue;
                }
                if (quiz_session::has_user_completed_quiz($cmid, $userid)) {
                    continue;
                }
                $pending[] = $cmid;
            } catch (\Exception $e) {
                continue;
            }
        }
        return $pending;
    }

    /**
     * Check if the current request is on an allowed page (quiz interface or assignment view for a pending cmid, or login/logout).
     *
     * @param int[] $pending_cmids List of cmids that require completion
     * @return bool True if current page is allowed and no redirect should happen
     */
    public static function is_current_page_allowed($pending_cmids) {
        global $PAGE;

        if (empty($pending_cmids)) {
            return true;
        }

        $path = $PAGE->url->get_path();
        $path = str_replace('\\', '/', $path);

        if (strpos($path, '/login/') !== false || strpos($path, '/logout/') !== false) {
            return true;
        }

        if (strpos($path, '/local/trustgrade/quiz_interface.php') !== false) {
            $cmid = optional_param('cmid', 0, PARAM_INT);
            if ($cmid && in_array($cmid, $pending_cmids, true)) {
                return true;
            }
        }

        if (strpos($path, '/mod/assign/view.php') !== false) {
            $id = optional_param('id', 0, PARAM_INT);
            if ($id && in_array($id, $pending_cmids, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enforce block: if user has a pending required quiz and is not on an allowed page, redirect after 24h from first block.
     * Call from before_http_headers hook.
     */
    public static function enforce_if_required() {
        global $USER, $SESSION;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        if (!get_config('local_trustgrade', 'plugin_enabled')) {
            return;
        }

        $pending = self::get_pending_required_cmids($USER->id);
        if (empty($pending)) {
            if (isset($SESSION->local_trustgrade_require_quiz_since)) {
                unset($SESSION->local_trustgrade_require_quiz_since);
            }
            return;
        }

        if (self::is_current_page_allowed($pending)) {
            return;
        }

        $now = time();
        if (!isset($SESSION->local_trustgrade_require_quiz_since)) {
            $SESSION->local_trustgrade_require_quiz_since = $now;
        }
        $since = (int) $SESSION->local_trustgrade_require_quiz_since;
        if (($now - $since) > self::BLOCK_DURATION_SECONDS) {
            unset($SESSION->local_trustgrade_require_quiz_since);
            return;
        }

        $cmid = $pending[0];
        $url = new \moodle_url('/mod/assign/view.php', ['id' => $cmid]);
        \redirect($url, get_string('require_quiz_completion_redirect', 'local_trustgrade'), null, \core\output\notification::NOTIFY_INFO);
    }
}
