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

    $settings->add(new admin_setting_configcheckbox(
        'local_trustgrade/course_specific',
        get_string('course_specific', 'local_trustgrade'),
        get_string('course_specific_desc', 'local_trustgrade'),
        0
    ));

    require_once($CFG->dirroot . '/local/trustgrade/classes/admin_setting_course_multiselect.php');
    $settings->add(new \local_trustgrade\admin_setting_course_multiselect(
        'local_trustgrade/enabled_courses',
        get_string('enabled_courses', 'local_trustgrade'),
        get_string('enabled_courses_desc', 'local_trustgrade'),
        []
    ));

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

    $settings->add(new admin_setting_confightmleditor(
        'local_trustgrade/custom_disclosure_message',
        get_string('custom_disclosure_message', 'local_trustgrade'),
        get_string('custom_disclosure_message_desc', 'local_trustgrade'),
        '',
        PARAM_RAW,
        60,
        6
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
        'https://trustgrade.cloud/', // Updated default to https://trustgrade.cloud/
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_trustgrade/gateway_token',
        get_string('gateway_token', 'local_trustgrade'),
        get_string('gateway_token_desc', 'local_trustgrade'),
        '' // Changed default to empty string
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

    // Client usage (GET gateway_endpoint/usage?api_key=token)
    $settings->add(new admin_setting_heading(
        'local_trustgrade/gateway_usage_heading',
        get_string('gateway_usage', 'local_trustgrade'),
        get_string('gateway_usage_desc', 'local_trustgrade')
    ));

    $usage_html = '';
    $endpoint = get_config('local_trustgrade', 'gateway_endpoint');
    $token = get_config('local_trustgrade', 'gateway_token');
    if (empty($endpoint) || empty($token)) {
        $usage_html = html_writer::div(
            get_string('gateway_usage_not_configured', 'local_trustgrade'),
            'alert alert-info'
        );
    } else {
        try {
            require_once($CFG->dirroot . '/local/trustgrade/classes/gateway_client.php');
            $client = new \local_trustgrade\gateway_client();
            $result = $client->getUsage();
            if ($result['success'] && !empty($result['usage'])) {
                $u = $result['usage'];
                if (!empty($u['client_name'])) {
                    $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_client_name', 'local_trustgrade') . ': ') . s($u['client_name']));
                }
                if (!empty($u['requests'])) {
                    $r = $u['requests'];
                    $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_requests', 'local_trustgrade') . ': '));
                    $usage_html .= html_writer::start_tag('ul');
                    if (isset($r['total'])) {
                        $usage_html .= html_writer::tag('li', get_string('gateway_usage_total', 'local_trustgrade') . ': ' . (int)$r['total']);
                    }
                    if (isset($r['today'])) {
                        $usage_html .= html_writer::tag('li', get_string('gateway_usage_today', 'local_trustgrade') . ': ' . (int)$r['today']);
                    }
                    if (isset($r['week'])) {
                        $usage_html .= html_writer::tag('li', get_string('gateway_usage_week', 'local_trustgrade') . ': ' . (int)$r['week']);
                    }
                    if (isset($r['month'])) {
                        $usage_html .= html_writer::tag('li', get_string('gateway_usage_month', 'local_trustgrade') . ': ' . (int)$r['month']);
                    }
                    if (isset($r['year'])) {
                        $usage_html .= html_writer::tag('li', get_string('gateway_usage_year', 'local_trustgrade') . ': ' . (int)$r['year']);
                    }
                    $usage_html .= html_writer::end_tag('ul');
                }
                if (!empty($u['limits'])) {
                    $lim = $u['limits'];
                    $has_limits = isset($lim['daily']) || isset($lim['monthly']) || isset($lim['trial']) || isset($lim['tokens']);
                    if ($has_limits) {
                        $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_limits', 'local_trustgrade') . ': '));
                        $usage_html .= html_writer::start_tag('ul');
                        if (isset($lim['daily'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_daily', 'local_trustgrade') . ': ' . (int)$lim['daily']);
                        }
                        if (isset($lim['monthly'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_monthly', 'local_trustgrade') . ': ' . (int)$lim['monthly']);
                        }
                        if (isset($lim['trial'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_trial', 'local_trustgrade') . ': ' . (int)$lim['trial']);
                        }
                        if (isset($lim['tokens'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_tokens', 'local_trustgrade') . ': ' . number_format((int)$lim['tokens']));
                        }
                        $usage_html .= html_writer::end_tag('ul');
                    }
                }
                if (!empty($u['remaining'])) {
                    $rem = $u['remaining'];
                    $has_remaining = isset($rem['daily']) || isset($rem['monthly']) || isset($rem['trial']) || isset($rem['tokens']);
                    if ($has_remaining) {
                        $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_remaining', 'local_trustgrade') . ': '));
                        $usage_html .= html_writer::start_tag('ul');
                        if (isset($rem['daily'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_daily', 'local_trustgrade') . ': ' . (int)$rem['daily']);
                        }
                        if (isset($rem['monthly'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_monthly', 'local_trustgrade') . ': ' . (int)$rem['monthly']);
                        }
                        if (isset($rem['trial'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_trial', 'local_trustgrade') . ': ' . (int)$rem['trial']);
                        }
                        if (isset($rem['tokens'])) {
                            $usage_html .= html_writer::tag('li', get_string('gateway_usage_tokens', 'local_trustgrade') . ': ' . number_format((int)$rem['tokens']));
                        }
                        $usage_html .= html_writer::end_tag('ul');
                    }
                }
                if (isset($u['tokens_used'])) {
                    $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_tokens_used', 'local_trustgrade') . ': ') . number_format((int)$u['tokens_used']));
                }
                if (!empty($u['last_request_at'])) {
                    $usage_html .= html_writer::tag('p', html_writer::tag('strong', get_string('gateway_usage_last_request', 'local_trustgrade') . ': ') . s($u['last_request_at']));
                }
            } else {
                $usage_html = html_writer::div(
                    get_string('gateway_usage_connection_error', 'local_trustgrade') . ' ' . ($result['error'] ?? ''),
                    'alert alert-warning'
                );
            }
        } catch (\Exception $e) {
            $usage_html = html_writer::div(
                get_string('gateway_usage_connection_error', 'local_trustgrade') . ' ' . s($e->getMessage()),
                'alert alert-warning'
            );
        }
    }

    $settings->add(new admin_setting_description(
        'local_trustgrade/gateway_usage',
        '',
        $usage_html
    ));

    $ADMIN->add('localplugins', $settings);
}
