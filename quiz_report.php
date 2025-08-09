<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Get parameters
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Require login
require_login();

// Set up page context
if ($cmid) {
    $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_capability('mod/assign:grade', $context);
    $PAGE->set_cm($cm, $course);
    $PAGE->set_context($context);
} else if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id);
    require_capability('moodle/course:manageactivities', $context);
    $PAGE->set_course($course);
    $PAGE->set_context($context);
} else {
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    $PAGE->set_context($context);
}

// Set up page
$PAGE->set_url('/local/trustgrade/quiz_report.php', array('cmid' => $cmid, 'courseid' => $courseid));
$PAGE->set_title(get_string('quiz_report', 'local_trustgrade'));
$PAGE->set_heading(get_string('quiz_report', 'local_trustgrade'));
$PAGE->set_pagelayout('admin');

// Add CSS for grading interface
$PAGE->requires->css('/local/trustgrade/grading_styles.css');

// Helper function to normalize the new JSON question format to the structure expected by the report renderer
function local_trustgrade_normalize_questions_for_report($questions) {
    // Accepts JSON string, array, or stdClass; returns array of stdClass with:
    // - question: string (mapped from "text" if present)
    // - options: string[] (mapped from options[].text if options are objects)
    // - correct_answer: int (computed from options[].correct if present)
    if (is_string($questions)) {
        $decoded = json_decode($questions);
        if (json_last_error() === JSON_ERROR_NONE) {
            $questions = $decoded;
        }
    }

    $normalized = [];
    foreach ((array)$questions as $q) {
        // Convert to object
        if (is_array($q)) {
            $q = (object)$q;
        } elseif (!is_object($q)) {
            // Fallback to basic shape if needed
            $q = (object)['question' => (string)$q];
        }

        // Map "text" -> "question" if needed
        if (!isset($q->question) && isset($q->text)) {
            $q->question = $q->text;
        }

        // Normalize options: allow strings or objects { text, explanation, correct }
        if (isset($q->options)) {
            $opts = is_array($q->options) ? $q->options : (array)$q->options;
            $optionTexts = [];
            // If already provided, keep; otherwise compute from per-option "correct"
            $correctIndex = isset($q->correct_answer) ? (int)$q->correct_answer : null;

            foreach ($opts as $idx => $opt) {
                if (is_array($opt)) {
                    // Array-form option object
                    $text = isset($opt['text']) ? (string)$opt['text'] : (string)json_encode($opt);
                    $optionTexts[] = $text;
                    if ($correctIndex === null && isset($opt['correct']) && $opt['correct']) {
                        $correctIndex = (int)$idx;
                    }
                } elseif (is_object($opt)) {
                    // Object-form option
                    $text = isset($opt->text) ? (string)$opt->text : (string)json_encode($opt);
                    $optionTexts[] = $text;
                    if ($correctIndex === null && isset($opt->correct) && $opt->correct) {
                        $correctIndex = (int)$idx;
                    }
                } else {
                    // String or scalar
                    $optionTexts[] = (string)$opt;
                }
            }

            // Preserve the option order from the session (which already reflects any randomization)
            $q->options = $optionTexts;

            // If we discovered a correct index from the new shape, expose it for the renderer
            if ($correctIndex !== null) {
                $q->correct_answer = $correctIndex;
            }
        }

        $normalized[] = $q;
    }

    return $normalized;
}

// Get completed quiz sessions
$sessions = [];
try {
    if ($cmid) {
        // Get sessions for specific assignment
        $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_assignment($cmid);
    } else if ($courseid) {
        // Get sessions for specific course
        $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_course($courseid);
    } else {
        // Get all completed sessions
        $sessions = \local_trustgrade\quiz_session::get_all_completed_sessions();
    }
} catch (Exception $e) {
    debugging('Error loading quiz sessions: ' . $e->getMessage(), DEBUG_DEVELOPER);
    $sessions = [];
}

// Normalize questions_data to ensure compatibility with report renderer and randomized order.
foreach ($sessions as $session) {
    // Normalize questions_data (supports JSON string, array, stdClass)
    $session->questions_data = local_trustgrade_normalize_questions_for_report($session->questions_data ?? []);

    // Decode answers_data if it was stored as a JSON string for robustness
    if (isset($session->answers_data) && is_string($session->answers_data)) {
        $decodedAnswers = json_decode($session->answers_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $session->answers_data = $decodedAnswers;
        }
    }
}

// Output page
echo $OUTPUT->header();

// Page heading and description
echo $OUTPUT->heading(get_string('quiz_report', 'local_trustgrade'));

if ($cmid) {
    echo html_writer::tag('p', 
        get_string('quiz_report_assignment_desc', 'local_trustgrade', $cm->name),
        ['class' => 'lead']
    );
} else if ($courseid) {
    echo html_writer::tag('p', 
        get_string('quiz_report_course_desc', 'local_trustgrade', $course->fullname),
        ['class' => 'lead']
    );
} else {
    echo html_writer::tag('p', 
        get_string('quiz_report_all_desc', 'local_trustgrade'),
        ['class' => 'lead']
    );
}

// Render the report
$renderer = $PAGE->get_renderer('local_trustgrade', 'report');
echo $renderer->render_quiz_report($sessions, $cmid);

// Back navigation
if ($cmid) {
    $back_url = new moodle_url('/mod/assign/view.php', array('id' => $cmid));
    echo html_writer::div(
        html_writer::link($back_url, get_string('back_to_assignment', 'local_trustgrade'), 
            ['class' => 'btn btn-secondary']),
        'mt-3'
    );
} else if ($courseid) {
    $back_url = new moodle_url('/course/view.php', array('id' => $courseid));
    echo html_writer::div(
        html_writer::link($back_url, get_string('back_to_course', 'local_trustgrade'), 
            ['class' => 'btn btn-secondary']),
        'mt-3'
    );
}

echo $OUTPUT->footer();
