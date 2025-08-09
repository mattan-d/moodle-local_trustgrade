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

// Helper: Convert mixed array/stdClass to array (shallow).
function tg_to_array($value) {
    if (is_array($value)) {
        return $value;
    }
    if (is_object($value)) {
        return (array)$value;
    }
    return $value;
}

// Helper: Deeply ensure an array of stdClass for renderer compatibility.
function tg_to_object($arr) {
    if (is_array($arr)) {
        $obj = new stdClass();
        foreach ($arr as $k => $v) {
            $obj->{$k} = is_array($v) ? tg_array_to_objects($v) : $v;
        }
        return $obj;
    } else if (is_object($arr)) {
        return $arr;
    }
    return $arr;
}
function tg_array_to_objects($arr) {
    $out = [];
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            // If this is a list, keep as list of scalars/objects; if associative, make stdClass.
            $islist = array_keys($v) === range(0, count($v) - 1);
            if ($islist) {
                $out[$k] = array_map(function($item) {
                    if (is_array($item)) {
                        // If item is associative array, make object; if list, recurse.
                        $islist2 = array_keys($item) === range(0, count($item) - 1);
                        return $islist2 ? tg_array_to_objects($item) : tg_to_object($item);
                    }
                    return $item;
                }, $v);
            } else {
                $out[$k] = tg_to_object($v);
            }
        } else {
            $out[$k] = $v;
        }
    }
    return $out;
}

// Helper: Normalize a question structure from new/old JSON into a consistent array.
// - Returns array with keys: question (string), type (string), options (string[]), correct_answer (?int),
//   points (int), source (?string)
function tg_normalize_session_question($sq_raw) {
    $sq = tg_to_array($sq_raw);

    // Type
    $type = isset($sq['type']) ? (string)$sq['type'] : (isset($sq['questiontype']) ? (string)$sq['questiontype'] : 'multiple_choice');

    // Question text can be under 'text' (new) or 'question' (old).
    $qtext = '';
    if (!empty($sq['text'])) {
        $qtext = (string)$sq['text'];
    } else if (!empty($sq['question'])) {
        $qtext = (string)$sq['question'];
    }

    // Points (default 10)
    $points = isset($sq['points']) && is_numeric($sq['points']) ? (int)$sq['points'] : 10;

    // Source (optional)
    $source = isset($sq['source']) ? (string)$sq['source'] : null;

    // Options: support strings or objects { text, explanation, correct }
    $optionsText = [];
    $correctIndexFromFlags = null;
    if (isset($sq['options']) && is_array($sq['options'])) {
        foreach ($sq['options'] as $idx => $opt) {
            if (is_array($opt) || is_object($opt)) {
                $optArr = tg_to_array($opt);
                $text = isset($optArr['text']) ? (string)$optArr['text'] : (isset($optArr['label']) ? (string)$optArr['label'] : '');
                $optionsText[] = $text;
                if ($correctIndexFromFlags === null && isset($optArr['correct']) && ($optArr['correct'] === true || $optArr['correct'] === 1 || $optArr['correct'] === '1')) {
                    $correctIndexFromFlags = $idx;
                }
            } else {
                // Scalar string
                $optionsText[] = (string)$opt;
            }
        }
    }

    // correct_answer may exist as index; otherwise derive from flags.
    $correctIdx = null;
    if (isset($sq['correct_answer']) && ($sq['correct_answer'] === 0 || !empty($sq['correct_answer'])) && is_numeric($sq['correct_answer'])) {
        $correctIdx = (int)$sq['correct_answer'];
    } else if ($correctIndexFromFlags !== null) {
        $correctIdx = (int)$correctIndexFromFlags;
    }

    return [
        'type' => $type,
        'question' => $qtext,
        'options' => $optionsText,
        'correct_answer' => $correctIdx,
        'points' => $points,
        'source' => $source,
    ];
}

// Helper: Normalize an original bank question (instructor) similarly.
function tg_normalize_original_question($oq_raw) {
    $oq = tg_to_array($oq_raw);
    $type = isset($oq['type']) ? (string)$oq['type'] : (isset($oq['questiontype']) ? (string)$oq['questiontype'] : 'multiple_choice');
    $qtext = '';
    if (!empty($oq['text'])) {
        $qtext = (string)$oq['text'];
    } else if (!empty($oq['question'])) {
        $qtext = (string)$oq['question'];
    }
    $points = isset($oq['points']) && is_numeric($oq['points']) ? (int)$oq['points'] : 10;
    $optionsText = [];
    if (isset($oq['options']) && is_array($oq['options'])) {
        foreach ($oq['options'] as $opt) {
            if (is_array($opt) || is_object($opt)) {
                $optArr = tg_to_array($opt);
                $optionsText[] = isset($optArr['text']) ? (string)$optArr['text'] : (isset($optArr['label']) ? (string)$optArr['label'] : '');
            } else {
                $optionsText[] = (string)$opt;
            }
        }
    }
    $correctIdx = null;
    if (isset($oq['correct_answer']) && ($oq['correct_answer'] === 0 || !empty($oq['correct_answer'])) && is_numeric($oq['correct_answer'])) {
        $correctIdx = (int)$oq['correct_answer'];
    } else if (isset($oq['options']) && is_array($oq['options'])) {
        foreach ($oq['options'] as $idx => $opt) {
            if ((is_array($opt) || is_object($opt))) {
                $optArr = tg_to_array($opt);
                if (isset($optArr['correct']) && ($optArr['correct'] === true || $optArr['correct'] === 1 || $optArr['correct'] === '1')) {
                    $correctIdx = $idx; break;
                }
            }
        }
    }

    return [
        'type' => $type,
        'question' => $qtext,
        'options' => $optionsText,
        'correct_answer' => $correctIdx,
        'points' => $points,
    ];
}

