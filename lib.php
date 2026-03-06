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
 * Library of functions and constants for the TrustGrade plugin.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add TrustGrade elements to assignment form
 */
function local_trustgrade_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE, $DB, $COURSE;

    if (!get_config('local_trustgrade', 'plugin_enabled')) {
        return;
    }

    if (!\local_trustgrade\course_availability::is_available_for_course($COURSE->id)) {
        return;
    }

    if ($PAGE->pagetype === 'mod-assign-mod') {
        $PAGE->requires->js_call_amd('local_trustgrade/submission_processing', 'init', [0]);

        // Get course module ID if editing existing assignment
        $cmid = optional_param('update', 0, PARAM_INT);

        // Add TrustGrade header (creates a collapsible tab)
        $mform->addElement('header', 'trustgrade_header', get_string('trustgrade_tab', 'local_trustgrade'));
        $mform->setExpanded('trustgrade_header', false);

        // Get current settings
        $current_settings = \local_trustgrade\quiz_settings::get_settings($cmid);

        $default_enabled = ($cmid > 0)
            ? ($current_settings['enabled'] ? 1 : 0)
            : get_config('local_trustgrade', 'default_enabled');

        $mform->addElement('advcheckbox', 'trustgrade_enabled',
                get_string('trustgrade_enabled', 'local_trustgrade'),
                get_string('trustgrade_enabled_desc', 'local_trustgrade'));
        $mform->setDefault('trustgrade_enabled', $default_enabled);

        $default_require = ($cmid > 0) ? ($current_settings['require_quiz_completion'] ? 1 : 0) : 0;
        $mform->addElement('advcheckbox', 'trustgrade_require_quiz_completion',
                get_string('require_quiz_completion', 'local_trustgrade'),
                get_string('require_quiz_completion_desc', 'local_trustgrade'));
        $mform->setDefault('trustgrade_require_quiz_completion', $default_require);
        $mform->addHelpButton('trustgrade_require_quiz_completion', 'require_quiz_completion', 'local_trustgrade');
        $mform->disabledIf('trustgrade_require_quiz_completion', 'trustgrade_enabled', 'notchecked');
        $mform->setAdvanced('trustgrade_require_quiz_completion');

        // Add quiz settings section FIRST
        $mform->addElement('static', 'trustgrade_quiz_settings_title', '',
                '<h4>' . get_string('quiz_settings_title', 'local_trustgrade') . '</h4>');
        $mform->setAdvanced('trustgrade_quiz_settings_title');

        // Questions to generate
        $generate_options = [];
        for ($i = 0; $i <= 50; $i++) { // Increased maximum from 10 to 50 to allow more questions
            $generate_options[$i] = $i;
        }

        // Options for number of questions (used for instructor and submission questions)
        $question_count_options = [];
        for ($i = 0; $i <= 50; $i++) { // Increased maximum from 10 to 50 to allow more questions
            $question_count_options[$i] = $i;
        }

        // Question source distribution
        $mform->addElement('static', 'trustgrade_distribution_title', '',
                '<strong>' . get_string('question_distribution', 'local_trustgrade') . '</strong>');
        $mform->setAdvanced('trustgrade_distribution_title');

        $mform->addElement('select', 'trustgrade_instructor_questions',
                get_string('instructor_questions', 'local_trustgrade'), $question_count_options);
        $default_instructor = ($cmid > 0) ? $current_settings['instructor_questions'] : 5;
        $mform->setDefault('trustgrade_instructor_questions', $default_instructor);
        $mform->addHelpButton('trustgrade_instructor_questions', 'instructor_questions', 'local_trustgrade');
        $mform->setAdvanced('trustgrade_instructor_questions');

        $mform->addElement('select', 'trustgrade_submission_questions',
                get_string('submission_questions', 'local_trustgrade'), $question_count_options);
        $default_submission = ($cmid > 0) ? $current_settings['submission_questions'] : 5;
        $mform->setDefault('trustgrade_submission_questions', $default_submission);
        $mform->addHelpButton('trustgrade_submission_questions', 'submission_questions', 'local_trustgrade');
        $mform->setAdvanced('trustgrade_submission_questions');

        // Note: Randomize answers is always enabled (removed from UI)

        // Time per question
        $time_options = [
                15 => '15 ' . get_string('seconds', 'local_trustgrade'),
                25 => '25 ' . get_string('seconds', 'local_trustgrade'),
                35 => '35 ' . get_string('seconds', 'local_trustgrade'),
                45 => '45 ' . get_string('seconds', 'local_trustgrade'),
                60 => '60 ' . get_string('seconds', 'local_trustgrade')
        ];
        $mform->addElement('select', 'trustgrade_time_per_question',
                get_string('time_per_question', 'local_trustgrade'), $time_options);
        $mform->setDefault('trustgrade_time_per_question', $current_settings['time_per_question']);
        $mform->addHelpButton('trustgrade_time_per_question', 'time_per_question', 'local_trustgrade');
        $mform->setAdvanced('trustgrade_time_per_question');

        // Check Instructions button (placed after settings)
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('button', 'check_instructions_btn',
                get_string('check_instructions', 'local_trustgrade'),
                array('id' => 'check-instructions-btn', 'class' => ''));
        $mform->addGroup($buttonarray, 'trustgrade_buttons', ' ', ' ', false);
        $mform->setAdvanced('trustgrade_buttons');

        // Add recommendation display area (hidden by default)
        $mform->addElement('static', 'trustgrade_recommendation', '',
                '<div id="ai-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> ' .
                get_string('processing', 'local_trustgrade') . '</div>' .
                '<div id="ai-recommendation-container" style="display: none;">' .
                '<div id="ai-recommendation" class="alert alert-info"></div></div>');
        $mform->setAdvanced('trustgrade_recommendation');

        // Add hidden field to store assignment ID for AJAX calls
        $mform->addElement('hidden', 'trustgrade_cmid', $cmid);
        $mform->setType('trustgrade_cmid', PARAM_INT);

        $mform->disabledIf('trustgrade_instructor_questions', 'trustgrade_enabled');
        $mform->disabledIf('trustgrade_buttons', 'trustgrade_enabled');

        // Add JavaScript for AJAX functionality (check instructions only; question bank is on question_bank.php)
        $PAGE->requires->js_call_amd('local_trustgrade/trustgrade', 'init');
        $PAGE->requires->css('/local/trustgrade/styles.css');
    }
}

