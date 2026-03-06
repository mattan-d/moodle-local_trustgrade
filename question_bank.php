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
 * Question bank interface for viewing and managing AI-generated questions.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/form/generate_from_files_form.php');
require_once($CFG->dirroot . '/local/trustgrade/classes/form/edit_instructions_form.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
if (!$cm) {
    throw new moodle_exception('invalidcoursemodule', 'error');
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$assign = new assign(context_module::instance($cm->id), $cm, $course);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);

// Check if plugin is enabled
if (!get_config('local_trustgrade', 'plugin_enabled')) {
    throw new moodle_exception('plugindisabled', 'local_trustgrade');
}

// Check if TrustGrade is enabled for this activity
$settings = \local_trustgrade\quiz_settings::get_settings($cmid);
if (!$settings['enabled']) {
    throw new moodle_exception('trustgradedisabled', 'local_trustgrade');
}

$PAGE->set_url('/local/trustgrade/question_bank.php', array('cmid' => $cmid));
$PAGE->set_title(get_string('question_bank', 'local_trustgrade'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Form for generating questions from uploaded files.
$form = new \local_trustgrade\form\generate_from_files_form(null, ['cmid' => $cmid]);
$draftitemid = file_get_submitted_draft_itemid('generatefiles');
if (empty($draftitemid)) {
    $draftitemid = file_get_unused_draft_itemid();
}
$form->set_data(['generatefiles' => $draftitemid, 'cmid' => $cmid]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]));
}

if ($data = $form->get_data()) {
    $instructions = isset($data->instructions) ? trim($data->instructions) : '';
    $files = \local_trustgrade\external::collect_intro_files((int) $data->generatefiles, 0);
    if (empty($files) && $instructions === '') {
        \core\notification::error(get_string('no_instructions_or_files', 'local_trustgrade'));
    } else {
        $questioncount = isset($data->questioncount) ? (int) $data->questioncount : 5;
        $questioncount = max(1, min(50, $questioncount));
        try {
            \local_trustgrade\instructor_generation_manager::create_task(
                $cmid,
                $USER->id,
                $instructions,
                $questioncount,
                $files
            );
            \core\notification::success(get_string('generation_started', 'local_trustgrade'));
            redirect(new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]));
        } catch (\Throwable $e) {
            \core\notification::error(get_string('error_generating_questions', 'local_trustgrade') . ': ' . $e->getMessage());
        }
    }
}

// Editable instructions form (intro + activity) and "Check instructions" section.
$assigninstance = $assign->get_instance();
$editform = new \local_trustgrade\form\edit_instructions_form(null, ['cmid' => $cmid, 'context' => $context]);
$introeditordefault = [];
$activityeditordefault = [];
$introdraftid = file_get_submitted_draft_itemid('introeditor');
if (!$introdraftid) {
    $introdraftid = file_get_unused_draft_itemid();
}
$introtext = file_prepare_draft_area($introdraftid, $context->id, 'mod_assign', 'intro', 0, ['subdirs' => true], $assigninstance->intro ?? '');
$introeditordefault = ['text' => $introtext, 'format' => $assigninstance->introformat ?? FORMAT_HTML, 'itemid' => $introdraftid];
$activitydraftid = file_get_submitted_draft_itemid('activityeditor');
if (!$activitydraftid) {
    $activitydraftid = file_get_unused_draft_itemid();
}
if (!empty($assigninstance->activity)) {
    $activitytext = file_prepare_draft_area($activitydraftid, $context->id, 'mod_assign', ASSIGN_ACTIVITYATTACHMENT_FILEAREA, 0, ['subdirs' => true], $assigninstance->activity);
    $activityeditordefault = ['text' => $activitytext, 'format' => $assigninstance->activityformat ?? FORMAT_HTML, 'itemid' => $activitydraftid];
} else {
    $activityeditordefault = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => $activitydraftid];
}
$editform->set_data([
    'cmid' => $cmid,
    'introeditor' => $introeditordefault,
    'activityeditor' => $activityeditordefault,
]);
if ($editdata = $editform->get_data()) {
    // Save intro to assign.
    $newintro = $editdata->introeditor['text'];
    if (isset($editdata->introeditor['itemid']) && $editdata->introeditor['itemid']) {
        $newintro = file_save_draft_area_files($editdata->introeditor['itemid'], $context->id, 'mod_assign', 'intro', 0, ['subdirs' => true], $editdata->introeditor['text']);
    }
    $DB->set_field('assign', 'intro', $newintro, ['id' => $assigninstance->id]);
    $DB->set_field('assign', 'introformat', $editdata->introeditor['format'], ['id' => $assigninstance->id]);
    // Save activity to assign.
    $newactivity = $editdata->activityeditor['text'] ?? '';
    if (isset($editdata->activityeditor['itemid']) && $editdata->activityeditor['itemid']) {
        $newactivity = file_save_draft_area_files($editdata->activityeditor['itemid'], $context->id, 'mod_assign', ASSIGN_ACTIVITYATTACHMENT_FILEAREA, 0, ['subdirs' => true], $editdata->activityeditor['text']);
    }
    $DB->set_field('assign', 'activity', $newactivity, ['id' => $assigninstance->id]);
    $DB->set_field('assign', 'activityformat', $editdata->activityeditor['format'] ?? FORMAT_HTML, ['id' => $assigninstance->id]);
    $assign->get_instance(true); // Reload instance.
    \core\notification::success(get_string('changessaved', 'moodle'));
    redirect(new moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]));
}
// For "Check instructions" JS: use current saved values (form may have been edited but not submitted).
$introplain = trim(strip_tags($assigninstance->intro ?? ''));
$activityplain = !empty($assigninstance->activity) ? trim(strip_tags($assigninstance->activity)) : '';
$instructionsforai = trim($introplain . "\n\n" . $activityplain);
$fs = get_file_storage();
$introfiles = $fs->get_area_files($context->id, 'mod_assign', 'intro', 0, 'sortorder, id', false) ?: [];
$attachfiles = $fs->get_area_files($context->id, 'mod_assign', 'introattachment', 0, 'sortorder, id', false) ?: [];
$assignmentfilelist = [];
foreach (array_merge($introfiles, $attachfiles) as $f) {
    if ($f->is_directory()) {
        continue;
    }
    $assignmentfilelist[] = [
        'name' => $f->get_filename(),
        'url' => \moodle_url::make_pluginfile_url(
            $f->get_contextid(),
            $f->get_component(),
            $f->get_filearea(),
            $f->get_itemid(),
            $f->get_filepath(),
            $f->get_filename(),
            true
        )->out(false),
    ];
}
$lastrecommendation = $DB->get_record_sql(
    "SELECT recommendation FROM {local_trustgrade_logs} WHERE cmid = ? AND userid = ? ORDER BY timecreated DESC LIMIT 1",
    [$cmid, $USER->id]
);
$lastrecommendationjson = $lastrecommendation && !empty($lastrecommendation->recommendation)
    ? $lastrecommendation->recommendation
    : '';