// Build a cache of original questions by cmid for quick lookup (keyed by normalized question text).
$tg_original_cache = [];

/**
 * Get original questions map for a given cmid.
 * Returns ['bytext' => [lowercased_text => questionArray], 'list' => [questionArray, ...]]
 */
function tg_get_original_questions_for_cmid($cmid) {
    static $cache = [];
    if (isset($cache[$cmid])) {
        return $cache[$cmid];
    }
    $bytext = [];
    $list = [];
    try {
        $originals = \local_trustgrade\question_generator::get_questions($cmid);
        if (is_array($originals)) {
            foreach ($originals as $oq) {
                $n = tg_normalize_original_question($oq);
                $list[] = $n;
                $key = trim(mb_strtolower((string)($n['question'] ?? '')));
                if ($key !== '') {
                    // If duplicates, keep the first one—can be extended if needed.
                    if (!isset($bytext[$key])) {
                        $bytext[$key] = $n;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // If original questions are not available, we silently continue.
        debugging('Warning: unable to load original questions for cmid '.$cmid.': '.$e->getMessage(), DEBUG_DEVELOPER);
    }
    $cache[$cmid] = ['bytext' => $bytext, 'list' => $list];
    return $cache[$cmid];
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

/**
 * Normalize questions for reporting:
 * - Base question text and points on the original bank question (when available).
 * - Preserve session-specific options order and correct_answer index (so randomization is respected).
 * - Convert to the structure expected by the existing renderer: question, options as strings, correct_answer index.
 */
foreach ($sessions as $sid => $session) {
    // Determine cmid for this session (may differ when viewing by course or all).
    $sessioncmid = isset($session->cmid) ? (int)$session->cmid : (int)$cmid;

    // Load and cache original questions for this cmid.
    $origPack = tg_get_original_questions_for_cmid($sessioncmid);
    $origByText = $origPack['bytext'];

    // Ensure session questions_data is an array.
    $sessionQuestions = isset($session->questions_data) ? (array)$session->questions_data : [];
    $normalizedQuestions = [];

    foreach ($sessionQuestions as $sq_raw) {
        $sqN = tg_normalize_session_question($sq_raw);

        // Try to find original by normalized question text.
        $textKey = trim(mb_strtolower((string)$sqN['question']));
        $orig = $textKey !== '' && isset($origByText[$textKey]) ? $origByText[$textKey] : null;

        // Build final question for report:
        // - question: prefer original text when available to "base on the original question"
        // - points: prefer original points when available
        // - options: keep session order (respects randomization)
        // - correct_answer: keep session index if present; otherwise derive from flags
        $final = [
            'type' => $sqN['type'],
            'question' => $orig && !empty($orig['question']) ? $orig['question'] : $sqN['question'],
            'options' => $sqN['options'], // session order preserved
            'correct_answer' => $sqN['correct_answer'],
            'points' => $orig && isset($orig['points']) ? (int)$orig['points'] : (int)$sqN['points'],
            'source' => isset($sqN['source']) ? $sqN['source'] : null,
        ];

        // Ensure options are strings (renderer expects string list).
        $final['options'] = array_map(function($opt) {
            return (string)$opt;
        }, is_array($final['options']) ? $final['options'] : []);

        // Ensure correct_answer is null or a valid integer within options bounds.
        if ($final['correct_answer'] !== null && is_numeric($final['correct_answer'])) {
            $idx = (int)$final['correct_answer'];
            if ($idx < 0 || $idx >= count($final['options'])) {
                // Out of bounds, nullify to avoid misleading marking.
                $final['correct_answer'] = null;
            } else {
                $final['correct_answer'] = $idx;
            }
        } else {
            $final['correct_answer'] = null;
        }

        $normalizedQuestions[] = tg_to_object($final);
    }

    // Replace questions_data with normalized array of stdClass for the renderer.
    $session->questions_data = $normalizedQuestions;

    // Keep answers_data and all other fields untouched.
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
