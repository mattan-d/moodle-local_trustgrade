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
 * Export quiz report data to Excel (including questions and answers per student).
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();

if ($cmid) {
    $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    require_capability('mod/assign:grade', $context);
} else if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($course->id);
    require_capability('moodle/course:manageactivities', $context);
} else {
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    $course = null;
}

if (ob_get_length()) {
    ob_end_clean();
}

$sessions = [];
if ($cmid) {
    $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_assignment($cmid);
} else if ($courseid) {
    $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_course($courseid);
} else {
    $sessions = \local_trustgrade\quiz_session::get_all_completed_sessions();
}

$single_cmid = $cmid;
$seen = [];
$rows = [];

foreach ($sessions as $session) {
    $key = $single_cmid ? $session->userid : ($session->userid . '_' . $session->cmid);
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;

    $session_cmid = $single_cmid ?: $session->cmid;
    $grading_manager = $session_cmid ? new \local_trustgrade\grading_manager($session_cmid) : null;
    $ratio = $grading_manager ? $grading_manager->get_session_score_ratio($session) : ['correct' => 0, 'total' => 0];
    $correct = $ratio['correct'];
    $total = $ratio['total'];
    $percentage = $total > 0 ? round(($correct / $total) * 100) : 0;

    $completed = $session->timecompleted ?: $session->timemodified;
    $duration = $completed - $session->timecreated;
    $durationstr = floor($duration / 60) . 'm ' . ($duration % 60) . 's';

    $currentgrade = '';
    if ($grading_manager) {
        $grades = $grading_manager->get_current_grades([$session->userid]);
        $currentgrade = isset($grades[$session->userid]) && $grades[$session->userid] !== null
            ? number_format($grades[$session->userid], 2) : '';
    }

    $assignmentname = (!$single_cmid && !empty($session->assignmentname)) ? $session->assignmentname : '';
    $completedstr = userdate($completed, get_string('strftimedatetimeshort'));

    $questions = (array) $session->questions_data;
    $answers = (array) $session->answers_data;

    if (empty($questions)) {
        $row = (object)[
            'fullname' => fullname($session),
            'email' => $session->email ?? '',
            'question_num' => '',
            'question_text' => '',
            'student_answer' => '',
            'result' => '',
            'total_correct' => $correct,
            'total_questions' => $total,
            'percentage' => $percentage,
            'completed' => $completedstr,
            'duration' => $durationstr,
            'grade' => $currentgrade,
        ];
        if (!$single_cmid) {
            $row->assignment = $assignmentname;
        }
        $rows[] = $row;
    }

    foreach ($questions as $qindex => $question) {
        $q = is_array($question) ? (object) $question : $question;
        $user_answer = isset($answers[$qindex]) ? $answers[$qindex] : null;

        $question_text = '';
        if (isset($q->text) && $q->text !== '') {
            $question_text = trim(strip_tags($q->text));
        } else if (isset($q->question) && $q->question !== '') {
            $question_text = trim(strip_tags($q->question));
        }

        $answer_text = '';
        $is_correct = false;
        if ($grading_manager) {
            $info = $grading_manager->get_question_answer_export_info($q, $user_answer);
            $answer_text = $info['answer_text'];
            $is_correct = $info['is_correct'];
        }

        $row = (object)[
            'fullname' => fullname($session),
            'email' => $session->email ?? '',
            'question_num' => $qindex + 1,
            'question_text' => $question_text,
            'student_answer' => $answer_text,
            'result' => $is_correct ? get_string('correct', 'local_trustgrade') : get_string('incorrect', 'local_trustgrade'),
            'total_correct' => $correct,
            'total_questions' => $total,
            'percentage' => $percentage,
            'completed' => $completedstr,
            'duration' => $durationstr,
            'grade' => $currentgrade,
        ];
        if (!$single_cmid) {
            $row->assignment = $assignmentname;
        }
        $rows[] = $row;
    }
}

$columns = [
    'fullname' => get_string('fullname'),
    'email' => get_string('email'),
    'question_num' => get_string('question', 'local_trustgrade') . ' #',
    'question_text' => get_string('question', 'local_trustgrade') . ' - ' . get_string('text', 'local_trustgrade'),
    'student_answer' => get_string('student_answer', 'local_trustgrade'),
    'result' => get_string('result', 'local_trustgrade'),
    'total_correct' => get_string('correct', 'local_trustgrade'),
    'total_questions' => get_string('total'),
    'percentage' => get_string('percentage', 'grades'),
    'completed' => get_string('completed_on', 'local_trustgrade'),
    'duration' => get_string('time_taken', 'local_trustgrade'),
    'grade' => get_string('final_grade', 'local_trustgrade'),
];
if (!$single_cmid) {
    $columns['assignment'] = get_string('assignment', 'assign');
}

// Reorder row keys to match column order (assignment after email when present).
$column_keys = array_keys($columns);

$filename = 'trustgrade_quiz_report_' . ($cmid ? $cmid : ($courseid ? 'course_' . $courseid : 'all'));
$filename = clean_filename($filename);

\core\dataformat::download_data($filename, 'excel', $columns, $rows, function ($row) use ($column_keys) {
    $arr = (array) $row;
    $out = [];
    foreach ($column_keys as $k) {
        $out[$k] = $arr[$k] ?? '';
    }
    return $out;
});
