<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Helper: safely convert mixed value into array.
 *
 * @param mixed $val
 * @return array
 */
function ltg_to_array($val): array {
    if (is_array($val)) {
        return $val;
    }
    if (is_object($val)) {
        return (array)$val;
    }
    if (is_string($val)) {
        $trim = trim($val);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
    }
    return [];
}

/**
 * Helper: safely access property from array|object.
 *
 * @param array|object $container
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function ltg_get($container, string $key, $default = null) {
    if (is_array($container)) {
        return array_key_exists($key, $container) ? $container[$key] : $default;
    }
    if (is_object($container)) {
        return property_exists($container, $key) ? $container->$key : $default;
    }
    return $default;
}

/**
 * Normalize a question object to the shape expected by the report renderer
 * while supporting the new JSON pattern:
 * - question text may be at "question" or "text"
 * - options may be strings or objects { text, explanation?, correct? }
 * - correct answer may be "correct_answer" (index) or per-option "correct": true
 *
 * Returns stdClass: {
 *   type: string,
 *   question: string,
 *   options: string[],
 *   correct_answer: int,
 *   points?: number,
 *   source?: string
 * }
 *
 * @param array|object $q
 * @return stdClass
 */
function ltg_normalize_question($q): stdClass {
    $type = (string)ltg_get($q, 'type', 'multiple_choice');

    // Question text field support: prefer "question", fall back to "text".
    $questiontext = ltg_get($q, 'question');
    if ($questiontext === null || $questiontext === '') {
        $questiontext = ltg_get($q, 'text', '');
    }
    // Renderers expect HTML-ready string; keep as provided.
    $questiontext = (string)$questiontext;

    $rawoptions = ltg_to_array(ltg_get($q, 'options', []));
    $optiontexts = [];
    $derivedCorrectIndex = null;

    foreach ($rawoptions as $idx => $opt) {
        if (is_array($opt) || is_object($opt)) {
            $text = ltg_get($opt, 'text', '');
            $optiontexts[] = (string)$text;
            $isCorrect = ltg_get($opt, 'correct', null);
            if ($isCorrect === true && $derivedCorrectIndex === null) {
                $derivedCorrectIndex = $idx;
            }
        } else {
            // Primitive (string) option
            $optiontexts[] = (string)$opt;
        }
    }

    // Determine correct index: prefer explicit "correct_answer" if numeric; otherwise from per-option "correct".
    $correctAnswer = ltg_get($q, 'correct_answer', null);
    if (is_numeric($correctAnswer)) {
        $correctIndex = (int)$correctAnswer;
    } else if ($derivedCorrectIndex !== null) {
            $correctIndex = (int)$derivedCorrectIndex;
    } else {
        // Fallback to 0 if none specified and options exist.
        $correctIndex = !empty($optiontexts) ? 0 : -1;
    }

    $out = new stdClass();
    $out->type = $type;
    $out->question = $questiontext;
    $out->options = $optiontexts;
    $out->correct_answer = $correctIndex;

    // Pass through optional metadata if present
    $points = ltg_get($q, 'points', null);
    if ($points !== null) {
        $out->points = is_numeric($points) ? 0 + $points : $points;
    }
    $source = ltg_get($q, 'source', null);
    if ($source !== null) {
        $out->source = (string)$source;
    }

    return $out;
}

/**
 * Extract option text from a session question options array given an answer value.
 * The answer may be a numeric index or a literal string; options may be strings or objects with "text".
 *
 * @param array|object $sessionQuestion
 * @param mixed $answer
 * @return string|null
 */
function ltg_get_selected_option_text($sessionQuestion, $answer): ?string {
    $opts = ltg_to_array(ltg_get($sessionQuestion, 'options', []));
    // If $answer is numeric index and within range, extract text.
    if (is_numeric($answer)) {
        $ai = (int)$answer;
        if ($ai >= 0 && $ai < count($opts)) {
            $opt = $opts[$ai];
            if (is_array($opt) || is_object($opt)) {
                $text = ltg_get($opt, 'text', '');
                return $text !== '' ? (string)$text : null;
            } else {
                return (string)$opt;
            }
        }
        return null;
    }

    // If the answer is a string, it may already be the text value.
    if (is_string($answer) && $answer !== '') {
        return $answer;
    }

    return null;
}

/**
 * Map the student's selected answer (from the potentially randomized session question)
 * to the index in the original question's options array by comparing option text.
 *
 * @param array|object $sessionQuestion
 * @param mixed $sessionAnswer
 * @param stdClass $normalizedOriginalQuestion from ltg_normalize_question
 * @return int|null Index in original options, or null if not resolvable
 */
