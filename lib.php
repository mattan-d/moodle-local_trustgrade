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

        $yesno = [0 => get_string('no'), 1 => get_string('yes')];
        $mform->addElement('select', 'trustgrade_enabled',
                get_string('trustgrade_enabled', 'local_trustgrade'),
                $yesno);
        $mform->setDefault('trustgrade_enabled', $default_enabled);
        $mform->addHelpButton('trustgrade_enabled', 'trustgrade_enabled', 'local_trustgrade');

        // Add quiz settings section (heading first, then "require quiz completion" under it)
        $mform->addElement('static', 'trustgrade_quiz_settings_title', '',
                '<h4>' . get_string('quiz_settings_title', 'local_trustgrade') . '</h4>');
        $mform->setAdvanced('trustgrade_quiz_settings_title');

        $default_require = ($cmid > 0) ? ($current_settings['require_quiz_completion'] ? 1 : 0) : 0;
        $mform->addElement('advcheckbox', 'trustgrade_require_quiz_completion',
                get_string('require_quiz_completion', 'local_trustgrade'),
                get_string('require_quiz_completion_desc', 'local_trustgrade'));
        $mform->setDefault('trustgrade_require_quiz_completion', $default_require);
        $mform->addHelpButton('trustgrade_require_quiz_completion', 'require_quiz_completion', 'local_trustgrade');
        $mform->disabledIf('trustgrade_require_quiz_completion', 'trustgrade_enabled', 'eq', 0);
        $mform->setAdvanced('trustgrade_require_quiz_completion');

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

        // Add hidden field to store assignment ID for AJAX calls
        $mform->addElement('hidden', 'trustgrade_cmid', $cmid);
        $mform->setType('trustgrade_cmid', PARAM_INT);

        $mform->disabledIf('trustgrade_instructor_questions', 'trustgrade_enabled', 'eq', 0);

        // Add JavaScript for AJAX functionality (check instructions only; question bank is on question_bank.php)
        $PAGE->requires->js_call_amd('local_trustgrade/trustgrade', 'init');
        $PAGE->requires->css('/local/trustgrade/styles.css');
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
