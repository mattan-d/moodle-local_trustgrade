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

// Add CSS and JavaScript
$PAGE->requires->css('/local/trustgrade/styles.css');
$PAGE->requires->js_call_amd('local_trustgrade/question_bank', 'init', [$cmid]);
$PAGE->requires->js_call_amd('local_trustgrade/question_editor', 'init', [$cmid]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('question_bank', 'local_trustgrade'));

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
?>