/**
 * Hook called when assignment page is viewed
 */
function local_trustgrade_before_standard_html_head() {
    global $PAGE;

    if (!get_config('local_trustgrade', 'plugin_enabled')) {
        return;
    }

    // Enforce "require quiz completion": block access to other pages until quiz is done (up to 24 hours).
    \local_trustgrade\require_quiz_completion_handler::enforce_if_required();

    // Redirect to question bank after save (new or update) when TrustGrade is enabled.
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

    $cache = cache::make('local_trustgrade', 'pending_generation');
    $pending = $cache->get('trustgrade_pending_generation');

    if ($pending) {

        // Only process if this is the assignment view page and it matches the pending cmid
        if ($PAGE->pagetype === 'mod-assign-view') {
            $current_cmid = optional_param('id', 0, PARAM_INT);

            if ($current_cmid == $pending['cmid']) {
                $cache->delete('trustgrade_pending_generation');

                // Now process the question generation with proper course module validation
                try {
                    // Verify course module exists before proceeding
                    $cm = get_coursemodule_from_id('assign', $pending['cmid'], 0, false, IGNORE_MISSING);
                    if (!$cm) {
                        throw new Exception('Course module not found');
                    }

                    // Collect files using the external class method
                    $files = \local_trustgrade\external::collect_intro_files(
                        $pending['intro_itemid'],
                        $pending['intro_attachments_itemid']
                    );

                    // Trigger question generation
                    $gateway_client = new \local_trustgrade\gateway_client();
                    $result = $gateway_client->generateQuestions(
                        $pending['instructions'],
                        $pending['question_count'],
                        $files
                    );

                    if ($result && isset($result['success']) && $result['success']) {
                        $questions = $result['data']['questions'] ?? [];
                        if (!empty($questions) && is_array($questions)) {
                            $save_success = \local_trustgrade\question_generator::save_questions($pending['cmid'], $questions);
                            if ($save_success) {
                                \core\notification::success(get_string('questions_generated_success', 'local_trustgrade'));
                                // Redirect to question bank after successful generation
                                $question_bank_url = new \moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $pending['cmid']]);
                                redirect($question_bank_url);
                            } else {
                                \core\notification::error(get_string('error_saving_questions', 'local_trustgrade'));
                            }
                        } else {
                            \core\notification::error(get_string('no_questions_generated', 'local_trustgrade'));
                        }
                    } else {
                        $error_msg = isset($result['error']) ? $result['error'] : get_string('questions_generation_failed', 'local_trustgrade');
                        \core\notification::error($error_msg);
                    }
                } catch (Exception $e) {
                    \core\notification::error(get_string('questions_generation_error', 'local_trustgrade') . ': ' . $e->getMessage());
                }
            }
        }
    }

    // Load CSS early for all assignment pages
    if (strpos($PAGE->pagetype, 'mod-assign') === 0) {
        $PAGE->requires->css('/local/trustgrade/styles.css');
    }


    // Check if this is an assignment view page
    if ($PAGE->pagetype === 'mod-assign-view') {
        $cmid = optional_param('id', 0, PARAM_INT);

        if ($cmid > 0) {
            $settings = \local_trustgrade\quiz_settings::get_settings($cmid);
            if (!$settings['enabled']) {
                return;
            }

            // Check if user should be redirected to quiz
            \local_trustgrade\redirect_handler::check_and_handle_redirect($cmid);
        }
    }

    // Handle disclosure for assignment submission pages
    if ($PAGE->pagetype === 'mod-assign-editsubmission' || $PAGE->pagetype === 'mod-assign-submit') {
        $cmid = optional_param('id', 0, PARAM_INT);

        if ($cmid > 0) {
            $settings = \local_trustgrade\quiz_settings::get_settings($cmid);
            if (!$settings['enabled']) {
                return;
            }

            // Initialize disclosure using external files
            \local_trustgrade\disclosure_handler::init_disclosure($cmid);

            $PAGE->requires->js_call_amd('local_trustgrade/submission_processing', 'init', [
                $cmid,
                $settings['questions_to_generate']
            ]);
        }
    }

    if (isloggedin() && !isguestuser()) {
        // Don't show task indicator on the quiz interface page (user is already in the quiz).
        $path = $PAGE->url->get_path();
        if (strpos($path, 'quiz_interface.php') === false) {
            $PAGE->requires->js_call_amd('local_trustgrade/task_indicator', 'init');
        }
    }
}

