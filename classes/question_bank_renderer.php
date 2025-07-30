<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Question bank renderer for displaying and editing questions
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
        
        $html .= '<div class="editable-question-item" data-question-index="' . $index . '" data-cmid="' . $cmid . '">';
        
        // Question header with controls
        $html .= '<div class="question-header">';
        $html .= '<h5>Question ' . ($index + 1) . '</h5>';
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
     * Render question in display mode
     * 
     * @param array $question Question data
     * @return string HTML for question display
     */
    private static function render_question_display($question) {
        $html = '';
        
        $html .= '<div class="question-content">';
        $html .= '<p><strong>Type:</strong> ' . ucfirst(str_replace('_', ' ', $question['type'])) . '</p>';
        $html .= '<p><strong>Difficulty:</strong> ' . ($question['difficulty'] ?? 'medium') . '</p>';
        $html .= '<p><strong>Points:</strong> ' . ($question['points'] ?? 10) . '</p>';
        $html .= '<p><strong>Question:</strong> ' . htmlspecialchars($question['question']) . '</p>';
        
        if (isset($question['options']) && is_array($question['options'])) {
            $html .= '<p><strong>Options:</strong></p>';
            $html .= '<ul>';
            foreach ($question['options'] as $opt_index => $option) {
                $is_correct = (isset($question['correct_answer']) && $question['correct_answer'] == $opt_index);
                $correct_indicator = $is_correct ? ' <strong>(Correct)</strong>' : '';
                $html .= '<li>' . htmlspecialchars($option) . $correct_indicator . '</li>';
            }
            $html .= '</ul>';
        }
        
        if (isset($question['explanation']) && !empty($question['explanation'])) {
            $html .= '<p><strong>Explanation:</strong> ' . htmlspecialchars($question['explanation']) . '</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render question edit form
     * 
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for question edit form
     */
    private static function render_question_edit_form($question, $index) {
        $html = '';
        
        $html .= '<div class="question-edit-form">';
        
        // Question text
        $html .= '<div class="form-group">';
        $html .= '<label for="question_text_' . $index . '">Question Text:</label>';
        $html .= '<textarea class="form-control question-text-input" id="question_text_' . $index . '" rows="3">';
        $html .= htmlspecialchars($question['question']);
        $html .= '</textarea>';
        $html .= '</div>';
        
        // Question type
        $html .= '<div class="form-group">';
        $html .= '<label for="question_type_' . $index . '">Question Type:</label>';
        $html .= '<select class="form-control question-type-input" id="question_type_' . $index . '">';
        $types = ['multiple_choice' => 'Multiple Choice', 'true_false' => 'True/False', 'short_answer' => 'Short Answer'];
        foreach ($types as $value => $label) {
            $selected = ($question['type'] == $value) ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        
        // Difficulty
        $html .= '<div class="form-group">';
        $html .= '<label for="question_difficulty_' . $index . '">Difficulty:</label>';
        $html .= '<select class="form-control question-difficulty-input" id="question_difficulty_' . $index . '">';
        $difficulties = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];
        foreach ($difficulties as $value => $label) {
            $selected = (($question['difficulty'] ?? 'medium') == $value) ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        
        // Points
        $html .= '<div class="form-group">';
        $html .= '<label for="question_points_' . $index . '">Points:</label>';
        $html .= '<input type="number" class="form-control question-points-input" id="question_points_' . $index . '" ';
        $html .= 'value="' . ($question['points'] ?? 10) . '" min="1" max="100">';
        $html .= '</div>';
        
        // Options (for multiple choice and true/false)
        $html .= '<div class="question-options-section">';
        if ($question['type'] == 'multiple_choice') {
            $html .= self::render_multiple_choice_options($question, $index);
        } elseif ($question['type'] == 'true_false') {
            $html .= self::render_true_false_options($question, $index);
        }
        $html .= '</div>';
        
        // Explanation
        $html .= '<div class="form-group">';
        $html .= '<label for="question_explanation_' . $index . '">Explanation:</label>';
        $html .= '<textarea class="form-control question-explanation-input" id="question_explanation_' . $index . '" rows="2">';
        $html .= htmlspecialchars($question['explanation'] ?? '');
        $html .= '</textarea>';
        $html .= '</div>';
        
        // Save/Cancel buttons
        $html .= '<div class="question-edit-buttons">';
        $html .= '<button type="button" class="btn btn-primary save-question-btn">Save</button>';
        $html .= '<button type="button" class="btn btn-secondary cancel-edit-btn">Cancel</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render multiple choice options editor
     * 
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for options editor
     */
    private static function render_multiple_choice_options($question, $index) {
        $html = '';
        
        $html .= '<div class="multiple-choice-options">';
        $html .= '<label>Options:</label>';
        
        $options = $question['options'] ?? ['', '', '', ''];
        $correct_answer = $question['correct_answer'] ?? 0;
        
        for ($i = 0; $i < 4; $i++) {
            $html .= '<div class="option-row">';
            $html .= '<div class="form-check">';
            $checked = ($correct_answer == $i) ? 'checked' : '';
            $html .= '<input class="form-check-input correct-answer-radio" type="radio" ';
            $html .= 'name="correct_answer_' . $index . '" value="' . $i . '" ' . $checked . '>';
            $html .= '<input type="text" class="form-control option-text-input" ';
            $html .= 'placeholder="Option ' . chr(65 + $i) . '" value="' . htmlspecialchars($options[$i] ?? '') . '">';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render true/false options editor
     * 
     * @param array $question Question data
     * @param int $index Question index
     * @return string HTML for true/false editor
     */
    private static function render_true_false_options($question, $index) {
        $html = '';
        
        $html .= '<div class="true-false-options">';
        $html .= '<label>Correct Answer:</label>';
        
        $correct_answer = $question['correct_answer'] ?? true;
        
        $html .= '<div class="form-check">';
        $checked = $correct_answer ? 'checked' : '';
        $html .= '<input class="form-check-input" type="radio" name="tf_answer_' . $index . '" value="true" ' . $checked . '>';
        $html .= '<label class="form-check-label">True</label>';
        $html .= '</div>';
        
        $html .= '<div class="form-check">';
        $checked = !$correct_answer ? 'checked' : '';
        $html .= '<input class="form-check-input" type="radio" name="tf_answer_' . $index . '" value="false" ' . $checked . '>';
        $html .= '<label class="form-check-label">False</label>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
