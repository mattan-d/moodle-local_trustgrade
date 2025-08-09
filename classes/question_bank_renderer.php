<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Question bank renderer for displaying and editing questions
 * Supports new JSON pattern:
 * [
 *   {
 *     "id": 1,
 *     "type": "multiple_choice",
 *     "text": "Question text",
 *     "options": [{ "id": 1, "text": "A", "is_correct": true, "explanation": "..." }, ...],
 *     "metadata": { "blooms_level": "Understand", "points": 10 }
 *   }
 * ]
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
        $html .= '<div class="add-question-section mt-3">';
        $html .= '<button type="button" id="add-new-question-btn" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-plus" aria-hidden="true"></i> ' . get_string('add_new_question', 'local_trustgrade');
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
        $html .= '<div class="editable-question-item card mb-4" data-question-index="' . $index . '" data-cmid="' . $cmid . '" data-question-id="' . $qid . '">';

        // Header
        $html .= '<div class="card-header d-flex align-items-center justify-content-between">';
        $html .= '<h5 class="mb-0">' . get_string('question', 'local_trustgrade') . ' ' . ($index + 1) . '</h5>';
        $html .= '<div class="question-controls d-flex gap-2">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary edit-question-btn">';
        $html .= '<i class="fa fa-edit" aria-hidden="true"></i> ' . get_string('edit', 'local_trustgrade');
        $html .= '</button>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">';
        $html .= '<i class="fa fa-trash" aria-hidden="true"></i> ' . get_string('delete', 'local_trustgrade');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';

        // Body
        $html .= '<div class="card-body">';

        // Question display mode
        $html .= '<div class="question-display-mode">';
        $html .= self::render_question_display($question);
        $html .= '</div>';

        // Question edit mode (hidden by default)
        $html .= '<div class="question-edit-mode" style="display: none;">';
        $html .= self::render_question_edit_form($question, $index);
        $html .= '</div>';

        $html .= '</div>'; // card-body
        $html .= '</div>'; // card

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
        $html .= '<p class="mb-1"><strong>' . get_string('type', 'local_trustgrade') . ':</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) . '</p>';

        $metaBits = [];
        if ($points !== null) {
            $metaBits[] = get_string('points', 'local_trustgrade') . ': ' . $points;
        }
        if (!empty($blooms)) {
            $metaBits[] = 'Bloom\'s: ' . htmlspecialchars($blooms);
        }
        if (!empty($metaBits)) {
            $html .= '<p class="text-muted mb-2">' . implode(' | ', $metaBits) . '</p>';
        }

        $html .= '<p><strong>' . get_string('question', 'local_trustgrade') . ':</strong> ' . htmlspecialchars($text) . '</p>';

        if (isset($question['options']) && is_array($question['options'])) {
            $html .= '<div class="mt-3">';
            $html .= '<p class="mb-2"><strong>' . get_string('options', 'local_trustgrade') . ':</strong></p>';
            $html .= '<ul class="mb-0">';
            foreach ($question['options'] as $opt) {
                $optText = isset($opt['text']) ? $opt['text'] : '';
                $isCorrect = !empty($opt['is_correct']);
                $explanation = isset($opt['explanation']) ? $opt['explanation'] : '';

                $correctIndicator = $isCorrect ? ' <strong>(' . get_string('correct', 'local_trustgrade') . ')</strong>' : '';
                $html .= '<li class="mb-1">' . htmlspecialchars($optText) . $correctIndicator;
                if (!empty($explanation)) {
                    $html .= '<div class="option-explanation text-muted small mt-1"><em>' . get_string('explanation', 'local_trustgrade') . ':</em> ' . htmlspecialchars($explanation) . '</div>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render question edit form using new JSON pattern
     * Aligned using a responsive grid for clarity.
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

        $html .= '<div class="question-edit-form container-fluid px-0">';

        // Question text (full width)
        $html .= '<div class="form-group mb-3">';
        $html .= '  <label for="question_text_' . $index . '" class="form-label">' . get_string('question', 'local_trustgrade') . ' ' . get_string('text', 'local_trustgrade') . ':</label>';
        $html .= '  <textarea class="form-control question-text-input" id="question_text_' . $index . '" rows="3" placeholder="' . get_string('entertext', 'local_trustgrade') . '">' . htmlspecialchars($text) . '</textarea>';
        $html .= '</div>';

        // Row: Type | Points | Bloom's
        $html .= '<div class="row g-3">';

        // Type
        $html .= '  <div class="col-12 col-md-4">';
        $html .= '    <div class="form-group">';
        $html .= '      <label for="question_type_' . $index . '" class="form-label">' . get_string('type', 'local_trustgrade') . ':</label>';
        $html .= '      <select class="form-control question-type-input" id="question_type_' . $index . '">';
        $types = ['multiple_choice' => 'Multiple Choice', 'true_false' => 'True/False', 'short_answer' => 'Short Answer'];
        foreach ($types as $value => $label) {
            $selected = ($type == $value) ? 'selected' : '';
            $html .= '        <option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        $html .= '      </select>';
        $html .= '    </div>';
        $html .= '  </div>';

        // Points
        $html .= '  <div class="col-12 col-md-4">';
        $html .= '    <div class="form-group">';
        $html .= '      <label for="question_points_' . $index . '" class="form-label">' . get_string('points', 'local_trustgrade') . ':</label>';
        $html .= '      <input type="number" class="form-control question-points-input" id="question_points_' . $index . '" value="' . $points . '" min="0" max="100" />';
        $html .= '      <small class="form-text text-muted">' . get_string('points_help', 'local_trustgrade') . '</small>';
        $html .= '    </div>';
        $html .= '  </div>';

        // Bloom's Level
        $html .= '  <div class="col-12 col-md-4">';
        $html .= '    <div class="form-group">';
        $html .= '      <label for="question_blooms_' . $index . '" class="form-label">Bloom\'s ' . get_string('level', 'local_trustgrade') . ':</label>';
        $html .= '      <select class="form-control question-blooms-input" id="question_blooms_' . $index . '">';
        $levels = ['', 'Remember', 'Understand', 'Apply', 'Analyze', 'Evaluate', 'Create'];
        foreach ($levels as $level) {
            $sel = ($blooms === $level) ? 'selected' : '';
            $label = $level === '' ? '-' : $level;
            $html .= '        <option value="' . htmlspecialchars($level) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '      </select>';
        $html .= '    </div>';
        $html .= '  </div>';

        $html .= '</div>'; // row

        // Options section (full width)
        $html .= '<div class="question-options-section mt-4">';

        // Section header
        $html .= '  <div class="d-flex align-items-center justify-content-between mb-2">';
        $html .= '    <h6 class="mb-0">' . get_string('options', 'local_trustgrade') . '</h6>';
        $html .= '  </div>';

        // Column headers for alignment (visually subtle)
        $html .= '  <div class="row text-muted small fw-semibold mb-1" role="presentation">';
        $html .= '    <div class="col-12 col-md-1">' . get_string('correct', 'local_trustgrade') . '</div>';
        $html .= '    <div class="col-12 col-md-5">' . get_string('optiontext', 'local_trustgrade') . '</div>';
        $html .= '    <div class="col-12 col-md-6">' . get_string('explanation', 'local_trustgrade') . '</div>';
        $html .= '  </div>';

        if ($type === 'multiple_choice') {
            $html .= self::render_multiple_choice_options($question, $index);
        } elseif ($type === 'true_false') {
            $html .= self::render_true_false_options($question, $index);
        }
        $html .= '</div>';

        // Save/Cancel buttons
        $html .= '<div class="question-edit-buttons mt-4 d-flex gap-2">';
        $html .= '  <button type="button" class="btn btn-primary save-question-btn">' . get_string('savechanges') . '</button>';
        $html .= '  <button type="button" class="btn btn-secondary cancel-edit-btn">' . get_string('cancel') . '</button>';
        $html .= '</div>';

        $html .= '</div>'; // question-edit-form

        return $html;
    }

    /**
     * Render multiple choice options editor with aligned grid and per-option explanations
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

        foreach ($options as $i => $opt) {
            $optText = isset($opt['text']) ? $opt['text'] : '';
            $isCorrect = !empty($opt['is_correct']);
            $explanation = isset($opt['explanation']) ? $opt['explanation'] : '';

            $html .= '<div class="row align-items-start gy-2 gx-3 mb-2 option-row">';

            // Correct radio
            $html .= '  <div class="col-12 col-md-1 d-flex align-items-start pt-2">';
            $checked = $isCorrect ? 'checked' : '';
            $html .= '    <input class="form-check-input mt-0 correct-answer-radio" type="radio" aria-label="' . get_string('correct', 'local_trustgrade') . '" name="correct_answer_' . $index . '" value="' . $i . '" ' . $checked . '>';
            $html .= '  </div>';

            // Option text
            $html .= '  <div class="col-12 col-md-5">';
            $html .= '    <input type="text" class="form-control option-text-input" placeholder="' . get_string('option_placeholder', 'local_trustgrade', chr(65 + $i)) . '" value="' . htmlspecialchars($optText) . '">';
            $html .= '  </div>';

            // Explanation
            $html .= '  <div class="col-12 col-md-6">';
            $html .= '    <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars($explanation) . '</textarea>';
            $html .= '  </div>';

            $html .= '</div>'; // row
        }

        return $html;
    }

    /**
     * Render true/false options editor with aligned grid and per-option explanations
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

        // True row
        $html .= '<div class="row align-items-start gy-2 gx-3 mb-2 tf-row" data-value="true">';

        $html .= '  <div class="col-12 col-md-1 d-flex align-items-start pt-2">';
        $html .= '    <input class="form-check-input mt-0" type="radio" name="tf_answer_' . $index . '" value="true" ' . (!empty($trueOpt['is_correct']) ? 'checked' : '') . ' aria-label="' . get_string('true', 'local_trustgrade') . '">';
        $html .= '  </div>';

        $html .= '  <div class="col-12 col-md-5 d-flex align-items-center">';
        $html .= '    <label class="mb-0 fw-semibold">' . get_string('true', 'local_trustgrade') . '</label>';
        $html .= '  </div>';

        $html .= '  <div class="col-12 col-md-6">';
        $html .= '    <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars(isset($trueOpt['explanation']) ? $trueOpt['explanation'] : '') . '</textarea>';
        $html .= '  </div>';

        $html .= '</div>';

        // False row
        $html .= '<div class="row align-items-start gy-2 gx-3 mb-2 tf-row" data-value="false">';

        $html .= '  <div class="col-12 col-md-1 d-flex align-items-start pt-2">';
        $html .= '    <input class="form-check-input mt-0" type="radio" name="tf_answer_' . $index . '" value="false" ' . (!empty($falseOpt['is_correct']) ? 'checked' : '') . ' aria-label="' . get_string('false', 'local_trustgrade') . '">';
        $html .= '  </div>';

        $html .= '  <div class="col-12 col-md-5 d-flex align-items-center">';
        $html .= '    <label class="mb-0 fw-semibold">' . get_string('false', 'local_trustgrade') . '</label>';
        $html .= '  </div>';

        $html .= '  <div class="col-12 col-md-6">';
        $html .= '    <textarea class="form-control option-explanation-input" rows="2" placeholder="' . get_string('explanation', 'local_trustgrade') . '">' . htmlspecialchars(isset($falseOpt['explanation']) ? $falseOpt['explanation'] : '') . '</textarea>';
        $html .= '  </div>';

        $html .= '</div>';

        return $html;
    }
}
