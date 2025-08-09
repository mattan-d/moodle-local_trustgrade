<?php
// This file is part of Moodle - http://moodle.org/
//
// Local plugin: local_trustgrade - Quiz report page
//
// Purpose: Display completed quiz sessions and results while supporting the new
// JSON format for questions where each option can include:
//   - text: string
//   - is_correct (preferred) | correct | isCorrect: boolean for correctness
//   - explanation: string (optional, not required to display here)
// It also preserves answer order as stored in the session (to respect randomization).

require_once(__DIR__ . '/../../config.php');

use local_trustgrade\quiz_session;

$cmid = required_param('cmid', PARAM_INT);

// Load required Moodle records.
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cmid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/trustgrade/quiz_report.php', ['cmid' => $cmid]));
$PAGE->set_title(get_string('pluginname', 'local_trustgrade') . ' - ' . get_string('quizreport', 'local_trustgrade', null, true));
$PAGE->set_heading(format_string($course->fullname, true));

echo $OUTPUT->header();

// Fetch all completed sessions for this assignment/module.
$sessions = quiz_session::get_completed_sessions_for_assignment($cmid);

// Helper: safely read a property from array|object.
function ltg_prop($container, $key, $default = null) {
    if (is_array($container) && array_key_exists($key, $container)) {
        return $container[$key];
    }
    if (is_object($container) && isset($container->$key)) {
        return $container->$key;
    }
    return $default;
}

// Helper: convert answers_data to array (index => selected index/value).
function ltg_answers_to_array($answers) {
    if (is_array($answers)) {
        return $answers;
    }
    if (is_object($answers)) {
        // Cast stdClass to array while preserving integer-like keys.
        $arr = [];
        foreach ($answers as $k => $v) {
            // Normalize keys to int when possible.
            $ik = is_numeric($k) ? (int)$k : $k;
            $arr[$ik] = $v;
        }
        return $arr;
    }
    return [];
}

// Helper: get display text for an option that can be string|object.
function ltg_option_text($opt) {
    if (is_string($opt)) {
        return $opt;
    }
    if (is_object($opt) || is_array($opt)) {
        $text = ltg_prop($opt, 'text');
        if ($text !== null && $text !== '') {
            return $text;
        }
        $label = ltg_prop($opt, 'label');
        if ($label !== null && $label !== '') {
            return $label;
        }
        // Fallback stringify.
        return is_object($opt) ? json_encode($opt) : (string)json_encode($opt);
    }
    return (string)$opt;
}

// Helper: compute correct index from per-option flags (new format) or fallback.
function ltg_compute_correct_index($question) {
    $options = ltg_prop($question, 'options', []);
    if (!is_array($options)) {
        // If options came as stdClass, convert to array preserving order.
        if (is_object($options)) {
            $tmp = [];
            foreach ($options as $v) { $tmp[] = $v; }
            $options = $tmp;
        } else {
            $options = [];
        }
    }

    // Preferred: per-option "is_correct" flag (also accept "correct" / "isCorrect").
    foreach ($options as $i => $opt) {
        $isCorrect = false;
        if (is_object($opt) || is_array($opt)) {
            $flag = ltg_prop($opt, 'is_correct');
            if ($flag === null) { $flag = ltg_prop($opt, 'correct'); }
            if ($flag === null) { $flag = ltg_prop($opt, 'isCorrect'); }
            $isCorrect = (bool)$flag;
        }
        if ($isCorrect) {
            return (int)$i;
        }
    }

    // Backward-compatible: numeric correct_answer field on the question.
    $fallback = ltg_prop($question, 'correct_answer', null);
    if (is_numeric($fallback)) {
        return (int)$fallback;
    }

    // No correctness known.
    return null;
}

// Helper: normalize question into a consistent structure for display.
function ltg_normalize_question($question) {
    // Accept array or object
    $q = $question;

    $text = ltg_prop($q, 'text');
    if ($text === null || $text === '') {
        $text = ltg_prop($q, 'question', '');
    }

    // Normalize options into an ordered array of raw option entries
    $rawoptions = ltg_prop($q, 'options', []);
    if (!is_array($rawoptions)) {
        if (is_object($rawoptions)) {
            // Convert stdClass list to array preserving order
            $tmp = [];
            foreach ($rawoptions as $v) { $tmp[] = $v; }
            $rawoptions = $tmp;
        } else {
            $rawoptions = [];
        }
    }

    $optionsText = [];
    foreach ($rawoptions as $opt) {
        $optionsText[] = ltg_option_text($opt);
    }

    $correctIndex = ltg_compute_correct_index($q);

    $type = ltg_prop($q, 'type', 'multiple_choice');

    return [
        'type' => $type,
        'question' => $text,
        'options' => $rawoptions,     // keep original entries for potential future use
        'options_text' => $optionsText,
        'correct_index' => $correctIndex,
        'points' => ltg_prop($q, 'points', 10),
    ];
}

// Helper: format a user full name (fallback if missing fields).
function ltg_user_fullname($session) {
    $first = isset($session->firstname) ? $session->firstname : '';
    $last = isset($session->lastname) ? $session->lastname : '';
    $name = trim($first . ' ' . $last);
    return $name !== '' ? $name : (isset($session->email) ? $session->email : get_string('user'));
}

