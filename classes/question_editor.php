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
 * Question editor class for managing instructor question edits.
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
* Question editor class for managing instructor question edits
*/
class question_editor {
  
  /**
   * Save an edited question
   * 
   * @param int $cmid Course module ID
   * @param int $question_index Question index to update
   * @param array $question_data New question data
   * @return array Success/error response
   */
  public static function save_question($cmid, $question_index, $question_data) {
      global $DB, $USER;
      
      try {
          // Get existing questions
          $existing_questions = question_generator::get_questions($cmid);
          
          // Validate question data
          $validation_result = self::validate_question_data($question_data);
          if (!$validation_result['valid']) {
              return ['success' => false, 'error' => $validation_result['error']];
          }
          
          if (isset($existing_questions[$question_index])) {
              // Update existing question
              $existing_questions[$question_index] = $question_data;
          } else {
              // Add new question - append to the end of the array
              $existing_questions[] = $question_data;
          }
          
          // Delete existing questions for this assignment
          $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);
          
          // Save updated questions
          foreach ($existing_questions as $question) {
              $record = new \stdClass();
              $record->cmid = $cmid;
              $record->userid = $USER->id;
              $record->question_data = json_encode($question);
              $record->is_mandatory = isset($question['is_mandatory']) ? intval($question['is_mandatory']) : 0;
              $record->timecreated = time();
              $record->timemodified = time();
              
              $DB->insert_record('local_trustgrade_questions', $record);
          }
          
          return ['success' => true, 'message' => get_string('question_saved_successfully', 'local_trustgrade')];
          
      } catch (\Exception $e) {
          return ['success' => false, 'error' => get_string('failed_save_question', 'local_trustgrade', $e->getMessage())];
      }
  }
  
  /**
   * Delete a question
   * 
   * @param int $cmid Course module ID
   * @param int $question_index Question index to delete
   * @return array Success/error response
   */
  public static function delete_question($cmid, $question_index) {
      global $DB, $USER;
      
      try {
          // Get existing questions
          $existing_questions = question_generator::get_questions($cmid);
          
          if (!isset($existing_questions[$question_index])) {
              return ['success' => false, 'error' => get_string('question_not_found_error', 'local_trustgrade')];
          }
          
          // Remove the question from the array
          unset($existing_questions[$question_index]);
          
          // Reindex array to maintain sequential indices
          $existing_questions = array_values($existing_questions);
          
          // Delete existing questions for this assignment
          $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);
          
          // Save remaining questions
          foreach ($existing_questions as $question) {
              $record = new \stdClass();
              $record->cmid = $cmid;
              $record->userid = $USER->id;
              $record->question_data = json_encode($question);
              $record->is_mandatory = isset($question['is_mandatory']) ? intval($question['is_mandatory']) : 0;
              $record->timecreated = time();
              $record->timemodified = time();
              
              $DB->insert_record('local_trustgrade_questions', $record);
          }
          
          return ['success' => true, 'message' => get_string('question_deleted_successfully', 'local_trustgrade')];
          
      } catch (\Exception $e) {
          return ['success' => false, 'error' => get_string('failed_delete_question', 'local_trustgrade', $e->getMessage())];
      }
  }
  
  /**
   * Update a single question by its database record id.
   *
   * @param int $questionid Record id in local_trustgrade_questions
   * @param int $cmid Course module ID (must match the record)
   * @param array $question_data Decoded question data
   * @return array Success/error response
   */
  public static function update_question_by_id($questionid, $cmid, $question_data) {
      global $DB;

      $record = $DB->get_record('local_trustgrade_questions', ['id' => $questionid, 'cmid' => $cmid], '*', MUST_EXIST);
      $validation = self::validate_question_data($question_data);
      if (!$validation['valid']) {
          return ['success' => false, 'error' => $validation['error']];
      }
      $record->question_data = json_encode($question_data);
      $record->is_mandatory = isset($question_data['is_mandatory']) ? (int) $question_data['is_mandatory'] : 0;
      $record->timemodified = time();
      $DB->update_record('local_trustgrade_questions', $record);
      return ['success' => true, 'message' => get_string('question_saved_successfully', 'local_trustgrade')];
  }

  /**
   * Validate question data
   *
   * @param array $question_data Question data to validate
   * @return array Validation result
   */
  private static function validate_question_data($question_data) {
      // Allow receiving a JSON string; decode to array.
      if (is_string($question_data)) {
          $decoded = json_decode($question_data, true);
          if (json_last_error() !== JSON_ERROR_NONE) {
              return ['valid' => false, 'error' => get_string('invalid_json_error', 'local_trustgrade', json_last_error_msg())];
          }
          $question_data = $decoded;
      }

      if (!is_array($question_data)) {
          return ['valid' => false, 'error' => get_string('question_data_must_be_array', 'local_trustgrade')];
      }

      // Required: type
      if (empty($question_data['type']) || !is_string($question_data['type'])) {
          return ['valid' => false, 'error' => get_string('question_type_required', 'local_trustgrade')];
      }
      $type = $question_data['type'];
      $valid_types = ['multiple_choice'];
      if (!in_array($type, $valid_types, true)) {
          return ['valid' => false, 'error' => get_string('invalid_question_type', 'local_trustgrade')];
      }

      // Required: text (new schema replaces "question")
      if (!isset($question_data['text']) || !is_string($question_data['text']) || trim($question_data['text']) === '') {
          return ['valid' => false, 'error' => get_string('question_text_field_required', 'local_trustgrade')];
      }

      // Options validation for choice-based types (per-option explanations in new schema)
      if ($type === 'multiple_choice') {
          if (!isset($question_data['options']) || !is_array($question_data['options'])) {
              return ['valid' => false, 'error' => get_string('options_must_be_array', 'local_trustgrade')];
          }
          if (count($question_data['options']) < 2) {
              return ['valid' => false, 'error' => get_string('at_least_2_options_required', 'local_trustgrade')];
          }

          $correctCount = 0;

          foreach ($question_data['options'] as $index => $opt) {
              if (!is_array($opt)) {
                  return ['valid' => false, 'error' => get_string('option_must_be_object', 'local_trustgrade')];
              }

              // id is recommended numeric; allow missing but if present must be numeric
              if (isset($opt['id']) && !is_numeric($opt['id'])) {
                  return ['valid' => false, 'error' => get_string('option_non_numeric_id', 'local_trustgrade', $index)];
              }

              // text is required and non-empty
              if (!isset($opt['text']) || !is_string($opt['text']) || trim($opt['text']) === '') {
                  return ['valid' => false, 'error' => get_string('option_text_required', 'local_trustgrade', $index)];
              }

              // is_correct is required and boolean-like
              if (!array_key_exists('is_correct', $opt)) {
                  return ['valid' => false, 'error' => get_string('option_is_correct_required', 'local_trustgrade', $index)];
              }
              $isCorrectRaw = $opt['is_correct'];
              // Accept true/false, 'true'/'false', 1/0
              $isCorrect = null;
              if (is_bool($isCorrectRaw)) {
                  $isCorrect = $isCorrectRaw;
              } elseif ($isCorrectRaw === 1 || $isCorrectRaw === 0 || $isCorrectRaw === '1' || $isCorrectRaw === '0') {
                  $isCorrect = (bool)((int)$isCorrectRaw);
              } elseif (is_string($isCorrectRaw) && in_array(strtolower($isCorrectRaw), ['true', 'false'], true)) {
                  $isCorrect = strtolower($isCorrectRaw) === 'true';
              }
              if ($isCorrect === null) {
                  return ['valid' => false, 'error' => get_string('option_is_correct_invalid', 'local_trustgrade', $index)];
              }
              if ($isCorrect) {
                  $correctCount++;
              }

              // explanation is per-option; optional but must be string if present
              if (isset($opt['explanation']) && !is_string($opt['explanation'])) {
                  return ['valid' => false, 'error' => get_string('option_explanation_invalid', 'local_trustgrade', $index)];
              }
          }

          if ($type === 'multiple_choice' && $correctCount < 1) {
              return ['valid' => false, 'error' => get_string('at_least_one_correct_option', 'local_trustgrade')];
          }
      }

      // Metadata (optional) with points moved under metadata in new schema
      if (isset($question_data['metadata'])) {
          if (!is_array($question_data['metadata'])) {
              return ['valid' => false, 'error' => get_string('metadata_must_be_object', 'local_trustgrade')];
          }
          if (isset($question_data['metadata']['points'])) {
              $points = (int)$question_data['metadata']['points'];
              if ($points < 1 || $points > 100) {
                  return ['valid' => false, 'error' => get_string('points_must_be_1_to_100', 'local_trustgrade')];
              }
          }
          if (isset($question_data['metadata']['blooms_level']) && !is_string($question_data['metadata']['blooms_level'])) {
              return ['valid' => false, 'error' => get_string('blooms_level_must_be_string', 'local_trustgrade')];
          }
      }

      // Ignore legacy fields if sent; new schema no longer uses these.
      // do not fail if 'difficulty' or 'correct_answer' appear; we simply do not rely on them.

      return ['valid' => true];
  }
}
