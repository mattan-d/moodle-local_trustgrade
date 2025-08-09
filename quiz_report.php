<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Decode a value if it is a JSON string, otherwise return as-is.
 *
 * @param mixed $value
 * @return mixed
 */
function local_trustgrade_decode_if_json($value) {
    if (is_string($value)) {
        $decoded = json_decode($value);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return $value;
}

/**
 * Normalize questions data into the legacy shape expected by the report renderer:
 * - Ensure each question has ->question (fallback to ->text).
 * - Convert options array of objects to a flat array of strings, preserving order.
 * - Derive ->correct_answer from either existing ->correct_answer or per-option ->correct.
 * - Preserve existing fields like ->points and ->source.
 *
 * This function does NOT change the order of options, so randomized answer order remains intact.
 *
 * @param mixed $questions Raw questions data (array/object/JSON string)
 * @return array Array of stdClass questions
 */
function local_trustgrade_normalize_questions_for_report($questions) {
    $questions = local_trustgrade_decode_if_json($questions);

    // Coerce to a list.
    if (is_object($questions)) {
        // If it's an object that represents an indexed list (decoded JSON), cast then take values.
        $questions = array_values((array)$questions);
    } else if (!is_array($questions)) {
        $questions = [];
    }

    $normalized = [];

    foreach ($questions as $q) {
        // Coerce each question to object.
        if (is_array($q)) {
            $q = (object)$q;
        } else if (!is_object($q)) {
            // Skip invalid entries.
            continue;
        }

        $nq = new stdClass();

        // Question text: prefer ->question, fallback to ->text.
        $nq->question = '';
        if (property_exists($q, 'question') && is_string($q->question)) {
            $nq->question = $q->question;
        } else if (property_exists($q, 'text') && is_string($q->text)) {
            $nq->question = $q->text;
        }

        // Type (default to multiple_choice if missing).
        if (property_exists($q, 'type') && is_string($q->type)) {
            $nq->type = $q->type;
        } else {
            $nq->type = 'multiple_choice';
        }

        // Preserve optional fields if present.
        if (property_exists($q, 'points')) {
            $nq->points = $q->points;
        }
        if (property_exists($q, 'source')) {
            $nq->source = $q->source;
        }

        // Normalize options: keep order exactly as provided (important for randomized results).
        $options = [];
        $derivedCorrect = null;

        if (property_exists($q, 'options') && (is_array($q->options) || is_object($q->options))) {
            $optlist = is_object($q->options) ? array_values((array)$q->options) : $q->options;

            foreach ($optlist as $idx => $opt) {
                // Accept both strings and objects for options.
                if (is_array($opt)) {
                    $opt = (object)$opt;
                }

                if (is_object($opt)) {
                    // Pull visible label from known fields, fallback to string cast.
                    $label = '';
                    if (property_exists($opt, 'text') && is_string($opt->text)) {
                        $label = $opt->text;
                    } else if (property_exists($opt, 'label') && is_string($opt->label)) {
                        $label = $opt->label;
                    } else if (property_exists($opt, 'value') && (is_string($opt->value) || is_numeric($opt->value))) {
                        $label = (string)$opt->value;
                    } else {
                        $label = (string)json_encode($opt);
                    }
                    $options[] = $label;

                    // If correct not already known, derive from per-option correct flag if present.
                    if ($derivedCorrect === null && property_exists($opt, 'correct')) {
                        $isCorrect = $opt->correct;
                        // Accept truthy variants (bool true, "true", 1, "1").
                        if ($isCorrect === true || $isCorrect === 1 || $isCorrect === '1' || $isCorrect === 'true') {
                            $derivedCorrect = $idx;
                        }
                    }
                } else {
                    // Scalar option
                    $options[] = (string)$opt;
                }
            }
        }

        $nq->options = $options;

        // Determine correct_answer priority:
        // 1) Existing numeric correct_answer; 2) Derived from per-option correct; otherwise leave unset.
        if (property_exists($q, 'correct_answer') && $q->correct_answer !== null && $q->correct_answer !== '') {
            $nq->correct_answer = (int)$q->correct_answer;
        } else if ($derivedCorrect !== null) {
            $nq->correct_answer = (int)$derivedCorrect;
        }

        $normalized[] = $nq;
    }

    return $normalized;
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
} catch (Exception $e) {
    debugging('Error loading quiz sessions: ' . $e->getMessage(), DEBUG_DEVELOPER);
    $sessions = [];
}

// Normalize questions/answers for the report renderer while preserving randomized answer order.
if (!empty($sessions)) {
    foreach ($sessions as &$session) {
        // Normalize questions_data to legacy shape
        if (isset($session->questions_data)) {
            $session->questions_data = local_trustgrade_normalize_questions_for_report($session->questions_data);
        }

        // Decode answers_data if stored as JSON string or object; keep as a simple indexed array.
        if (isset($session->answers_data)) {
            $answers = local_trustgrade_decode_if_json($session->answers_data);
            if (is_object($answers)) {
                $answers = array_values((array)$answers);
            } else if (!is_array($answers)) {
                $answers = (array)$answers; // Fallback cast
            }
            $session->answers_data = $answers;
        }
    }
    unset($session);
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