// Render

echo html_writer::tag('h2', get_string('quizreport', 'local_trustgrade', null, true));

if (empty($sessions)) {
    echo $OUTPUT->notification(get_string('no_completed_quizzes', 'local_trustgrade'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Iterate sessions and render results.
foreach ($sessions as $session) {
    // Normalize per-question and compute correctness using per-option flags.
    $questions = $session->questions_data; // already json_decoded in quiz_session::get_completed_sessions_for_assignment
    if (!is_array($questions)) {
        if (is_object($questions)) {
            $tmp = [];
            foreach ($questions as $v) { $tmp[] = $v; }
            $questions = $tmp;
        } else {
            $questions = [];
        }
    }

    $answers = ltg_answers_to_array($session->answers_data);

    // Attempt settings (to indicate if randomization was enabled). We don't shuffle here; we respect stored order.
    $settings = isset($session->settings_data) ? $session->settings_data : null;
    if (is_string($settings)) {
        $settings = json_decode($settings);
    }
    $randomized = false;
    if (is_object($settings) || is_array($settings)) {
        $randomized = (bool) ltg_prop($settings, 'randomize_answers', false);
    }

    // Session header
    $userheading = format_string(ltg_user_fullname($session), true);
    $randomlabel = $randomized ? get_string('yes') : get_string('no');
    echo html_writer::start_div('local-trustgrade-session card mb-4');
    echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
    echo html_writer::tag('div', $userheading, ['class' => 'font-weight-bold']);
    echo html_writer::tag('div', get_string('email') . ': ' . s($session->email));
    echo html_writer::tag('div', get_string('randomizeanswers', 'local_trustgrade', null, true) . ': ' . $randomlabel, ['class' => 'text-muted']);
    echo html_writer::end_div(); // card-header

    echo html_writer::start_div('card-body');

    // Results table
    $table = new html_table();
    $table->head = [
        get_string('question'),
        get_string('answers', 'local_trustgrade', null, true),
        get_string('selected', 'local_trustgrade', null, true),
        get_string('result', 'local_trustgrade', null, true),
        get_string('points', 'local_trustgrade', null, true),
    ];
    $table->data = [];

    $totalpoints = 0;
    $scored = 0;

    foreach ($questions as $qindex => $qraw) {
        $norm = ltg_normalize_question($qraw);
        $totalpoints += (int)$norm['points'];

        // Determine user's answer index/value.
        $userval = array_key_exists($qindex, $answers) ? $answers[$qindex] : null;
        $userindex = null;
        if (is_numeric($userval)) {
            $userindex = (int)$userval;
        } else if ($userval === true || $userval === false) {
            // Map boolean to index for legacy true/false (assume [false, true] typical ordering, but keep stored order as-is)
            // We'll attempt to align to common 2-option TF: index 1 = true, index 0 = false, as used in the AMD module.
            $userindex = $userval ? 1 : 0;
        }

        // Correctness
        $isCorrect = ($userindex !== null && $norm['correct_index'] !== null && $userindex === (int)$norm['correct_index']);
        if ($isCorrect) {
            $scored += (int)$norm['points'];
        }

        // Build options HTML in the exact stored order (respecting any prior randomization).
        $optionshtml = '';
        foreach ($norm['options_text'] as $i => $label) {
            $classes = ['ltg-option', 'd-flex', 'align-items-center', 'mb-1'];
            $badge = '';
            // Mark the correct option - it's a report for instructors; showing correctness is appropriate.
            if ($norm['correct_index'] !== null && (int)$norm['correct_index'] === (int)$i) {
                $badge = html_writer::span(get_string('correct', 'local_trustgrade'), 'badge badge-success ml-2');
            }
            // Mark the user's selected option
            $selectedMark = ($userindex !== null && (int)$userindex === (int)$i) ? '&#9679;' : '&#9711;'; // filled/empty circle
            $optionshtml .= html_writer::div(
                html_writer::span($selectedMark, 'mr-2') . s($label) . ' ' . $badge,
                implode(' ', $classes)
            );
        }

        $selectedtext = ($userindex !== null && array_key_exists($userindex, $norm['options_text']))
            ? s($norm['options_text'][$userindex])
            : html_writer::span(get_string('no_answer', 'local_trustgrade'), 'text-muted');

        $resultbadge = $isCorrect
            ? html_writer::span(get_string('correct', 'local_trustgrade'), 'badge badge-success')
            : html_writer::span(get_string('incorrect', 'local_trustgrade'), 'badge badge-danger');

        $pointscol = ($isCorrect ? (int)$norm['points'] : 0) . '/' . (int)$norm['points'];

        $table->data[] = [
            format_text($norm['question'], FORMAT_HTML, ['context' => $context]),
            $optionshtml,
            $selectedtext,
            $resultbadge,
            $pointscol,
        ];
    }

    echo html_writer::table($table);

    // Summary
    $percentage = $totalpoints > 0 ? round(($scored / $totalpoints) * 100) : 0;
    $summary = get_string('final_score', 'local_trustgrade', ['score' => $scored, 'total' => $totalpoints, 'percentage' => $percentage]);
    echo html_writer::div(html_writer::tag('strong', $summary), 'alert alert-info mt-3');

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
}

echo $OUTPUT->footer();
