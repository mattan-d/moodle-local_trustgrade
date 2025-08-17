<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
* Question bank renderer for displaying and editing questions
* Supports question structure:
* {
*   "id": 1,
*   "type": "multiple_choice", 
*   "question": "Question text",
*   "options": ["Option A", "Option B", "Option C", "Option D"],
*   "correct_answer": 1,
*   "explanation": "Explanation text",
*   "difficulty": "medium",
*   "points": 10,
*   "metadata": { "blooms_level": " 이해", "cognitiveobjective": " הסבר, סיכום" }
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
   * Render question in display mode using the provided question structure
   *
   * @param array $question Question data
   * @return string HTML for question display
   */
  private static function render_question_display($question) {
      $html = '';

      $type = isset($question['type']) ? $question['type'] : '';
      $questionText = isset($question['question']) ? $question['question'] : '';
      $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [];
      $correctAnswer = isset($question['correct_answer']) ? intval($question['correct_answer']) : 0;
      $explanation = isset($question['explanation']) ? $question['explanation'] : '';
      $difficulty = isset($question['difficulty']) ? $question['difficulty'] : '';
      $points = isset($question['points']) ? intval($question['points']) : null;
      $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
      $bloomsLevel = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : '';
      $cognitiveObjective = isset($metadata['cognitiveobjective']) ? $metadata['cognitiveobjective'] : '';

      $html .= '<div class="question-content">';
      $html .= '<p class="mb-1"><strong>' . get_string('type', 'local_trustgrade') . ':</strong> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) . '</p>';

      $metaBits = [];
      if ($points !== null) {
          $metaBits[] = get_string('points', 'local_trustgrade') . ': ' . $points;
      }
      if (!empty($difficulty)) {
          $metaBits[] = get_string('difficulty', 'local_trustgrade') . ': ' . htmlspecialchars($difficulty);
      }
      if (!empty($bloomsLevel)) {
          $metaBits[] = 'Bloom\'s: ' . htmlspecialchars($bloomsLevel);
      }
      if (!empty($cognitiveObjective)) {
          $metaBits[] = get_string('cognitive_objective', 'local_trustgrade') . ': ' . htmlspecialchars($cognitiveObjective);
      }
      if (!empty($metaBits)) {
          $html .= '<p class="text-muted mb-2">' . implode(' | ', $metaBits) . '</p>';
      }

      $html .= '<p><strong>' . get_string('question', 'local_trustgrade') . ':</strong> ' . htmlspecialchars($questionText) . '</p>';

      if (!empty($options)) {
          $html .= '<div class="mt-3">';
          $html .= '<p class="mb-2"><strong>' . get_string('options', 'local_trustgrade') . ':</strong></p>';
          $html .= '<ul class="mb-0">';
          foreach ($options as $index => $optionText) {
              $isCorrect = ($index === $correctAnswer);
              $correctIndicator = $isCorrect ? ' <strong>(' . get_string('correct', 'local_trustgrade') . ')</strong>' : '';
              $html .= '<li class="mb-1">' . htmlspecialchars($optionText) . $correctIndicator . '</li>';
          }
          $html .= '</ul>';
          $html .= '</div>';
      }

      if (!empty($explanation)) {
          $html .= '<div class="mt-3">';
          $html .= '<p class="mb-1"><strong>' . get_string('explanation', 'local_trustgrade') . ':</strong></p>';
          $html .= '<div class="explanation-text text-muted">' . htmlspecialchars($explanation) . '</div>';
          $html .= '</div>';
      }

      $html .= '</div>';

      return $html;
  }

  /**
   * Render question edit form using the provided question structure
   *
   * @param array $question Question data
   * @param int $index Question index
   * @return string HTML for question edit form
   */
  private static function render_question_edit_form($question, $index) {
      $html = '';

      $type = isset($question['type']) ? $question['type'] : 'multiple_choice';
      $questionText = isset($question['question']) ? $question['question'] : '';
      $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [];
      $correctAnswer = isset($question['correct_answer']) ? intval($question['correct_answer']) : 0;
      $explanation = isset($question['explanation']) ? $question['explanation'] : '';
      $difficulty = isset($question['difficulty']) ? $question['difficulty'] : 'medium';
      $points = isset($question['points']) ? intval($question['points']) : 10;
      $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
      $bloomsLevel = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : '';
      $cognitiveObjective = isset($metadata['cognitiveobjective']) ? $metadata['cognitiveobjective'] : '';

      $html .= '<div class="question-edit-form container-fluid px-0">';

      // Question text (full width)
      $html .= '<div class="form-group mb-3">';
      $html .= '  <label for="question_text_' . $index . '" class="form-label">' . get_string('question', 'local_trustgrade') . ' ' . get_string('text', 'local_trustgrade') . ':</label>';
      $html .= '  <textarea class="form-control question-text-input" id="question_text_' . $index . '" rows="3" placeholder="' . get_string('entertext', 'local_trustgrade') . '">' . htmlspecialchars($questionText) . '</textarea>';
      $html .= '</div>';

      // Row: Type | Points | Difficulty
      $html .= '<div class="row g-3">';

      // Type
      $html .= '  <div class="col-12 col-md-4">';
      $html .= '    <div class="form-group">';
      $html .= '      <label for="question_type_' . $index . '" class="form-label">' . get_string('type', 'local_trustgrade') . ':</label>';
      $html .= '      <select class="form-control question-type-input" id="question_type_' . $index . '">';
      $types = ['multiple_choice' => 'Multiple Choice'];
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
      $html .= '    </div>';
      $html .= '  </div>';

      $html .= '  <div class="col-12 col-md-4">';
      $html .= '    <div class="form-group">';
      $html .= '      <label for="question_difficulty_' . $index . '" class="form-label">' . get_string('difficulty', 'local_trustgrade') . ':</label>';
      $html .= '      <select class="form-control question-difficulty-input" id="question_difficulty_' . $index . '">';
      $difficulties = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];
      foreach ($difficulties as $value => $label) {
          $selected = ($difficulty == $value) ? 'selected' : '';
          $html .= '        <option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
      }
      $html .= '      </select>';
      $html .= '    </div>';
      $html .= '  </div>';

      $html .= '</div>'; // row

      $html .= '<div class="row g-3 mt-2">';
      
      // Bloom's Level
      $html .= '  <div class="col-12 col-md-6">';
      $html .= '    <div class="form-group">';
      $html .= '      <label for="question_blooms_' . $index . '" class="form-label">Bloom\'s ' . get_string('level', 'local_trustgrade') . ':</label>';
      $html .= '      <input type="text" class="form-control question-blooms-input" id="question_blooms_' . $index . '" value="' . htmlspecialchars($bloomsLevel) . '" placeholder="e.g., הבנה" />';
      $html .= '    </div>';
      $html .= '  </div>';

      // Cognitive Objective
      $html .= '  <div class="col-12 col-md-6">';
      $html .= '    <div class="form-group">';
      $html .= '      <label for="question_cognitive_' . $index . '" class="form-label">' . get_string('cognitive_objective', 'local_trustgrade') . ':</label>';
      $html .= '      <input type="text" class="form-control question-cognitive-input" id="question_cognitive_' . $index . '" value="' . htmlspecialchars($cognitiveObjective) . '" placeholder="e.g., הסבר, סיכום, פרשנות" />';
      $html .= '    </div>';
      $html .= '  </div>';

      $html .= '</div>'; // row

      // Options section (full width)
      $html .= '<div class="question-options-section mt-4">';
      $html .= '  <h6 class="mb-3">' . get_string('options', 'local_trustgrade') . '</h6>';

      if ($type === 'multiple_choice') {
          $html .= self::render_multiple_choice_options_new_structure($options, $correctAnswer, $index);
      }
      $html .= '</div>';

      $html .= '<div class="form-group mt-4">';
      $html .= '  <label for="question_explanation_' . $index . '" class="form-label">' . get_string('explanation', 'local_trustgrade') . ':</label>';
      $html .= '  <textarea class="form-control question-explanation-input" id="question_explanation_' . $index . '" rows="3" placeholder="' . get_string('explanation_placeholder', 'local_trustgrade') . '">' . htmlspecialchars($explanation) . '</textarea>';
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
   * Render multiple choice options editor for the new structure (array of strings with correct_answer index)
   *
   * @param array $options Array of option strings
   * @param int $correctAnswer Index of correct answer
   * @param int $index Question index
   * @return string HTML for options editor
   */
  private static function render_multiple_choice_options_new_structure($options, $correctAnswer, $index) {
      $html = '';

      // Ensure 4 options minimum
      for ($i = count($options); $i < 4; $i++) {
          $options[] = '';
      }

      foreach ($options as $i => $optionText) {
          $isCorrect = ($i === $correctAnswer);

          $html .= '<div class="row align-items-center gy-2 gx-3 mb-2 option-row">';

          // Correct radio
          $html .= '  <div class="col-12 col-md-1">';
          $checked = $isCorrect ? 'checked' : '';
          $html .= '    <input class="form-check-input correct-answer-radio" type="radio" name="correct_answer_' . $index . '" value="' . $i . '" ' . $checked . '>';
          $html .= '  </div>';

          // Option text
          $html .= '  <div class="col-12 col-md-11">';
          $html .= '    <input type="text" class="form-control option-text-input" placeholder="' . get_string('option_placeholder', 'local_trustgrade', chr(65 + $i)) . '" value="' . htmlspecialchars($optionText) . '">';
          $html .= '  </div>';

          $html .= '</div>'; // row
      }

      return $html;
  }
}
