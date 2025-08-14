<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Add TrustGrade elements to assignment form
 */
function local_trustgrade_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE, $DB;

    if ($PAGE->pagetype === 'mod-assign-mod') {
        // Get course module ID if editing existing assignment
        $cmid = optional_param('update', 0, PARAM_INT);

        // Add TrustGrade header (creates a collapsible tab)
        $mform->addElement('header', 'trustgrade_header', get_string('trustgrade_tab', 'local_trustgrade'));
        $mform->setExpanded('trustgrade_header', false);

        // Add description
        $mform->addElement('static', 'trustgrade_description', '',
                get_string('trustgrade_description', 'local_trustgrade'));

        // Add quiz settings section FIRST
        $mform->addElement('static', 'trustgrade_quiz_settings_title', '',
                '<h4>' . get_string('quiz_settings_title', 'local_trustgrade') . '</h4>');

        // Get current settings
        $current_settings = \local_trustgrade\quiz_settings::get_settings($cmid);

        // Questions to generate
        $generate_options = [];
        for ($i = 1; $i <= 10; $i++) {
            $generate_options[$i] = $i;
        }
        $mform->addElement('select', 'trustgrade_questions_to_generate',
                get_string('questions_to_generate', 'local_trustgrade'), $generate_options);
        $mform->setDefault('trustgrade_questions_to_generate', $current_settings['questions_to_generate']);
        $mform->addHelpButton('trustgrade_questions_to_generate', 'questions_to_generate', 'local_trustgrade');

        // Options for number of questions (used for instructor and submission questions)
        $question_count_options = [];
        for ($i = 0; $i <= 20; $i++) {
            $question_count_options[$i] = $i;
        }

        // Question source distribution
        $mform->addElement('static', 'trustgrade_distribution_title', '',
                '<strong>' . get_string('question_distribution', 'local_trustgrade') . '</strong>');

        $mform->addElement('select', 'trustgrade_instructor_questions',
                get_string('instructor_questions', 'local_trustgrade'), $question_count_options);
        $mform->setDefault('trustgrade_instructor_questions', $current_settings['instructor_questions']);
        $mform->addHelpButton('trustgrade_instructor_questions', 'instructor_questions', 'local_trustgrade');

        $mform->addElement('select', 'trustgrade_submission_questions',
                get_string('submission_questions', 'local_trustgrade'), $question_count_options);
        $mform->setDefault('trustgrade_submission_questions', $current_settings['submission_questions']);
        $mform->addHelpButton('trustgrade_submission_questions', 'submission_questions', 'local_trustgrade');

        // Randomize answers
        $mform->addElement('advcheckbox', 'trustgrade_randomize_answers',
                get_string('randomize_answers', 'local_trustgrade'),
                get_string('randomize_answers_desc', 'local_trustgrade'));
        $mform->setDefault('trustgrade_randomize_answers', $current_settings['randomize_answers']);

        // Time per question
        $time_options = [
                10 => '10 ' . get_string('seconds', 'local_trustgrade'),
                15 => '15 ' . get_string('seconds', 'local_trustgrade'),
                20 => '20 ' . get_string('seconds', 'local_trustgrade'),
                25 => '25 ' . get_string('seconds', 'local_trustgrade'),
                30 => '30 ' . get_string('seconds', 'local_trustgrade')
        ];
        $mform->addElement('select', 'trustgrade_time_per_question',
                get_string('time_per_question', 'local_trustgrade'), $time_options);
        $mform->setDefault('trustgrade_time_per_question', $current_settings['time_per_question']);
        $mform->addHelpButton('trustgrade_time_per_question', 'time_per_question', 'local_trustgrade');

        // Show countdown
        $mform->addElement('advcheckbox', 'trustgrade_show_countdown',
                get_string('show_countdown', 'local_trustgrade'),
                get_string('show_countdown_desc', 'local_trustgrade'));
        $mform->setDefault('trustgrade_show_countdown', $current_settings['show_countdown']);

        // Add check button
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('button', 'check_instructions_btn',
                get_string('check_instructions', 'local_trustgrade'),
                array('id' => 'check-instructions-btn', 'class' => ''));
        $mform->addGroup($buttonarray, 'trustgrade_buttons', get_string('ai_recommendation', 'local_trustgrade'), ' ', false);

        // Add recommendation display area (hidden by default)
        $mform->addElement('static', 'trustgrade_recommendation',
                '<div id="ai-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> ' .
                get_string('processing', 'local_trustgrade') . '</div>',
                '<div id="ai-recommendation-container" style="display: none;">' .
                '<div id="ai-recommendation" class="alert alert-info"></div></div>');

        // Add question generation button
        $questionbuttonarray = array();
        $questionbuttonarray[] = $mform->createElement('button', 'generate_questions_btn',
                get_string('generate_questions', 'local_trustgrade'),
                array('id' => 'generate-questions-btn', 'class' => ''));
        $mform->addGroup($questionbuttonarray, 'trustgrade_question_buttons', '', ' ', false);

        // Add question bank section placeholder (will be loaded via AJAX)
        $mform->addElement('static', 'trustgrade_question_bank_placeholder',
                get_string('generated_questions', 'local_trustgrade'),
                '<div id="ai-question-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> ' .
                get_string('generating_questions', 'local_trustgrade') . '</div>' .
                '<div id="question-bank-section">' .
                '<div id="question-bank-loading" style="display: none;">' .
                '<i class="fa fa-spinner fa-spin"></i> ' . get_string('loading_question_bank', 'local_trustgrade') .
                '</div>' .
                '<div id="question-bank-container"></div>' .
                '</div>');

        // Add hidden field to store assignment ID for AJAX calls
        $mform->addElement('hidden', 'trustgrade_cmid', $cmid);
        $mform->setType('trustgrade_cmid', PARAM_INT);

        // Add JavaScript for AJAX functionality
        $PAGE->requires->js_call_amd('local_trustgrade/trustgrade', 'init');
        $PAGE->requires->js_call_amd('local_trustgrade/question_editor', 'init', [$cmid]);
        $PAGE->requires->css('/local/trustgrade/styles.css');
    }
}

