<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Question generator class for AI-powered question creation via Gateway
 */
class question_generator {
    
    /**
     * Generate questions based on assignment instructions
     * 
     * @param string $instructions The assignment instructions
     * @return array Response from Gateway or error
     */
    public static function generate_questions($instructions) {
        // Ensure instructions is a string
        if (!is_string($instructions)) {
            return ['error' => 'Instructions must be a string'];
        }
        
        $instructions = trim($instructions);
        if (empty($instructions)) {
            return ['error' => get_string('no_instructions', 'local_trustgrade')];
        }
        
        // Default to 5 questions
        return self::generate_questions_with_count($instructions, 5);
    }
    
    /**
     * Generate questions based on assignment instructions with custom count
     * 
     * @param string $instructions The assignment instructions
     * @param int $questions_count Number of questions to generate
     * @return array Response from Gateway or error
     */
    public static function generate_questions_with_count($instructions, $questions_count = 5) {
        // Ensure instructions is a string
        if (!is_string($instructions)) {
            return ['error' => 'Instructions must be a string'];
        }
        
        $instructions = trim($instructions);
        if (empty($instructions)) {
            return ['error' => get_string('no_instructions', 'local_trustgrade')];
        }
        
        // Validate questions count
        $questions_count = max(1, min(10, intval($questions_count)));
        
        try {
            $gateway = new gateway_client();
            $result = $gateway->generateQuestions($instructions, $questions_count);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'questions' => $result['data']['questions']
                ];
            } else {
                return ['error' => $result['error']];
            }
            
        } catch (\Exception $e) {
            return ['error' => 'Gateway error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Save generated questions to database
     * 
     * @param int $cmid Course module ID
     * @param array $questions Array of questions
     * @return bool Success status
     */
    public static function save_questions($cmid, $questions) {
        global $DB, $USER;
        
        try {
            if (!$cmid || $cmid <= 0) {
                error_log('TrustGrade: Invalid cmid provided to save_questions: ' . $cmid);
                return false;
            }

            $cm_exists = $DB->record_exists_sql(
                "SELECT cm.id FROM {course_modules} cm 
                 JOIN {modules} md ON md.id = cm.module 
                 WHERE cm.id = ? AND md.name = ?", 
                [$cmid, 'assign']
            );

            if (!$cm_exists) {
                error_log('TrustGrade: Course module not found for cmid: ' . $cmid);
                return false;
            }

            if (!is_array($questions) || empty($questions)) {
                error_log('TrustGrade: Invalid questions array provided to save_questions');
                return false;
            }

            // Delete existing questions for this assignment
            $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);

            // Save new questions
            foreach ($questions as $question) {
                if (!is_array($question) && !is_object($question)) {
                    error_log('TrustGrade: Invalid question data format');
                    continue;
                }

                $record = new \stdClass();
                $record->cmid = $cmid;
                $record->userid = $USER->id;
                $record->question_data = json_encode($question);
                $record->timecreated = time();
                $record->timemodified = time();
                
                $result = $DB->insert_record('local_trustgrade_questions', $record);
                if (!$result) {
                    error_log('TrustGrade: Failed to insert question record for cmid: ' . $cmid);
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log('TrustGrade save_questions error: ' . $e->getMessage() . ' for cmid: ' . $cmid . ', questions count: ' . count($questions));
            return false;
        }
    }
    
    /**
     * Get saved questions for an assignment
     * 
     * @param int $cmid Course module ID
     * @return array Array of questions
     */
    public static function get_questions($cmid) {
        global $DB;
        
        $records = $DB->get_records('local_trustgrade_questions', ['cmid' => $cmid], 'timecreated ASC');
        $questions = [];
        
        foreach ($records as $record) {
            $question_data = json_decode($record->question_data, true);
            if ($question_data) {
                $questions[] = $question_data;
            }
        }
        
        return $questions;
    }
}
