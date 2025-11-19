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
 * Gateway connection testing interface for administrators.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/trustgrade/gateway_test.php');
$PAGE->set_title(get_string('gateway_test_title', 'local_trustgrade'));
$PAGE->set_heading(get_string('gateway_test_heading', 'local_trustgrade'));

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('gateway_test_heading', 'local_trustgrade'));

// Test Gateway connection
try {
    $gateway = new \local_trustgrade\gateway_client();
    $result = $gateway->testConnection();
    
    if ($result['success']) {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-check-circle']) . ' ' . $result['message'],
            'alert alert-success'
        );
        
        echo html_writer::tag('h3', get_string('gateway_configuration', 'local_trustgrade'));
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', get_string('gateway_endpoint_label', 'local_trustgrade') . ': ' . 
            get_config('local_trustgrade', 'gateway_endpoint'));
        echo html_writer::tag('li', get_string('gateway_token_label', 'local_trustgrade') . ': ' . 
            (get_config('local_trustgrade', 'gateway_token') ? 
                get_string('gateway_token_configured', 'local_trustgrade') : 
                get_string('gateway_token_not_configured', 'local_trustgrade')));
        echo html_writer::end_tag('ul');
        
        echo html_writer::tag('p', 
            html_writer::tag('strong', get_string('note', 'moodle') . ': ') . 
            get_string('gateway_openrouter_note', 'local_trustgrade')
        );
        
    } else {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' . 
            get_string('gateway_connection_failed', 'local_trustgrade', $result['error']),
            'alert alert-danger'
        );
        
        echo html_writer::tag('h3', get_string('gateway_troubleshooting', 'local_trustgrade'));
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', get_string('gateway_verify_url', 'local_trustgrade'));
        echo html_writer::tag('li', get_string('gateway_check_token', 'local_trustgrade'));
        echo html_writer::tag('li', get_string('gateway_ensure_running', 'local_trustgrade'));
        echo html_writer::tag('li', get_string('gateway_verify_apikey', 'local_trustgrade'));
        echo html_writer::end_tag('ul');
    }
    
} catch (Exception $e) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-times-circle']) . ' ' . 
        get_string('gateway_config_error', 'local_trustgrade', $e->getMessage()),
        'alert alert-danger'
    );
    
    echo html_writer::tag('h3', get_string('gateway_config_required', 'local_trustgrade'));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('gateway_config_endpoint', 'local_trustgrade'));
    echo html_writer::tag('li', get_string('gateway_config_token', 'local_trustgrade'));
    echo html_writer::tag('li', get_string('gateway_config_openrouter', 'local_trustgrade'));
    echo html_writer::end_tag('ul');
}

echo html_writer::tag('p', 
    html_writer::link(
        new moodle_url('/admin/settings.php', ['section' => 'local_trustgrade']),
        get_string('configure_gateway_settings', 'local_trustgrade'),
        ['class' => 'btn btn-primary']
    )
);

echo $OUTPUT->footer();
