<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
if (!$cm) {
    // Provide user-friendly error message instead of exception
    print_error('invalidcoursemodule', 'error');
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

?>

<div class="question-generation-section mb-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo get_string('generate_questions', 'local_trustgrade'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="questions-count" class="form-label">
                        <?php echo get_string('number_of_questions', 'local_trustgrade'); ?>
                    </label>
                    <input type="number" id="questions-count" class="form-control" 
                           value="5" min="1" max="10">
                </div>
                <div class="col-md-9">
                    <button type="button" id="generate-new-questions" class="btn btn-primary">
                        <i class="fa fa-magic"></i> <?php echo get_string('generate_questions', 'local_trustgrade'); ?>
                    </button>
                    <div id="generation-loading" class="spinner-border spinner-border-sm ms-2" 
                         style="display: none;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

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
