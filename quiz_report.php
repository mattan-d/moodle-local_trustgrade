<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Normalize question objects to the legacy structure expected by the report renderer.
 * - Ensure $q->question is populated (from $q->text if necessary)
 * - For multiple_choice:
 *     - Convert $q->options from array of objects to array of strings (using ->text if present)
 *     - Infer $q->correct_answer from the first option with truthy ->correct or ->isCorrect when not provided
 *
 * This function does not remove any fields; it only adds/derives legacy-compatible ones.
 *
 * @param array|\stdClass[] $questions
 * @return array normalized questions
 */
if (!function_exists('local_trustgrade_normalize_questions_for_report')) {
    function local_trustgrade_normalize_questions_for_report($questions) {
        if (!is_array($questions)) {
            $questions = (array)$questions;
        }

        $normalized = [];
        foreach ($questions as $q) {
            if (is_array($q)) {
                $q = (object)$q;
            }
            if (!$q instanceof stdClass) {
                // Skip invalid entries gracefully
                continue;
            }

            // Map new JSON field 'text' to legacy 'question' if needed
            if (!isset($q->question) && isset($q->text)) {
                $q->question = $q->text;
            }

            // Handle multiple choice options normalization
            if (isset($q->type) && $q->type === 'multiple_choice' && isset($q->options) && is_array($q->options)) {
                $options = $q->options;

                // If options are objects with { text, explanation, correct }, convert to array of strings for renderer
                $first = $options[0] ?? null;
                $areobjects = is_object($first);

                if ($areobjects) {
                    // Extract visible option text for the renderer
                    $flatOptions = [];
                    foreach ($options as $idx => $opt) {
                        if (is_object($opt)) {
                            // Use ->text if available, otherwise stringify
                            $flatOptions[$idx] = isset($opt->text) ? (string)$opt->text : (string)json_encode($opt);
                        } else {
                            $flatOptions[$idx] = (string)$opt;
                        }
                    }
                    $q->options = array_values($flatOptions);

                    // Infer correct_answer if missing and any option marks correct/isCorrect
                    if (!isset($q->correct_answer)) {
                        $correctIndex = null;
                        foreach ($options as $idx => $opt) {
                            $isCorrect = false;
                            if (is_object($opt)) {
                                if ((isset($opt->correct) && $opt->correct) || (isset($opt->isCorrect) && $opt->isCorrect)) {
                                    $isCorrect = true;
                                }
                            }
                            if ($isCorrect) {
                                $correctIndex = $idx;
                                break;
                            }
                        }
                        if ($correctIndex !== null) {
                            $q->correct_answer = (int)$correctIndex;
                        }
                    }
                } else {
                    // Keep as-is, but ensure correct_answer is an int if present
                    if (isset($q->correct_answer)) {
                        $q->correct_answer = (int)$q->correct_answer;
                    }
                }
            }

            // Push normalized question
            $normalized[] = $q;
        }

        return $normalized;
    }
}

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

    // Normalize questions in each session to support new JSON shape.
    foreach ($sessions as $s) {
        // questions_data may be a JSON string or already an array/object; ensure array of objects
        if (isset($s->questions_data)) {
            $qdata = $s->questions_data;

            // If it's a JSON string, decode
            if (is_string($qdata)) {
                $decoded = json_decode($qdata);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $qdata = $decoded;
                }
            }

            // Now normalize structure for the renderer
            $normalized = local_trustgrade_normalize_questions_for_report($qdata ?? []);
            $s->questions_data = $normalized;
        }
    }
} catch (Exception $e) {
    debugging('Error loading quiz sessions: ' . $e->getMessage(), DEBUG_DEVELOPER);
    $sessions = [];
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
