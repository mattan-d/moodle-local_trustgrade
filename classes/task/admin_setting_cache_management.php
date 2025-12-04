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
 * Custom admin setting for cache management
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade\task;

use local_trustgrade\gateway_client;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom admin setting for cache management
 */
class admin_setting_cache_management extends \admin_setting {

    /**
     * Constructor
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct(
            'local_trustgrade/cache_management_widget',
            get_string('cache_management', 'local_trustgrade'),
            get_string('cache_management_widget_desc', 'local_trustgrade'),
            ''
        );
    }

    /**
     * Get setting value
     */
    public function get_setting() {
        return true;
    }

    /**
     * Write setting (no-op since nosave is true)
     */
    public function write_setting($data) {
        return '';
    }

    /**
     * Output HTML for the setting
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $debug_mode = get_config('local_trustgrade', 'debug_mode');

        if (!$debug_mode) {
            return format_admin_setting($this, $this->visiblename,
                '<div class="alert alert-info">' .
                get_string('cache_disabled_message', 'local_trustgrade') .
                '</div>',
                $this->description, true, '', '', $query);
        }

        $html = '';

        // Cache statistics
        try {
            $gateway = new gateway_client();
            $stats = $gateway->getCacheStats();

            if ($stats['cache_enabled']) {
                $html .= '<div class="cache-stats-widget">';
                $html .= '<div class="row">';

                // Total records
                $html .= '<div class="col-md-3">';
                $html .= '<div class="card text-center">';
                $html .= '<div class="card-body">';
                $html .= '<h4 class="text-primary">' . ($stats['total_records'] ?? 0) . '</h4>';
                $html .= '<small>Total Cached</small>';
                $html .= '</div></div></div>';

                // Recent activity
                $html .= '<div class="col-md-3">';
                $html .= '<div class="card text-center">';
                $html .= '<div class="card-body">';
                $html .= '<h4 class="text-info">' . ($stats['last_24h'] ?? 0) . '</h4>';
                $html .= '<small>Last 24h</small>';
                $html .= '</div></div></div>';

                // Cache efficiency
                $html .= '<div class="col-md-3">';
                $html .= '<div class="card text-center">';
                $html .= '<div class="card-body">';
                $html .= '<h4 class="text-success">' . ($stats['cache_efficiency'] ?? 0) . '%</h4>';
                $html .= '<small>Efficiency</small>';
                $html .= '</div></div></div>';

                // Quick actions
                $html .= '<div class="col-md-3">';
                $html .= '<div class="card text-center">';
                $html .= '<div class="card-body">';
                $html .= '<div class="btn-group-vertical" role="group">';

                // Full management link
                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/cache_management.php'),
                    get_string('full_management', 'local_trustgrade'),
                    ['class' => 'btn btn-sm btn-primary mb-1']
                );

                // Quick clear all
                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/settings_actions.php', [
                        'action' => 'clear_cache',
                        'sesskey' => sesskey()
                    ]),
                    get_string('clear_all', 'local_trustgrade'),
                    [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'onclick' => 'return confirm("' . get_string('confirm_clear_cache', 'local_trustgrade') . '")'
                    ]
                );

                $html .= '</div></div></div></div>';
                $html .= '</div></div>';

                // Quick action buttons
                $html .= '<div class="cache-quick-actions mt-3">';
                $html .= '<div class="btn-group" role="group">';

                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/settings_actions.php', [
                        'action' => 'clear_instructions_cache',
                        'sesskey' => sesskey()
                    ]),
                    get_string('clear_instructions', 'local_trustgrade'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );

                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/settings_actions.php', [
                        'action' => 'clear_questions_cache',
                        'sesskey' => sesskey()
                    ]),
                    get_string('clear_questions', 'local_trustgrade'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );

                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/settings_actions.php', [
                        'action' => 'clear_submissions_cache',
                        'sesskey' => sesskey()
                    ]),
                    get_string('clear_submissions', 'local_trustgrade'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );

                $html .= \html_writer::link(
                    new \moodle_url('/local/trustgrade/settings_actions.php', [
                        'action' => 'cleanup_old_cache',
                        'sesskey' => sesskey()
                    ]),
                    get_string('cleanup_old', 'local_trustgrade'),
                    ['class' => 'btn btn-sm btn-outline-warning']
                );

                $html .= '</div></div>';
            }

        } catch (\Exception $e) {
            $html .= '<div class="alert alert-warning">';
            $html .= get_string('cache_stats_error', 'local_trustgrade', $e->getMessage());
            $html .= '</div>';
        }


        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
    }
}
