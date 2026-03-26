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

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for local_trustgrade.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Handle redirects/enforcement before headers are sent.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        if (!get_config('local_trustgrade', 'plugin_enabled')) {
            return;
        }

        // Feature disabled: do not enforce "require quiz completion".

        // Redirect to question bank after assignment save.
        if (!empty($_SESSION['local_trustgrade_redirect_cmid'])) {
            $cmid = (int) $_SESSION['local_trustgrade_redirect_cmid'];
            unset($_SESSION['local_trustgrade_redirect_cmid']);
            if ($cmid > 0) {
                $cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
                if ($cm) {
                    redirect(new \moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]));
                }
            }
        }

        $cache = \cache::make('local_trustgrade', 'pending_generation');
        $pending = $cache->get('trustgrade_pending_generation');
        if ($pending && $PAGE->pagetype === 'mod-assign-view') {
            $currentcmid = optional_param('id', 0, PARAM_INT);
            if ($currentcmid == $pending['cmid']) {
                $cache->delete('trustgrade_pending_generation');
                try {
                    $cm = get_coursemodule_from_id('assign', $pending['cmid'], 0, false, IGNORE_MISSING);
                    if (!$cm) {
                        throw new \Exception('Course module not found');
                    }

                    $files = external::collect_intro_files(
                        $pending['intro_itemid'],
                        $pending['intro_attachments_itemid']
                    );

                    $gatewayclient = new gateway_client();
                    $result = $gatewayclient->generateQuestions(
                        $pending['instructions'],
                        $pending['question_count'],
                        $files
                    );

                    if ($result && !empty($result['success'])) {
                        $questions = $result['data']['questions'] ?? [];
                        if (!empty($questions) && is_array($questions)) {
                            $savesuccess = question_generator::save_questions($pending['cmid'], $questions);
                            if ($savesuccess) {
                                \core\notification::success(get_string('questions_generated_success', 'local_trustgrade'));
                                redirect(new \moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $pending['cmid']]));
                            } else {
                                \core\notification::error(get_string('error_saving_questions', 'local_trustgrade'));
                            }
                        } else {
                            \core\notification::error(get_string('no_questions_generated', 'local_trustgrade'));
                        }
                    } else {
                        $errmsg = $result['error'] ?? get_string('questions_generation_failed', 'local_trustgrade');
                        \core\notification::error($errmsg);
                    }
                } catch (\Exception $e) {
                    \core\notification::error(get_string('questions_generation_error', 'local_trustgrade') . ': ' . $e->getMessage());
                }
            }
        }

        if ($PAGE->pagetype === 'mod-assign-view') {
            $cmid = optional_param('id', 0, PARAM_INT);
            if ($cmid > 0) {
                $settings = quiz_settings::get_settings($cmid);
                if (!empty($settings['enabled'])) {
                    redirect_handler::check_and_handle_redirect($cmid);
                }
            }
        }
    }

    /**
     * Register head assets and page JS.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $PAGE;

        if (!get_config('local_trustgrade', 'plugin_enabled')) {
            return;
        }

        if (strpos($PAGE->pagetype, 'mod-assign') === 0) {
            $PAGE->requires->css('/local/trustgrade/styles.css');
        }

        if ($PAGE->pagetype === 'mod-assign-editsubmission' || $PAGE->pagetype === 'mod-assign-submit') {
            $cmid = optional_param('id', 0, PARAM_INT);
            if ($cmid > 0) {
                $settings = quiz_settings::get_settings($cmid);
                if (!empty($settings['enabled'])) {
                    disclosure_handler::init_disclosure($cmid);
                    $PAGE->requires->js_call_amd('local_trustgrade/submission_processing', 'init', [
                        $cmid,
                        $settings['questions_to_generate'],
                    ]);
                }
            }
        }

        if (isloggedin() && !isguestuser()) {
            $path = $PAGE->url->get_path();
            if (strpos($path, 'quiz_interface.php') === false) {
                $PAGE->requires->js_call_amd('local_trustgrade/task_indicator', 'init');
            }
        }
    }
}