/**
 * Add TrustGrade links to the assignment module settings menu (gear menu).
 * Adds "Question bank" and "TrustGrade Report" when viewing an assignment with TrustGrade enabled.
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param context $context The current context
 */
function local_trustgrade_extend_settings_navigation($settingsnav, $context) {
    if (!get_config('local_trustgrade', 'plugin_enabled')) {
        return;
    }
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }
    $page = $settingsnav->get_page();
    if (!$page->cm || $page->activityname !== 'assign') {
        return;
    }
    $cmid = $page->cm->id;
    $settings = \local_trustgrade\quiz_settings::get_settings($cmid);
    if (empty($settings['enabled'])) {
        return;
    }
    $modulenode = $settingsnav->get('modulesettings');
    if (!$modulenode) {
        return;
    }
    $reporturl = new moodle_url('/local/trustgrade/quiz_report.php', ['cmid' => $cmid]);
    $modulenode->add(
        get_string('trustgrade_report', 'local_trustgrade'),
        $reporturl,
        navigation_node::TYPE_SETTING,
        null,
        'trustgrade_report',
        new pix_icon('i/report', '')
    );
    $questionbankurl = new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]);
    $modulenode->add(
        get_string('question_bank', 'local_trustgrade'),
        $questionbankurl,
        navigation_node::TYPE_SETTING,
        null,
        'trustgrade_question_bank',
        new pix_icon('i/question', '')
    );
}

/**
 * Hook called after assignment form is submitted
 */
function local_trustgrade_coursemodule_edit_post_actions($data, $course) {
    if (!get_config('local_trustgrade', 'plugin_enabled')) {
        return $data;
    }

    // Save quiz settings if they were provided
    if (isset($data->trustgrade_instructor_questions)) {
        $cmid = $data->coursemodule;

        $settings = [
                'enabled' => !empty($data->trustgrade_enabled),
                'questions_to_generate' => $data->trustgrade_instructor_questions,
                'instructor_questions' => $data->trustgrade_instructor_questions,
                'submission_questions' => $data->trustgrade_submission_questions,
                'randomize_answers' => true,
                'time_per_question' => $data->trustgrade_time_per_question,
                'show_countdown' => true,
                'require_quiz_completion' => !empty($data->trustgrade_require_quiz_completion),
            ];

        \local_trustgrade\quiz_settings::save_settings($cmid, $settings);

        // Schedule redirect to question bank after the next page load (avoids redirect inside DB transaction).
        if (!empty($data->trustgrade_enabled) && $cmid > 0) {
            $_SESSION['local_trustgrade_redirect_cmid'] = (int) $cmid;
        }
    }

    return $data;
}

/**
 * Add TrustGrade quiz grade column to assignment grading page
 */
function local_trustgrade_before_footer() {
    global $PAGE;

    // Check if plugin is globally enabled
    if (!get_config('local_trustgrade', 'plugin_enabled')) {
        return;
    }

    // Only run on assignment grading page
    if ($PAGE->pagetype === 'mod-assign-grading' ||
        (strpos($PAGE->url->get_path(), '/mod/assign/view.php') !== false &&
         optional_param('action', '', PARAM_ALPHA) === 'grading')) {

        // Get the assignment course module ID
        $cmid = optional_param('id', 0, PARAM_INT);

        if ($cmid > 0) {
            // Check if TrustGrade is enabled for this specific assignment
            $settings = \local_trustgrade\quiz_settings::get_settings($cmid);

            if (!empty($settings['enabled'])) {
                $PAGE->requires->js_call_amd('local_trustgrade/grading_table', 'init', [$cmid]);
            }
        }
    }
}
