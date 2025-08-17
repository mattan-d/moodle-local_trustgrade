<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
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
            <div class="col-md-8">
                <p class="mb-0"><?php echo get_string('question_bank_description', 'local_trustgrade'); ?></p>
            </div>
            <div class="col-md-4">
                <div class="generate-questions-section d-flex align-items-center justify-content-end gap-2">
                    <label for="questions-count" class="mb-0"><?php echo get_string('questions_to_generate', 'local_trustgrade'); ?>:</label>
                    <select id="questions-count" class="form-control" style="width: 80px;">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button id="generate-new-questions" class="btn btn-primary">
                        <i class="fa fa-plus"></i> <?php echo get_string('generate_questions', 'local_trustgrade'); ?>
                    </button>
                </div>
                <div id="generation-loading" style="display: none;" class="text-center mt-2">
                    <i class="fa fa-spinner fa-spin"></i> <?php echo get_string('generating_questions', 'local_trustgrade'); ?>
                </div>
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
