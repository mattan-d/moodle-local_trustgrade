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
 * Cache management interface for TrustGrade debug data.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_TEXT);

$PAGE->set_url('/local/trustgrade/cache_management.php');
$PAGE->set_title(get_string('cache_management_title', 'local_trustgrade'));
$PAGE->set_heading(get_string('cache_management_heading', 'local_trustgrade'));

// Handle actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'clear_all':
            \local_trustgrade\debug_cache::cleanup_old_records(0); // Clear all
            \core\notification::add(get_string('all_cache_cleared', 'local_trustgrade'), \core\notification::SUCCESS);
            break;
            
        case 'clear_instructions':
            \local_trustgrade\debug_cache::clear_cache_by_type('check_instructions');
            \core\notification::add(get_string('instruction_cache_cleared', 'local_trustgrade'), \core\notification::SUCCESS);
            break;
            
        case 'clear_questions':
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_questions');
            \core\notification::add(get_string('question_cache_cleared', 'local_trustgrade'), \core\notification::SUCCESS);
            break;
            
        case 'clear_submissions':
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_submission_questions');
            \core\notification::add(get_string('submission_cache_cleared', 'local_trustgrade'), \core\notification::SUCCESS);
            break;
            
        case 'cleanup_old':
            \local_trustgrade\debug_cache::cleanup_old_records(7); // Keep only 7 days
            \core\notification::add(get_string('old_cache_cleaned_up', 'local_trustgrade'), \core\notification::SUCCESS);
            break;
    }
    
    redirect($PAGE->url);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('cache_management_heading', 'local_trustgrade'));

// Check if debug mode is enabled
$debug_mode = get_config('local_trustgrade', 'debug_mode');

if (!$debug_mode) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-info-circle']) . ' ' .
        get_string('debug_mode_disabled_info', 'local_trustgrade'),
        'alert alert-info'
    );
} else {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-check-circle']) . ' ' .
        get_string('debug_mode_enabled_info', 'local_trustgrade'),
        'alert alert-success'
    );
}

// Get cache statistics
try {
    $gateway = new \local_trustgrade\gateway_client();
    $stats = $gateway->getCacheStats();
    
    if ($stats['cache_enabled']) {
        echo html_writer::tag('h3', get_string('cache_statistics', 'local_trustgrade'));
        
        echo html_writer::start_div('row');
        
        // Total records card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['total_records'] ?? 0, ['class' => 'text-primary']);
        echo html_writer::tag('p', get_string('total_cached_responses', 'local_trustgrade'), ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Last 24h card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['last_24h'] ?? 0, ['class' => 'text-info']);
        echo html_writer::tag('p', get_string('last_24_hours', 'local_trustgrade'), ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Cache potential card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['cache_potential'] ?? 0, ['class' => 'text-success']);
        echo html_writer::tag('p', get_string('cacheable_responses', 'local_trustgrade'), ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Cache efficiency card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', ($stats['cache_efficiency'] ?? 0) . '%', ['class' => 'text-warning']);
        echo html_writer::tag('p', get_string('cache_efficiency', 'local_trustgrade'), ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        echo html_writer::end_div();
        
        // Cache by type
        if (isset($stats['by_type']) && !empty($stats['by_type'])) {
            echo html_writer::tag('h4', get_string('cache_by_type', 'local_trustgrade'));
            echo html_writer::start_tag('table', ['class' => 'table table-striped']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('request_type', 'local_trustgrade'));
            echo html_writer::tag('th', get_string('cached_responses', 'local_trustgrade'));
            echo html_writer::tag('th', get_string('actions', 'local_trustgrade'));
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            
            foreach ($stats['by_type'] as $type => $count) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', ucfirst(str_replace('_', ' ', $type)));
                echo html_writer::tag('td', $count);
                echo html_writer::start_tag('td');
                
                $clear_url = new moodle_url($PAGE->url, [
                    'action' => 'clear_' . str_replace('generate_', '', str_replace('_questions', '', $type)),
                    'sesskey' => sesskey()
                ]);
                echo html_writer::link($clear_url, get_string('clear', 'local_trustgrade'), ['class' => 'btn btn-sm btn-outline-danger']);
                
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            }
            
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }
        
        // Recent activity
        $recent_activity = \local_trustgrade\debug_cache::get_recent_activity(10);
        if (!empty($recent_activity)) {
            echo html_writer::tag('h4', get_string('recent_cache_activity', 'local_trustgrade'));
            echo html_writer::start_tag('table', ['class' => 'table table-sm']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('type', 'local_trustgrade'));
            echo html_writer::tag('th', get_string('time', 'local_trustgrade'));
            echo html_writer::tag('th', get_string('status', 'local_trustgrade'));
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            
            foreach ($recent_activity as $activity) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', ucfirst(str_replace('_', ' ', $activity['type'])));
                echo html_writer::tag('td', $activity['time_ago']);
                $status = $activity['cacheable'] ? 
                    '<span class="badge badge-success">' . get_string('cached', 'local_trustgrade') . '</span>' : 
                    '<span class="badge badge-secondary">' . get_string('not_cached', 'local_trustgrade') . '</span>';
                echo html_writer::tag('td', $status);
                echo html_writer::end_tag('tr');
            }
            
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }
    }
    
} catch (Exception $e) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' .
        get_string('error_loading_cache_stats', 'local_trustgrade', $e->getMessage()),
        'alert alert-danger'
    );
}

// Cache management actions
echo html_writer::tag('h3', get_string('cache_management_actions', 'local_trustgrade'));

echo html_writer::start_div('row');

// Clear all cache
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('clear_all_cache_title', 'local_trustgrade'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('clear_all_cache_desc', 'local_trustgrade'), ['class' => 'card-text']);
$clear_all_url = new moodle_url($PAGE->url, ['action' => 'clear_all', 'sesskey' => sesskey()]);
echo html_writer::link($clear_all_url, get_string('clear_all_cache_button', 'local_trustgrade'), [
    'class' => 'btn btn-danger',
    'onclick' => 'return confirm("' . get_string('confirm_clear_cache', 'local_trustgrade') . '")'
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Cleanup old records
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('cleanup_old_records_title', 'local_trustgrade'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('cleanup_old_records_desc', 'local_trustgrade'), ['class' => 'card-text']);
$cleanup_url = new moodle_url($PAGE->url, ['action' => 'cleanup_old', 'sesskey' => sesskey()]);
echo html_writer::link($cleanup_url, get_string('cleanup_old_records_button', 'local_trustgrade'), ['class' => 'btn btn-warning']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

// Links
echo html_writer::tag('h3', get_string('related_pages', 'local_trustgrade'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::link(
    new moodle_url('/admin/settings.php', ['section' => 'local_trustgrade']),
    get_string('plugin_settings', 'local_trustgrade')
));
echo html_writer::tag('li', html_writer::link(
    new moodle_url('/local/trustgrade/gateway_test.php'),
    get_string('gateway_test_heading', 'local_trustgrade')
));
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();