// Add CSS and JavaScript
$PAGE->requires->css('/local/trustgrade/styles.css');
$PAGE->requires->js_call_amd('local_trustgrade/question_bank', 'init', [$cmid]);
$PAGE->requires->js_call_amd('local_trustgrade/question_editor', 'init', [$cmid]);
$PAGE->requires->js_call_amd('local_trustgrade/question_bank_status', 'init', [$cmid]);
$PAGE->requires->js_call_amd('local_trustgrade/trustgrade', 'initQuestionBank', [$cmid, $instructionsforai, $lastrecommendationjson]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('question_bank', 'local_trustgrade'));

// Status of instructor question generation (adhoc).
echo html_writer::div('', 'instructor-generation-status', ['id' => 'instructor-generation-status']);

// Assignment instructions (editable) and files + "Check instructions with AI" at the top.
echo html_writer::start_div('card mb-4', ['id' => 'trustgrade-instruction-review']);
echo html_writer::div(get_string('assignment_instructions_and_files', 'local_trustgrade'), 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag('p', get_string('edit_instructions_help', 'local_trustgrade'), ['class' => 'text-muted small mb-3']);
$editform->display();
if (!empty($assignmentfilelist)) {
    echo html_writer::tag('h6', get_string('assignment_files_list', 'local_trustgrade'), ['class' => 'mb-2 mt-3']);
    echo html_writer::start_tag('ul', ['class' => 'list-unstyled mb-3']);
    foreach ($assignmentfilelist as $fl) {
        echo html_writer::tag('li', html_writer::link($fl['url'], $fl['name'], ['target' => '_blank']));
    }
    echo html_writer::end_tag('ul');
}
echo html_writer::start_div('mb-3');
echo html_writer::tag('button', get_string('check_instructions', 'local_trustgrade'), [
    'type' => 'button',
    'id' => 'check-instructions-btn',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();
    echo html_writer::div(
        '<span class="trustgrade-loading-content"><i class="fa fa-spinner fa-spin me-2" aria-hidden="true"></i>' .
        get_string('processing', 'local_trustgrade') . '</span>',
        'alert alert-secondary d-none trustgrade-loading-indicator',
        ['id' => 'ai-loading', 'role' => 'status', 'aria-live' => 'polite']
    );
echo html_writer::start_div('', ['id' => 'ai-recommendation-container', 'style' => empty($lastrecommendationjson) ? 'display: none;' : '']);
echo html_writer::tag('h6', get_string('ai_recommendation', 'local_trustgrade'), ['class' => 'mb-2']);
echo html_writer::div('', 'alert alert-info', ['id' => 'ai-recommendation']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Help text for creating questions from files.
echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('generate_from_files_help', 'local_trustgrade'), 'card-body');
echo html_writer::end_div();

// Show "Create questions from files" form in a box.
echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('generate_from_files', 'local_trustgrade'), 'card-header');
echo html_writer::start_div('card-body');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();

$questions = \local_trustgrade\question_generator::get_questions($cmid);
?>

<div class="question-bank-container">
    <div class="question-bank-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-12">
                <p class="mb-0"><?php echo get_string('question_bank_description', 'local_trustgrade'); ?></p>
            </div>
        </div>
    </div>

    <div class="question-bank-content">
        <?php if (empty($questions)): ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <?php echo get_string('no_questions_found', 'local_trustgrade'); ?>
            </div>
        <?php else: ?>
            <?php
            echo \local_trustgrade\question_bank_renderer::render_editable_questions($questions, $cmid);
            ?>
        <?php endif; ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
