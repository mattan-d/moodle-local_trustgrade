<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Question bank renderer for displaying and editing questions
 * Updated to support new JSON pattern:
 * {
 *   "id": 1,
 *   "type": "multiple_choice",
 *   "text": "Question text",
 *   "options": [{ "id": 1, "text": "A", "is_correct": true, "explanation": "..." }, ...],
 *   "metadata": { "blooms_level": "Understand", "points": 10 }
 * }
 */
class question_bank_renderer {

    /**
     * Render editable questions for instructors
     *
     * @param array $questions Array of questions
     * @param int $cmid Course module ID
     * @return string HTML for editable questions
     */
    public static function render_editable_questions($questions, $cmid) {
        $html = '';

        $html .= '<div id="question-bank-container" class="question-bank-container">';

        foreach ($questions as $index => $question) {
            $html .= self::render_single_editable_question($question, $index, $cmid);
        }

        // Add new question button
        $html .= '<div class="add-question-section">';
        $html .= '<button type="button" id="add-new-question-btn" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-plus"></i> ' . get_string('add_new_question', 'local_trustgrade');
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single editable question
     *
     * @param array $question Question data
     * @param int $index Question index
     * @param int $cmid Course module ID
     * @return string HTML for single question
     */
    private static function render_single_editable_question($question, $index, $cmid) {
        $html = '';

        $qid = isset($question['id']) ? intval($question['id']) : 0;
        $html .= '<div class="editable-question-item" data-question-index="' . $index . '" data-cmid="' . $cmid . '" data-question-id="' . $qid . '">';

        // Question header with controls
        $html .= '<div class="question-header">';
        $html .= '<h5>' . get_string('question', 'local_trustgrade') . ' ' . ($index + 1) . '</h5>';
        $html .= '<div class="question-controls">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary edit-question-btn">';
        $html .= '<i class="fa fa-edit"></i> ' . get_string('edit', 'local_trustgrade');
        $html .= '</button>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">';
        $html .= '<i class="fa fa-trash"></i> ' . get_string('delete', 'local_trustgrade');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Question display mode
        $html .= '<div class="question-display-mode">';
        $html .= self::render_question_display($question);
        $html .= '</div>';

        // Question edit mode (hidden by default)
        $html .= '<div class="question-edit-mode" style="display: none;">';
        $html .= self::render_question_edit_form($question, $index);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render question in display mode using new JSON pattern
     *
     * @param array $question Question data
     * @return string HTML for question display
     */
    private static function render_question_display($question) {
        $html = '';

        $type = isset($question['type']) ? $question['type'] : '';
        $text = isset($question['text']) ? $question['text'] : '';
        $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
        $points = isset($metadata['points']) ? intval($metadata['points']) : null;
        $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : null;

        $html .= '<div class="question-content">';
        $html .= '<p><strong>Type:</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) . '</p>';

        $metaBits = [];
        if ($points !== null) {
            $metaBits[] = get_string('points', 'local_trustgrade') . ': ' . $points;
        }
        if (!empty($blooms)) {
            $metaBits[] = 'Bloom\'s: ' . htmlspecialchars($blooms);
        }
        if (!empty($metaBits)) {
            $html .= '<p>' . implode(' | ', $metaBits) . '</p>';
        }

        $html .= '<p><strong>' . get_string('question', 'local_trustgrade') . ':</strong> ' . htmlspecialchars($text) . '</p>';

        if (isset($question['options']) && is_array($question['options'])) {
            $html .= '<p><strong>Options:</strong></p>';
            $html .= '<ul>';
            foreach ($question['options'] as $opt) {
                $optText = isset($opt['text']) ? $opt['text'] : '';
                $isCorrect = !empty($opt['is_correct']);
                $explanation = isset($opt['explanation']) ? $opt['explanation'] : '';

                $correctIndicator = $isCorrect ? ' <strong>(' . get_string('correct', 'local_trustgrade') . ')</strong>' : '';
                $html .= '<li>' . htmlspecialchars($optText) . $correctIndicator;
                if (!empty($explanation)) {
                    $html .= '<div class="option-explanation"><em>' . get_string('explanation', 'local_trustgrade') . ':</em> ' . htmlspecialchars($explanation) . '</div>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render question edit form using new JSON pattern
     *
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for question edit form
     */
    private static function render_question_edit_form($question, $index) {
        $html = '';

        $type = isset($question['type']) ? $question['type'] : 'multiple_choice';
        $text = isset($question['text']) ? $question['text'] : '';
        $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
        $points = isset($metadata['points']) ? intval($metadata['points']) : 10;
        $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : '';

        $html .= '<div class="question-edit-form">';

        // Question text
        $html .= '<div class="form-group">';
        $html .= '<label for="question_text_' . $index . '">' . get_string('question', 'local_trustgrade') . ' Text:</label>';
        $html .= '<textarea class="form-control question-text-input" id="question_text_' . $index . '" rows="3">';
        $html .= htmlspecialchars($text);
        $html .= '</textarea>';
        $html .= '</div>';

        // Question type
        $html .= '<div class="form-group">';
        $html .= '<label for="question_type_' . $index . '">Question Type:</label>';
        $html .= '<select class="form-control question-type-input" id="question_type_' . $index . '">';
        $types = ['multiple_choice' => 'Multiple Choice', 'true_false' => 'True/False', 'short_answer' => 'Short Answer'];
        foreach ($types as $value => $label) {
            $selected = ($type == $value) ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // Points
        $html .= '<div class="form-group">';
        $html .= '<label for="question_points_' . $index . '">' . get_string('points', 'local_trustgrade') . ':</label>';
        $html .= '<input type="number" class="form-control question-points-input" id="question_points_' . $index . '" ';
        $html .= 'value="' . $points . '" min="0" max="100">';
        $html .= '</div>';

        // Bloom's Level (optional)
        $html .= '<div class="form-group">';
        $html .= '<label for="question_blooms_' . $index . '">Bloom\'s Level:</label>';
        $html .= '<select class="form-control question-blooms-input" id="question_blooms_' . $index . '">';
        $levels = ['', 'Remember', 'Understand', 'Apply', 'Analyze', 'Evaluate', 'Create'];
        foreach ($levels as $level) {
            $sel = ($blooms === $level) ? 'selected' : '';
            $label = $level === '' ? '-' : $level;
            $html .= '<option value="' . htmlspecialchars($level) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // Options section
        $html .= '<div class="question-options-section">';
        if ($type === 'multiple_choice') {
            $html .= self::render_multiple_choice_options($question, $index);
        } elseif ($type === 'true_false') {
            $html .= self::render_true_false_options($question, $index);
        }
        $html .= '</div>';

        // Save/Cancel buttons
        $html .= '<div class="question-edit-buttons">';
        $html .= '<button type="button" class="btn btn-primary save-question-btn">' . get_string('savechanges') . '</button>';
        $html .= '<button type="button" class="btn btn-secondary cancel-edit-btn">' . get_string('cancel') . '</button>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render multiple choice options editor with per-option explanations
     *
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for options editor
     */
    private static function render_multiple_choice_options($question, $index) {
        $html = '';

        $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [];
        // Ensure 4 rows minimum
        for ($i = count($options); $i < 4; $i++) {
            $options[] = ['text' => '', 'is_correct' => ($i === 0), 'explanation' => ''];
        }

        $html .= '<div class="multiple-choice-options">';
        $html .= '<label>Options:</label>';

        foreach ($options as $i => $opt) {
            $optText = isset($opt['text']) ? $opt['text'] : '';
            $isCorrect = !empty($opt['is_correct']);
            $explanation = isset($opt['explanation']) ? $opt['explanation'] : '';

            $html .= '<div class="option-row">';
            $html .= '  <div class="form-check">';
            $checked = $isCorrect ? 'checked' : '';
            $html .= '    <input class="form-check-input correct-answer-radio" type="radio" name="correct_answer_' . $index . '" value="' . $i . '" ' . $checked . '>';
            $html .= '    <input type="text" class="form-control option-text-input" placeholder="Option ' . chr(65 + $i) . '" value="' . htmlspecialchars($optText) . '">';
            $html .= '  </div>';
            $html .= '  <div class="form-group mt-2">';
            $html .= '    <label class="form-label">' . get_string('explanation', 'local_trustgrade') . '</label>';
            $html .= '    <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars($explanation) . '</textarea>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render true/false options editor with per-option explanations
     *
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for true/false editor
     */
    private static function render_true_false_options($question, $index) {
        $html = '';

        $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [];
        // Normalize options into [true, false]
        $trueOpt = ['text' => get_string('true', 'local_trustgrade'), 'is_correct' => true, 'explanation' => ''];
        $falseOpt = ['text' => get_string('false', 'local_trustgrade'), 'is_correct' => false, 'explanation' => ''];
        foreach ($options as $opt) {
            if (isset($opt['text']) && core_text::strtolower($opt['text']) === core_text::strtolower(get_string('true', 'local_trustgrade'))) {
                $trueOpt = $opt;
            } elseif (isset($opt['text']) && core_text::strtolower($opt['text']) === core_text::strtolower(get_string('false', 'local_trustgrade'))) {
                $falseOpt = $opt;
            }
        }

        $html .= '<div class="true-false-options">';
        $html .= '  <label>' . get_string('correct_answer', 'local_trustgrade') . ':</label>';

        // True row
        $html .= '  <div class="tf-row" data-value="true">';
        $html .= '    <div class="form-check">';
        $html .= '      <input class="form-check-input" type="radio" name="tf_answer_' . $index . '" value="true" ' . (!empty($trueOpt['is_correct']) ? 'checked' : '') . '>';
        $html .= '      <label class="form-check-label tf-label">' . get_string('true', 'local_trustgrade') . '</label>';
        $html .= '    </div>';
        $html .= '    <div class="form-group mt-2">';
        $html .= '      <label class="form-label">' . get_string('explanation', 'local_trustgrade') . '</label>';
        $html .= '      <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars(isset($trueOpt['explanation']) ? $trueOpt['explanation'] : '') . '</textarea>';
        $html .= '    </div>';
        $html .= '  </div>';

        // False row
        $html .= '  <div class="tf-row mt-3" data-value="false">';
        $html .= '    <div class="form-check">';
        $html .= '      <input class="form-check-input" type="radio" name="tf_answer_' . $index . '" value="false" ' . (!empty($falseOpt['is_correct']) ? 'checked' : '') . '>';
        $html .= '      <label class="form-check-label tf-label">' . get_string('false', 'local_trustgrade') . '</label>';
        $html .= '    </div>';
        $html .= '    <div class="form-group mt-2">';
        $html .= '      <label class="form-label">' . get_string('explanation', 'local_trustgrade') . '</label>';
        $html .= '      <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars(isset($falseOpt['explanation']) ? $falseOpt['explanation'] : '') . '</textarea>';
        $html .= '    </div>';
        $html .= '  </div>';

        $html .= '</div>';

        return $html;
    }
}
