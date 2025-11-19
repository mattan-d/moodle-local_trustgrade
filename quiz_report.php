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
 * Quiz report interface for viewing student quiz results and statistics.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
