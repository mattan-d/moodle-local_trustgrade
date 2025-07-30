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
                return ['error' => 'Question not found'];
            }
            
            // Validate question data
            $validation_result = self::validate_question_data($question_data);
            if (!$validation_result['valid']) {
                return ['error' => $validation_result['error']];
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
            return ['error' => 'Failed to save question: ' . $e->getMessage()];
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
                return ['error' => 'Question not found'];
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
            return ['error' => 'Failed to delete question: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate question data
     * 
     * @param array $question_data Question data to validate
     * @return array Validation result
     */
    private static function validate_question_data($question_data) {
        // Check required fields
        if (empty($question_data['question'])) {
            return ['valid' => false, 'error' => 'Question text is required'];
        }
        
        if (empty($question_data['type'])) {
            return ['valid' => false, 'error' => 'Question type is required'];
        }
        
        // Validate question type
        $valid_types = ['multiple_choice', 'true_false', 'short_answer'];
        if (!in_array($question_data['type'], $valid_types)) {
            return ['valid' => false, 'error' => 'Invalid question type'];
        }
        
        // Validate multiple choice options
        if ($question_data['type'] === 'multiple_choice') {
            if (!isset($question_data['options']) || !is_array($question_data['options'])) {
                return ['valid' => false, 'error' => 'Multiple choice questions must have options'];
            }
            
            if (count($question_data['options']) < 2) {
                return ['valid' => false, 'error' => 'Multiple choice questions must have at least 2 options'];
            }
            
            foreach ($question_data['options'] as $option) {
                if (empty(trim($option))) {
                    return ['valid' => false, 'error' => 'All options must be filled'];
                }
            }
            
            if (!isset($question_data['correct_answer']) || 
                $question_data['correct_answer'] < 0 || 
                $question_data['correct_answer'] >= count($question_data['options'])) {
                return ['valid' => false, 'error' => 'Invalid correct answer selection'];
            }
        }
        
        // Validate points
        if (isset($question_data['points'])) {
            $points = intval($question_data['points']);
            if ($points < 1 || $points > 100) {
                return ['valid' => false, 'error' => 'Points must be between 1 and 100'];
            }
            $question_data['points'] = $points;
        }
        
        return ['valid' => true];
    }
}
