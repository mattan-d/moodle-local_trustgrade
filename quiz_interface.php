<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$submissionid = optional_param('submissionid', 0, PARAM_INT);
$resubmit = optional_param('resubmit', 0, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$assignment = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check if user can view this quiz
if (!has_capability('mod/assign:submit', $context)) {
    throw new moodle_exception('nopermission');
}

$existing_session = \local_trustgrade\quiz_session::get_session($cmid, $submissionid, $USER->id);
$is_completed = $existing_session && $existing_session['attempt_completed'];

// If quiz is completed and user is not resubmitting, show results
if ($is_completed && !$resubmit) {
    $PAGE->requires->js_call_amd('local_trustgrade/quiz', 'showResults', [$existing_session]);
    $PAGE->requires->css('/local/trustgrade/quiz_styles.css');
    
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('ai_quiz_title', 'local_trustgrade'));
    
    // Show completed quiz results with resubmit option
    echo html_writer::start_div('quiz-completed-container');
    echo html_writer::tag('h3', get_string('quiz_completed', 'local_trustgrade'));
    echo html_writer::div('', 'quiz-results-display');
    
    // Resubmit button
    $resubmit_url = new moodle_url('/local/trustgrade/quiz_interface.php', [
        'cmid' => $cmid, 
        'submissionid' => $submissionid, 
        'resubmit' => 1
    ]);
    echo html_writer::link($resubmit_url, get_string('resubmit_quiz', 'local_trustgrade'), [
        'class' => 'btn btn-warning',
        'style' => 'margin-top: 20px;'
    ]);
    
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

// Get or create the quiz session. Force new session if resubmitting.
$session = \local_trustgrade\quiz_session::get_or_create_session($cmid, $submissionid, $USER->id, (bool)$resubmit);

$PAGE->set_url('/local/trustgrade/quiz_interface.php', ['cmid' => $cmid, 'submissionid' => $submissionid]);
$PAGE->set_title(get_string('ai_quiz_title', 'local_trustgrade'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Check if a session could be created (e.g., if questions were available)
if (!$session) {
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('ai_quiz_title', 'local_trustgrade'));
    \core\notification::add(get_string('no_questions_available', 'local_trustgrade'), \core\notification::INFO);
    echo $OUTPUT->footer();
    exit;
}

// Pass the entire session object to JavaScript. The client side will handle the rest.
$PAGE->requires->js_call_amd('local_trustgrade/quiz', 'init', [$session]);
$PAGE->requires->css('/local/trustgrade/quiz_styles.css');

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('ai_quiz_title', 'local_trustgrade'));

// The main quiz container will be populated by JavaScript.
echo html_writer::start_div('ai-quiz-container');

// Question counter (populated by JS).
echo html_writer::div('', 'question-counter');

// Timer display (populated by JS if enabled).
echo html_writer::div('', 'question-timer', ['style' => 'display: none;']);

// Placeholder for quiz content.
echo html_writer::div('', 'quiz-content');

// Navigation buttons (managed by JS).
echo html_writer::start_div('quiz-navigation');
echo html_writer::tag('button', get_string('next', 'local_trustgrade'), [
    'id' => 'next-btn',
    'class' => 'btn btn-primary',
    'style' => 'display: none;'
]);
echo html_writer::tag('button', get_string('finish_quiz', 'local_trustgrade'), [
    'id' => 'finish-btn',
    'class' => 'btn btn-success',
    'style' => 'display: none;'
]);
echo html_writer::end_div();

// Results area (hidden initially, populated by JS).
echo html_writer::div('', 'quiz-results', ['style' => 'display: none;']);

echo html_writer::end_div();

echo $OUTPUT->footer();
