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
      $html .= '<div class="table-responsive">';
      $html .= '<table class="table table-bordered table-hover generaltable trustgrade-questions-table">';
      $html .= '<thead><tr>';
      $html .= '<th class="col-no">#</th>';
      $html .= '<th class="col-question">' . get_string('question', 'local_trustgrade') . '</th>';
      $html .= '<th class="col-blooms">' . get_string('blooms_level_label', 'local_trustgrade') . '</th>';
      $html .= '<th class="col-mandatory">' . get_string('mandatory_question', 'local_trustgrade') . '</th>';
      $html .= '<th class="col-actions">' . get_string('actions', 'local_trustgrade') . '</th>';
      $html .= '</tr></thead>';

      if (empty($questions)) {
          $html .= '<tbody class="trustgrade-empty-questions-row">';
          $html .= '<tr><td colspan="5" class="text-center text-muted py-4">' . get_string('no_questions_found', 'local_trustgrade') . '</td></tr>';
          $html .= '</tbody>';
      } else {
          foreach ($questions as $index => $question) {
              $html .= self::render_single_editable_question_row($question, $index, $cmid);
          }
      }

      $html .= '</table>';
      $html .= '</div>';

      $html .= '</div>';

      return $html;
  }

  /**
   * Render a single editable question as table rows (display row + edit row).
   *
   * @param array $question Question data
   * @param int $index Question index
   * @param int $cmid Course module ID
   * @return string HTML for tbody with two tr
   */
  private static function render_single_editable_question_row($question, $index, $cmid) {
      $html = '';
      $qid = isset($question['id']) ? intval($question['id']) : 0;
      $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
      $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : null;
      $is_mandatory = isset($question['is_mandatory']) ? intval($question['is_mandatory']) : 0;
      $db_id = isset($question['db_id']) ? intval($question['db_id']) : (isset($question['id']) ? intval($question['id']) : 0);

      $html .= '<tbody class="editable-question-item" data-question-index="' . $index . '" data-cmid="' . $cmid . '" data-question-id="' . $qid . '">';

      // Display row
      $html .= '<tr class="question-display-mode">';
      $html .= '<td class="col-no">' . ($index + 1) . '</td>';
      $html .= '<td class="col-question">' . self::render_question_display($question, true) . '</td>';
      $html .= '<td class="col-blooms">' . ($blooms ? self::get_blooms_level_string($blooms) : '–') . '</td>';
      $html .= '<td class="col-mandatory">';
      $html .= '<div class="d-flex align-items-center gap-2 mandatory-controls" data-question-dbid="' . $db_id . '">';
      if ($is_mandatory) {
          $html .= '<span class="badge bg-danger mandatory-badge">' . get_string('mandatory_question', 'local_trustgrade') . '</span>';
          $html .= '<button type="button" class="btn btn-sm btn-outline-secondary toggle-mandatory-btn" data-mandatory="1" data-question-id="' . $db_id . '" title="' . get_string('remove_mandatory', 'local_trustgrade') . '">';
          $html .= '<i class="fa fa-times-circle" aria-hidden="true"></i> ' . get_string('remove_mandatory', 'local_trustgrade');
          $html .= '</button>';
      } else {
          $html .= '<button type="button" class="btn btn-sm btn-outline-primary toggle-mandatory-btn" data-mandatory="0" data-question-id="' . $db_id . '" title="' . get_string('make_mandatory', 'local_trustgrade') . '">';
          $html .= '<i class="fa fa-star" aria-hidden="true"></i> ' . get_string('make_mandatory', 'local_trustgrade');
          $html .= '</button>';
      }
      $html .= '</div></td>';
      $html .= '<td class="col-actions">';
      $html .= '<div class="question-controls d-flex gap-2">';
      $editurl = new \moodle_url('/local/trustgrade/question_edit.php', ['cmid' => $cmid, 'id' => $qid]);
      $html .= \html_writer::link($editurl, '<i class="fa fa-edit" aria-hidden="true"></i> ' . get_string('edit', 'local_trustgrade'), ['class' => 'btn btn-sm btn-outline-secondary']);
      $html .= '<button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">';
      $html .= '<i class="fa fa-trash" aria-hidden="true"></i> ' . get_string('delete', 'local_trustgrade');
      $html .= '</button>';
      $html .= '</div></td>';
      $html .= '</tr>';
      $html .= '</tbody>';

      return $html;
  }

  /**
   * Render question for view-only display (full text + options). For use on the edit page.
   *
   * @param array $question Question data
   * @return string HTML
   */
  public static function render_question_view_content($question) {
      return self::render_question_display($question, false);
  }

  /**
   * Map raw blooms_level value to plugin get_string for display (core str support).
   *
   * @param string $raw Raw value e.g. "Understanding", "Analyze"
   * @return string Translated label from local_trustgrade strings
   */
  private static function get_blooms_level_string($raw) {
      $keyMap = [
          'Remembering' => 'blooms_remembering',
          'Remember'    => 'blooms_remembering',
          'Understanding' => 'blooms_understanding',
          'Understand'  => 'blooms_understanding',
          'Applying'    => 'blooms_applying',
          'Apply'       => 'blooms_applying',
          'Analyzing'   => 'blooms_analyzing',
          'Analyze'     => 'blooms_analyzing',
          'Evaluating'  => 'blooms_evaluating',
          'Evaluate'    => 'blooms_evaluating',
      ];
      $key = isset($keyMap[$raw]) ? $keyMap[$raw] : null;
      if ($key !== null) {
          return get_string($key, 'local_trustgrade');
      }
      return s($raw);
  }

  /**
   * Render question in display mode using new JSON pattern.
   *
   * @param array $question Question data
   * @param bool $fortable When true, omit Blooms and Mandatory (for table columns)
   * @return string HTML for question display
   */
  private static function render_question_display($question, $fortable = false) {
      $html = '';

      $type = isset($question['type']) ? $question['type'] : '';
      $text = isset($question['text']) ? $question['text'] : '';
      $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
      $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : null;
      $is_mandatory = isset($question['is_mandatory']) ? intval($question['is_mandatory']) : 0;
      $db_id = isset($question['db_id']) ? intval($question['db_id']) : (isset($question['id']) ? intval($question['id']) : 0);

      $html .= '<div class="question-content">';

      if (!$fortable) {
          $metaBits = [];
          if (!empty($blooms)) {
              $bloomsLabel = get_string('blooms_level_label', 'local_trustgrade');
              $metaBits[] = $bloomsLabel . ': ' . self::get_blooms_level_string($blooms);
          }
          if (!empty($metaBits)) {
              $html .= '<p class="text-muted mb-2">' . implode(' | ', $metaBits) . '</p>';
          }

          $html .= '<div class="d-flex align-items-center gap-2 mb-2 mandatory-controls" data-question-dbid="' . $db_id . '">';
          if ($is_mandatory) {
              $html .= '<span class="badge bg-danger mandatory-badge">' . get_string('mandatory_question', 'local_trustgrade') . '</span>';
              $html .= '<button type="button" class="btn btn-sm btn-outline-secondary toggle-mandatory-btn" data-mandatory="1" data-question-id="' . $db_id . '" title="' . get_string('remove_mandatory', 'local_trustgrade') . '">';
              $html .= '<i class="fa fa-times-circle" aria-hidden="true"></i> ' . get_string('remove_mandatory', 'local_trustgrade');
              $html .= '</button>';
          } else {
              $html .= '<button type="button" class="btn btn-sm btn-outline-primary toggle-mandatory-btn" data-mandatory="0" data-question-id="' . $db_id . '" title="' . get_string('make_mandatory', 'local_trustgrade') . '">';
              $html .= '<i class="fa fa-star" aria-hidden="true"></i> ' . get_string('make_mandatory', 'local_trustgrade');
              $html .= '</button>';
          }
          $html .= '</div>';
      }

      $html .= '<p class="mb-1">' . htmlspecialchars($text) . '</p>';

      if (!$fortable && isset($question['options']) && is_array($question['options'])) {
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
      $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : '';
      $is_mandatory = !empty($question['is_mandatory']);

      $html .= '<div class="question-edit-form container-fluid px-0">';

      // Question text (full width)
      $html .= '<div class="form-group mb-3">';
      $html .= '  <label for="question_text_' . $index . '" class="form-label">' . get_string('question', 'local_trustgrade') . ' ' . get_string('text', 'local_trustgrade') . ':</label>';
      $html .= '  <textarea class="form-control question-text-input" id="question_text_' . $index . '" rows="3" placeholder="' . get_string('entertext', 'local_trustgrade') . '">' . htmlspecialchars($text) . '</textarea>';
      $html .= '</div>';

      // Hidden: type and points (points no longer shown; default 1 for save compatibility)
      $html .= '<input type="hidden" class="question-type-input" id="question_type_' . $index . '" value="multiple_choice">';
      $points = isset($metadata['points']) ? intval($metadata['points']) : 1;
      $html .= '<input type="hidden" class="question-points-input" id="question_points_' . $index . '" value="' . $points . '">';

      // Row: Bloom's
      $html .= '<div class="row g-3">';

      // Bloom's Level
      $html .= '  <div class="col-12 col-md-4">';
      $html .= '    <div class="form-group">';
      $html .= '      <label for="question_blooms_' . $index . '" class="form-label">' . get_string('blooms_level_label', 'local_trustgrade') . ':</label>';
      $html .= '      <select class="form-control question-blooms-input" id="question_blooms_' . $index . '">';

      $levels = [
          '' => '-',
          'Remembering' => get_string('blooms_remembering', 'local_trustgrade'),
          'Understanding' => get_string('blooms_understanding', 'local_trustgrade'),
          'Applying' => get_string('blooms_applying', 'local_trustgrade'),
          'Analyzing' => get_string('blooms_analyzing', 'local_trustgrade'),
          'Evaluating' => get_string('blooms_evaluating', 'local_trustgrade')
      ];

      foreach ($levels as $level => $label) {
          $sel = ($blooms === $level) ? 'selected' : '';
          $html .= '        <option value="' . htmlspecialchars($level) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
      }
      $html .= '      </select>';
      $html .= '    </div>';
      $html .= '  </div>';

      $html .= '  <div class="col-12 col-md-4">';
      $html .= '    <div class="form-group">';
      $html .= '      <label class="form-label d-block">&nbsp;</label>'; // Spacer for alignment
      $html .= '      <div class="form-check">';
      $checked = $is_mandatory ? 'checked' : '';
      $html .= '        <input class="form-check-input question-mandatory-input" type="checkbox" id="question_mandatory_' . $index . '" value="1" ' . $checked . '>';
      $html .= '        <label class="form-check-label" for="question_mandatory_' . $index . '">';
      $html .= '          ' . get_string('mandatory_question', 'local_trustgrade');
      $html .= '        </label>';
      $html .= '      </div>';
      $html .= '      <small class="form-text text-muted">' . get_string('mandatory_question_help', 'local_trustgrade') . '</small>';
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
   * Render question edit form as a full HTML form for the standalone edit page (POST with name attributes).
   *
   * @param array $question Question data
   * @param \moodle_url $actionurl Form action URL
   * @param int $cmid Course module ID
   * @return string HTML form
   */
  public static function render_question_edit_form_for_page($question, $actionurl, $cmid) {
      $type = isset($question['type']) ? $question['type'] : 'multiple_choice';
      $text = isset($question['text']) ? $question['text'] : '';
      $metadata = isset($question['metadata']) && is_array($question['metadata']) ? $question['metadata'] : [];
      $blooms = isset($metadata['blooms_level']) ? $metadata['blooms_level'] : '';
      $is_mandatory = !empty($question['is_mandatory']);
      $options = isset($question['options']) && is_array($question['options']) ? $question['options'] : [];
      for ($i = count($options); $i < 4; $i++) {
          $options[] = ['text' => '', 'is_correct' => ($i === 0), 'explanation' => ''];
      }

      $html = '<form method="post" action="' . $actionurl->out(false) . '" class="question-edit-form-page">';
      $html .= '<div class="form-group mb-3">';
      $html .= '<label for="qtext" class="form-label">' . get_string('question', 'local_trustgrade') . ' ' . get_string('text', 'local_trustgrade') . ':</label>';
      $html .= '<textarea class="form-control" name="qtext" id="qtext" rows="3" required="required">' . s($text) . '</textarea>';
      $html .= '</div>';

      $html .= '<div class="row g-3 mb-3">';
      $html .= '<div class="col-12 col-md-4">';
      $html .= '<label for="blooms" class="form-label">' . get_string('blooms_level_label', 'local_trustgrade') . ':</label>';
      $html .= '<select class="form-control" name="blooms" id="blooms">';
      $levels = [
          '' => '-',
          'Remembering' => get_string('blooms_remembering', 'local_trustgrade'),
          'Understanding' => get_string('blooms_understanding', 'local_trustgrade'),
          'Applying' => get_string('blooms_applying', 'local_trustgrade'),
          'Analyzing' => get_string('blooms_analyzing', 'local_trustgrade'),
          'Evaluating' => get_string('blooms_evaluating', 'local_trustgrade')
      ];
      foreach ($levels as $val => $label) {
          $sel = ($blooms === $val) ? ' selected="selected"' : '';
          $html .= '<option value="' . s($val) . '"' . $sel . '>' . s($label) . '</option>';
      }
      $html .= '</select></div>';
      $html .= '<div class="col-12 col-md-4">';
      $html .= '<label class="form-label d-block">&nbsp;</label>';
      $html .= '<div class="form-check"><input class="form-check-input" type="checkbox" name="mandatory" id="mandatory" value="1"' . ($is_mandatory ? ' checked="checked"' : '') . '>';
      $html .= '<label class="form-check-label" for="mandatory">' . get_string('mandatory_question', 'local_trustgrade') . '</label></div>';
      $html .= '</div></div>';

      $html .= '<div class="question-options-section mt-4">';
      $html .= '<h6 class="mb-2">' . get_string('options', 'local_trustgrade') . '</h6>';
      $html .= '<div class="row text-muted small fw-semibold mb-1"><div class="col-md-1">' . get_string('correct', 'local_trustgrade') . '</div><div class="col-md-5">' . get_string('optiontext', 'local_trustgrade') . '</div><div class="col-md-6">' . get_string('explanation', 'local_trustgrade') . '</div></div>';
      foreach ($options as $i => $opt) {
          $optText = isset($opt['text']) ? $opt['text'] : '';
          $isCorrect = !empty($opt['is_correct']);
          $explanation = isset($opt['explanation']) ? $opt['explanation'] : '';
          $html .= '<div class="row align-items-start gy-2 gx-3 mb-2">';
          $html .= '<div class="col-md-1 pt-2"><input type="radio" class="form-check-input" name="correct_index" value="' . $i . '"' . ($isCorrect ? ' checked="checked"' : '') . ' aria-label="' . get_string('correct', 'local_trustgrade') . '"></div>';
          $html .= '<div class="col-md-5"><input type="text" class="form-control" name="option_text[' . $i . ']" value="' . s($optText) . '" placeholder="' . s(get_string('option_placeholder', 'local_trustgrade', chr(65 + $i))) . '"></div>';
          $html .= '<div class="col-md-6"><textarea class="form-control" name="option_explanation[' . $i . ']" rows="2" placeholder="' . s(get_string('explanation', 'local_trustgrade')) . '">' . s($explanation) . '</textarea></div>';
          $html .= '</div>';
      }
      $html .= '</div>';

      $html .= '<div class="mt-4 d-flex gap-2">';
      $html .= '<button type="submit" class="btn btn-primary">' . get_string('savechanges') . '</button>';
      $html .= '<a href="' . new \moodle_url('/local/trustgrade/question_bank.php', ['cmid' => $cmid]) . '" class="btn btn-secondary">' . get_string('cancel') . '</a>';
      $html .= '</div></form>';
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
}
