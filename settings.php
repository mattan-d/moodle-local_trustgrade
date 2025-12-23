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
 * Plugin settings and configuration options.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_trustgrade', get_string('pluginname', 'local_trustgrade'));

    $settings->add(new admin_setting_configcheckbox(
        'local_trustgrade/plugin_enabled',
        get_string('plugin_enabled', 'local_trustgrade'),
        get_string('plugin_enabled_desc', 'local_trustgrade'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_trustgrade/default_enabled',
        get_string('default_enabled', 'local_trustgrade'),
        get_string('default_enabled_desc', 'local_trustgrade'),
        0
    ));

    // Add debugging mode setting
    $settings->add(new admin_setting_configcheckbox(
        'local_trustgrade/debug_mode',
        get_string('debug_mode', 'local_trustgrade'),
        get_string('debug_mode_desc', 'local_trustgrade'),
        0
    ));

    // Add cache management widget
    $settings->add(new \local_trustgrade\task\admin_setting_cache_management());

    // Add disclosure settings
    $settings->add(new admin_setting_heading(
        'local_trustgrade/disclosure_heading',
        get_string('disclosure_settings', 'local_trustgrade'),
        get_string('disclosure_settings_desc', 'local_trustgrade')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_trustgrade/show_disclosure',
        get_string('show_disclosure', 'local_trustgrade'),
        get_string('show_disclosure_desc', 'local_trustgrade'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_trustgrade/custom_disclosure_message',
        get_string('custom_disclosure_message', 'local_trustgrade'),
        get_string('custom_disclosure_message_desc', 'local_trustgrade'),
        '',
        PARAM_TEXT,
        60,
        4
    ));

    // Add Gateway settings section
    $settings->add(new admin_setting_heading(
        'local_trustgrade/gateway_heading',
        get_string('gateway_settings', 'local_trustgrade'),
        get_string('gateway_settings_desc', 'local_trustgrade')
    ));

    $settings->add(new admin_setting_configtext(
        'local_trustgrade/gateway_endpoint',
        get_string('gateway_endpoint', 'local_trustgrade'),
        get_string('gateway_endpoint_desc', 'local_trustgrade'),
        'http://trustgrade.cloud/', // Set default gateway endpoint to trustgrade.cloud
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_trustgrade/gateway_token',
        get_string('gateway_token', 'local_trustgrade'),
        get_string('gateway_token_desc', 'local_trustgrade'),
        'Demo123'
    ));

    // Gateway test link
    $gateway_test_link = html_writer::link(
        new moodle_url('/local/trustgrade/gateway_test.php'),
        get_string('test_gateway_connection', 'local_trustgrade'),
        ['class' => 'btn btn-outline-primary']
    );

    $settings->add(new admin_setting_description(
        'local_trustgrade/gateway_test',
        get_string('gateway_test', 'local_trustgrade'),
        $gateway_test_link
    ));

    $ADMIN->add('localplugins', $settings);
}
