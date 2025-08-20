<?php
// This file is part of Moodle - http://moodle.org/

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
          
          if (!isset($existing_questions[$question_index])) {
              return ['success' => false, 'error' => 'Question not found'];
          }
          
          // Validate question data
          $validation_result = self::validate_question_data($question_data);
          if (!$validation_result['valid']) {
              return ['success' => false, 'error' => $validation_result['error']];
          }
          
          // Update the question in the array
          $existing_questions[$question_index] = $question_data;
          
          // Delete existing questions for this assignment
          $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);
          
          // Save updated questions
          foreach ($existing_questions as $question) {
              $record = new \stdClass();
              $record->cmid = $cmid;
              $record->userid = $USER->id;
              $record->question_data = json_encode($question);
              $record->timecreated = time();
              $record->timemodified = time();
              
              $DB->insert_record('local_trustgrade_questions', $record);
          }
          
          return ['success' => true, 'message' => 'Question saved successfully'];
          
      } catch (\Exception $e) {
          return ['success' => false, 'error' => 'Failed to save question: ' . $e->getMessage()];
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
              return ['success' => false, 'error' => 'Question not found'];
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
              $record->timecreated = time();
              $record->timemodified = time();
              
              $DB->insert_record('local_trustgrade_questions', $record);
          }
          
          return ['success' => true, 'message' => 'Question deleted successfully'];
          
      } catch (\Exception $e) {
          return ['success' => false, 'error' => 'Failed to delete question: ' . $e->getMessage()];
      }
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
              return ['valid' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()];
          }
          $question_data = $decoded;
      }

      if (!is_array($question_data)) {
          return ['valid' => false, 'error' => 'Question data must be an associative array'];
      }

      // Required: type
      if (empty($question_data['type']) || !is_string($question_data['type'])) {
          return ['valid' => false, 'error' => 'Question type is required'];
      }
      $type = $question_data['type'];
      $valid_types = ['multiple_choice'];
      if (!in_array($type, $valid_types, true)) {
          return ['valid' => false, 'error' => 'Invalid question type'];
      }

      // Required: text (new schema replaces "question")
      if (!isset($question_data['text']) || !is_string($question_data['text']) || trim($question_data['text']) === '') {
          return ['valid' => false, 'error' => 'Question text (field "text") is required'];
      }

      // Options validation for choice-based types (per-option explanations in new schema)
      if ($type === 'multiple_choice') {
          if (!isset($question_data['options']) || !is_array($question_data['options'])) {
              return ['valid' => false, 'error' => 'Options must be provided as an array'];
          }
          if (count($question_data['options']) < 2) {
              return ['valid' => false, 'error' => 'At least 2 options are required'];
          }

          $correctCount = 0;

          foreach ($question_data['options'] as $index => $opt) {
              if (!is_array($opt)) {
                  return ['valid' => false, 'error' => 'Each option must be an object'];
              }

              // id is recommended numeric; allow missing but if present must be numeric
              if (isset($opt['id']) && !is_numeric($opt['id'])) {
                  return ['valid' => false, 'error' => "Option at index {$index} has non-numeric id"];
              }

              // text is required and non-empty
              if (!isset($opt['text']) || !is_string($opt['text']) || trim($opt['text']) === '') {
                  return ['valid' => false, 'error' => "Option at index {$index} must include non-empty 'text'"];
              }

              // is_correct is required and boolean-like
              if (!array_key_exists('is_correct', $opt)) {
                  return ['valid' => false, 'error' => "Option at index {$index} must include 'is_correct'"];
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
                  return ['valid' => false, 'error' => "Option at index {$index} has invalid 'is_correct' (must be boolean)"];
              }
              if ($isCorrect) {
                  $correctCount++;
              }

              // explanation is per-option; optional but must be string if present
              if (isset($opt['explanation']) && !is_string($opt['explanation'])) {
                  return ['valid' => false, 'error' => "Option at index {$index} has invalid 'explanation' (must be string)"];
              }
          }

          if ($type === 'multiple_choice' && $correctCount < 1) {
              return ['valid' => false, 'error' => 'Multiple choice questions must have at least one correct option'];
          }
      }

      // Metadata (optional) with points moved under metadata in new schema
      if (isset($question_data['metadata'])) {
          if (!is_array($question_data['metadata'])) {
              return ['valid' => false, 'error' => 'Metadata must be an object'];
          }
          if (isset($question_data['metadata']['points'])) {
              $points = (int)$question_data['metadata']['points'];
              if ($points < 1 || $points > 100) {
                  return ['valid' => false, 'error' => 'Points must be between 1 and 100'];
              }
          }
          if (isset($question_data['metadata']['blooms_level']) && !is_string($question_data['metadata']['blooms_level'])) {
              return ['valid' => false, 'error' => "Metadata 'blooms_level' must be a string"];
          }
      }

      // Ignore legacy fields if sent; new schema no longer uses these.
      // do not fail if 'difficulty' or 'correct_answer' appear; we simply do not rely on them.

      return ['valid' => true];
  }
}
