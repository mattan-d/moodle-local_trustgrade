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

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('question_bank', 'local_trustgrade'));

// Get existing questions
$questions = $DB->get_records('local_trustgrade_questions', array('cmid' => $cmid), 'id ASC');

?>

<div class="question-bank-container">
    <div class="question-bank-header">
        <div class="row">
            <div class="col-md-8">
                <p><?php echo get_string('question_bank_description', 'local_trustgrade'); ?></p>
            </div>
            <div class="col-md-4 text-right">
                <div class="generate-questions-section">
                    <label for="questions-count"><?php echo get_string('questions_to_generate', 'local_trustgrade'); ?>:</label>
                    <select id="questions-count" class="form-control d-inline-block" style="width: auto; margin: 0 10px;">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button id="generate-new-questions" class="btn btn-primary">
                        <i class="fa fa-plus"></i> <?php echo get_string('generate_questions', 'local_trustgrade'); ?>
                    </button>
                </div>
                <div id="generation-loading" style="display: none; margin-top: 10px;">
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
            <div class="questions-list">
                <?php foreach ($questions as $question): ?>
                    <?php
                    $question_data = json_decode($question->question_data, true);
                    if (!$question_data) continue;
                    ?>
                    <div class="question-item card mb-3" data-question-id="<?php echo $question->id; ?>">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <?php echo get_string('question', 'local_trustgrade') . ' #' . $question->id; ?>
                                        <span class="badge badge-secondary ml-2"><?php echo ucfirst($question_data['type']); ?></span>
                                    </h5>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-sm btn-outline-primary edit-question" data-question-id="<?php echo $question->id; ?>">
                                        <i class="fa fa-edit"></i> <?php echo get_string('edit', 'local_trustgrade'); ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-question" data-question-id="<?php echo $question->id; ?>">
                                        <i class="fa fa-trash"></i> <?php echo get_string('delete', 'local_trustgrade'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="question-text">
                                <strong><?php echo get_string('question_text', 'local_trustgrade'); ?>:</strong>
                                <p><?php echo htmlspecialchars($question_data['text']); ?></p>
                            </div>
                            
                            <?php if ($question_data['type'] === 'multiple_choice' && isset($question_data['options'])): ?>
                                <div class="question-options">
                                    <strong><?php echo get_string('options', 'local_trustgrade'); ?>:</strong>
                                    <ul class="list-unstyled ml-3">
                                        <?php foreach ($question_data['options'] as $option): ?>
                                            <li class="<?php echo $option['is_correct'] ? 'text-success font-weight-bold' : ''; ?>">
                                                <?php echo $option['is_correct'] ? '<i class="fa fa-check"></i>' : '<i class="fa fa-circle-o"></i>'; ?>
                                                <?php echo htmlspecialchars($option['text']); ?>
                                                <?php if (!empty($option['explanation'])): ?>
                                                    <small class="text-muted d-block ml-3"><?php echo htmlspecialchars($option['explanation']); ?></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($question_data['metadata'])): ?>
                                <div class="question-metadata">
                                    <small class="text-muted">
                                        <?php if (isset($question_data['metadata']['blooms_level'])): ?>
                                            <span class="badge badge-info"><?php echo $question_data['metadata']['blooms_level']; ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($question_data['metadata']['points'])): ?>
                                            <span class="badge badge-secondary"><?php echo $question_data['metadata']['points']; ?> <?php echo get_string('points', 'local_trustgrade'); ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
?>