function ltg_map_answer_to_original_index($sessionQuestion, $sessionAnswer, stdClass $normalizedOriginalQuestion): ?int {
    $selectedText = ltg_get_selected_option_text($sessionQuestion, $sessionAnswer);
    $originalOptions = isset($normalizedOriginalQuestion->options) && is_array($normalizedOriginalQuestion->options)
        ? $normalizedOriginalQuestion->options
        : [];

    if ($selectedText !== null && !empty($originalOptions)) {
        $needle = trim(mb_strtolower((string)$selectedText));
        foreach ($originalOptions as $i => $optText) {
            $cand = trim(mb_strtolower((string)$optText));
            if ($needle === $cand) {
                return $i;
            }
        }
    }

    // Fallback: if numeric and within bounds, pass through (best effort)
    if (is_numeric($sessionAnswer)) {
        $ai = (int)$sessionAnswer;
        if ($ai >= 0 && $ai < count($originalOptions)) {
            return $ai;
        }
    }

    return null;
}

/**
 * Attempt to obtain the original (non-randomized) questions list.
 * Strategy:
 * 1) If sessions include "original_questions_data", use the first available.
 * 2) Otherwise, fall back to the first session's "questions_data" as a best-effort baseline.
 *
 * @param array $sessions
 * @return array Original questions as array (possibly empty)
 */
function ltg_get_original_questions_from_sessions(array $sessions): array {
    // Search for an explicit original_questions_data payload.
    foreach ($sessions as $sess) {
        $maybe = ltg_get($sess, 'original_questions_data', null);
        if ($maybe !== null) {
            $arr = ltg_to_array($maybe);
            if (!empty($arr)) {
                return $arr;
            }
        }
    }

    // Fallback: use the first session's questions_data (best-effort)
    foreach ($sessions as $sess) {
        $arr = ltg_to_array(ltg_get($sess, 'questions_data', []));
        if (!empty($arr)) {
            return $arr;
        }
    }

    return [];
}

/**
 * Normalize sessions for reporting:
 * - Replace displayed question objects with normalized originals (ensures Correct Answer comes from original data).
 * - Remap student's answers to indices in the original options array (handles randomized delivery).
 * - Preserve points if present in session question or original.
 *
 * @param array $sessions
 * @return array normalized sessions
 */
function ltg_normalize_sessions_for_report(array $sessions): array {
    $originalQuestions = ltg_get_original_questions_from_sessions($sessions);
    $normalizedOriginals = array_map('ltg_normalize_question', $originalQuestions);

    $normalizedSessions = [];

    foreach ($sessions as $sess) {
        // Clone-like behavior: we will modify questions_data and answers_data fields only.
        $nsess = clone($sess);

        $sessionQuestions = ltg_to_array(ltg_get($sess, 'questions_data', []));
        $answers = ltg_to_array(ltg_get($sess, 'answers_data', []));

        $displayQuestions = [];
        $remappedAnswers = [];

        $qCount = max(count($sessionQuestions), count($normalizedOriginals));

        for ($i = 0; $i < $qCount; $i++) {
            $sessionQ = $sessionQuestions[$i] ?? null;
            $originalNormalized = $normalizedOriginals[$i] ?? null;

            // If we don't have an original for this index, fall back to normalizing the session question itself.
            if ($originalNormalized === null && $sessionQ !== null) {
                $originalNormalized = ltg_normalize_question($sessionQ);
            } else if ($originalNormalized === null) {
                // Construct an empty placeholder to keep indexing stable.
                $originalNormalized = (object)[
                    'type' => 'multiple_choice',
                    'question' => get_string('not_available', 'local_trustgrade'),
                    'options' => [],
                    'correct_answer' => -1,
                ];
            }

            // Preserve points from either session question or original
            $sessionPoints = $sessionQ ? ltg_get($sessionQ, 'points', null) : null;
            if ($sessionPoints !== null) {
                $originalNormalized->points = is_numeric($sessionPoints) ? 0 + $sessionPoints : $sessionPoints;
            }

            // Preserve source if present in session (useful in table badge)
            $sessionSource = $sessionQ ? ltg_get($sessionQ, 'source', null) : null;
            if ($sessionSource !== null) {
                $originalNormalized->source = (string)$sessionSource;
            }

            $displayQuestions[$i] = $originalNormalized;

            // Remap student's selected answer to the original options index space
            $sessionAnswer = $answers[$i] ?? null;
            $remapped = ltg_map_answer_to_original_index($sessionQ, $sessionAnswer, $originalNormalized);

            // If remapping failed, keep original answer for traceability
            $remappedAnswers[$i] = $remapped !== null ? $remapped : $sessionAnswer;
        }

        // Replace on the session object for the renderer
        $nsess->questions_data = $displayQuestions;
        $nsess->answers_data = $remappedAnswers;

        $normalizedSessions[] = $nsess;
    }

    return $normalizedSessions;
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

// Normalize sessions to support new JSON format and randomized answer order mapping.
// - Ensures Correct Answer comes from original question data.
// - Ensures student's selected answer is correctly mapped to original indices for scoring.
$normalized_sessions = ltg_normalize_sessions_for_report($sessions);

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

// Render the report using normalized sessions
$renderer = $PAGE->get_renderer('local_trustgrade', 'report');
echo $renderer->render_quiz_report($normalized_sessions, $cmid);

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