/**
 * Hook called when assignment page is viewed
 */
function local_trustgrade_before_standard_html_head() {
    global $PAGE;

    // Load CSS early for all assignment pages
    if (strpos($PAGE->pagetype, 'mod-assign') === 0) {
        $PAGE->requires->css('/local/trustgrade/styles.css');
    }

    // Check if this is an assignment view page
    if ($PAGE->pagetype === 'mod-assign-view') {
        $cmid = optional_param('id', 0, PARAM_INT);

        if ($cmid > 0) {
            // Check if user should be redirected to quiz
            \local_trustgrade\redirect_handler::check_and_handle_redirect($cmid);

            // Add AI Quiz Report button for instructors
            $context = \context_module::instance($cmid);
            if (has_capability('mod/assign:grade', $context)) {
                $PAGE->requires->js_call_amd('local_trustgrade/quiz_report_button', 'init', [$cmid]);
            }
        }
    }

    // Handle disclosure for assignment submission pages
    if ($PAGE->pagetype === 'mod-assign-editsubmission') {
        $cmid = optional_param('id', 0, PARAM_INT);

        if ($cmid > 0) {
            // Initialize disclosure using external files
            \local_trustgrade\disclosure_handler::init_disclosure($cmid);
        }
    }
}

/**
 * Hook called after assignment form is submitted
 */
function local_trustgrade_coursemodule_edit_post_actions($data, $course) {
    // Save quiz settings if they were provided
    if (isset($data->trustgrade_questions_to_generate)) {
        $cmid = $data->coursemodule;

        $settings = [
                'questions_to_generate' => $data->trustgrade_questions_to_generate,
                'instructor_questions' => $data->trustgrade_instructor_questions,
                'submission_questions' => $data->trustgrade_submission_questions,
                'randomize_answers' => !empty($data->trustgrade_randomize_answers),
                'time_per_question' => $data->trustgrade_time_per_question,
                'show_countdown' => !empty($data->trustgrade_show_countdown)
        ];

        \local_trustgrade\quiz_settings::save_settings($cmid, $settings);
    }

    return $data;
}
