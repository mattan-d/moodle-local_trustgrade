<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$submissionid = optional_param('submissionid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$assignment = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check if user can view this quiz
if (!has_capability('mod/assign:submit', $context)) {
    throw new moodle_exception('nopermission');
}

// Get or create the quiz session. This is now the single source of truth for the quiz state.
$session = \local_trustgrade\quiz_session::get_or_create_session($cmid, $submissionid, $USER->id);

$PAGE->set_url('/local/trustgrade/quiz_interface.php', ['cmid' => $cmid, 'submissionid' => $submissionid]);
$PAGE->set_title(get_string('ai_quiz_title', 'local_trustgrade'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Check if quiz is already completed
if ($session && $session['attempt_completed']) {
    // Show completion message and exit
    $PAGE->set_url('/local/trustgrade/quiz_interface.php', ['cmid' => $cmid, 'submissionid' => $submissionid]);
    $PAGE->set_title(get_string('ai_quiz_title', 'local_trustgrade'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_context($context);
    
    echo $OUTPUT->header();
    echo html_writer::tag('h2', get_string('ai_quiz_title', 'local_trustgrade'));
    
    \core\notification::add(
        'You have already completed this assessment. Only one attempt is allowed per assignment.',
        \core\notification::INFO
    );
    
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/assign/view.php', ['id' => $cmid]),
            'Return to Assignment',
            ['class' => 'btn btn-primary']
        ),
        'text-center mt-3'
    );
    
    echo $OUTPUT->footer();
    exit;
}

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

// Placeholder for quiz content.
echo html_writer::div('', 'quiz-content');

// Question counter (populated by JS).
echo html_writer::div('', 'question-counter');

// Timer display (populated by JS if enabled) - moved to be under quiz-progress.
echo html_writer::div('', 'question-timer', ['style' => 'display: none;']);

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
