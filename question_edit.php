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
 * View and edit a single question (standalone page).
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$questionid = optional_param('id', 0, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
if (!$cm) {
    throw new moodle_exception('invalidcoursemodule', 'error');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);

if (!get_config('local_trustgrade', 'plugin_enabled')) {
    throw new moodle_exception('plugindisabled', 'local_trustgrade');
}

$settings = \local_trustgrade\quiz_settings::get_settings($cmid);
if (!$settings['enabled']) {
    throw new moodle_exception('trustgradedisabled', 'local_trustgrade');
}

$isnew = ($questionid <= 0);
$question = null;

if (!$isnew) {
    $record = $DB->get_record('local_trustgrade_questions', ['id' => $questionid, 'cmid' => $cmid], '*', IGNORE_MISSING);
    if (!$record) {
        throw new moodle_exception('question_not_found_error', 'local_trustgrade');
    }
    $question = json_decode($record->question_data, true);
    if (!$question) {
        throw new moodle_exception('invalidrecord', 'error');
    }
    $question['is_mandatory'] = isset($record->is_mandatory) ? (int) $record->is_mandatory : 0;
    $question['db_id'] = $record->id;
    $question['id'] = $record->id;
} else {
    $question = [
        'id' => 0,
        'type' => 'multiple_choice',
        'text' => '',
        'options' => [
            ['text' => '', 'is_correct' => true, 'explanation' => ''],
            ['text' => '', 'is_correct' => false, 'explanation' => ''],
            ['text' => '', 'is_correct' => false, 'explanation' => ''],
            ['text' => '', 'is_correct' => false, 'explanation' => ''],
        ],
        'metadata' => ['points' => 10, 'blooms_level' => ''],
        'is_mandatory' => 0,
    ];
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtext = optional_param('qtext', '', PARAM_RAW);
    $blooms = optional_param('blooms', '', PARAM_ALPHA);
    $mandatory = optional_param('mandatory', 0, PARAM_INT);
    $correct_index = optional_param('correct_index', 0, PARAM_INT);
    $option_text = optional_param_array('option_text', [], PARAM_RAW);
    $option_explanation = optional_param_array('option_explanation', [], PARAM_RAW);

    $options = [];
    for ($i = 0; $i < 4; $i++) {
        $options[] = [
            'id' => $i + 1,
            'text' => isset($option_text[$i]) ? $option_text[$i] : '',
            'is_correct' => ((int) $correct_index === $i),
            'explanation' => isset($option_explanation[$i]) ? $option_explanation[$i] : '',
        ];
    }

    $question_data = [
        'type' => 'multiple_choice',
        'text' => $qtext,
        'metadata' => ['points' => 10, 'blooms_level' => $blooms],
        'is_mandatory' => $mandatory ? 1 : 0,
        'options' => $options,
    ];

    if (!$isnew) {
        $result = \local_trustgrade\question_editor::update_question_by_id($questionid, $cmid, $question_data);
    } else {
        $existing = \local_trustgrade\question_generator::get_questions($cmid);
        $result = \local_trustgrade\question_editor::save_question($cmid, count($existing), $question_data);
    }

    if (!empty($result['success'])) {
        \core\notification::success($result['message']);
        redirect(new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]));
    } else {
        \core\notification::error($result['error']);
    }
}

$editurl = new moodle_url('/local/trustgrade/question_edit.php', ['cmid' => $cmid, 'id' => $questionid]);
$PAGE->set_url($editurl);
$PAGE->set_title(get_string('edit_question_page', 'local_trustgrade'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->requires->css('/local/trustgrade/styles.css');

echo $OUTPUT->header();

$backurl = new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]);
echo html_writer::link($backurl, '&larr; ' . get_string('back_to_question_bank', 'local_trustgrade'), ['class' => 'btn btn-outline-secondary mb-4']);

if (!$isnew) {
    echo html_writer::tag('h4', get_string('view_question', 'local_trustgrade'), ['class' => 'mt-4']);
    echo html_writer::div(
        \local_trustgrade\question_bank_renderer::render_question_view_content($question),
        'card card-body mb-4'
    );
}

echo html_writer::tag('h4', $isnew ? get_string('add_new_question', 'local_trustgrade') : get_string('edit', 'local_trustgrade') . ' ' . get_string('question', 'local_trustgrade'), ['class' => 'mt-2']);
echo \local_trustgrade\question_bank_renderer::render_question_edit_form_for_page($question, $editurl, $cmid);

echo $OUTPUT->footer();
