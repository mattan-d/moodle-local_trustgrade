<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz settings manager class
 */
class quiz_settings {
    
    /**
     * Get quiz settings for an assignment
     * 
     * @param int $cmid Course module ID
     * @return array Quiz settings
     */
    public static function get_settings($cmid) {
        global $DB;
        
        $record = $DB->get_record('local_trustgd_quiz_settings', ['cmid' => $cmid]);
        
        if (!$record) {
            // Return default settings
            $settings = self::get_default_settings();
        } else {
            $settings = [
                'enabled' => (bool)$record->enabled,
                'questions_to_generate' => (int)$record->questions_to_generate,
                'instructor_questions' => (int)$record->instructor_questions,
                'submission_questions' => (int)$record->submission_questions,
                'randomize_answers' => (bool)$record->randomize_answers,
                'time_per_question' => (int)$record->time_per_question,
                'show_countdown' => (bool)$record->show_countdown
            ];
        }
        
        // The total number of questions is the sum of instructor and submission questions.
        $settings['total_quiz_questions'] = $settings['instructor_questions'] + $settings['submission_questions'];
        
        return $settings;
    }
    
    /**
     * Save quiz settings for an assignment
     * 
     * @param int $cmid Course module ID
     * @param array $settings Settings to save
     * @return bool Success status
     */
    public static function save_settings($cmid, $settings) {
        global $DB;
        
        try {
            // Validate settings
            $validated_settings = self::validate_settings($settings);
            if (!$validated_settings) {
                return false;
            }
            
            $existing = $DB->get_record('local_trustgd_quiz_settings', ['cmid' => $cmid]);
            
            if ($existing) {
                // Update existing settings
                $existing->enabled = $validated_settings['enabled'] ? 1 : 0;
                $existing->questions_to_generate = $validated_settings['questions_to_generate'];
                $existing->instructor_questions = $validated_settings['instructor_questions'];
                $existing->submission_questions = $validated_settings['submission_questions'];
                $existing->randomize_answers = $validated_settings['randomize_answers'] ? 1 : 0;
                $existing->time_per_question = $validated_settings['time_per_question'];
                $existing->show_countdown = $validated_settings['show_countdown'] ? 1 : 0;
                $existing->timemodified = time();
                
                $DB->update_record('local_trustgd_quiz_settings', $existing);
            } else {
                // Create new settings
                $record = new \stdClass();
                $record->cmid = $cmid;
                $record->enabled = $validated_settings['enabled'] ? 1 : 0;
                $record->questions_to_generate = $validated_settings['questions_to_generate'];
                $record->instructor_questions = $validated_settings['instructor_questions'];
                $record->submission_questions = $validated_settings['submission_questions'];
                $record->randomize_answers = $validated_settings['randomize_answers'] ? 1 : 0;
                $record->time_per_question = $validated_settings['time_per_question'];
                $record->show_countdown = $validated_settings['show_countdown'] ? 1 : 0;
                $record->timecreated = time();
                $record->timemodified = time();
                
                $DB->insert_record('local_trustgd_quiz_settings', $record);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get default quiz settings
     * 
     * @return array Default settings
     */
    public static function get_default_settings() {
        return [
            'enabled' => true,
            'questions_to_generate' => 5,
            'instructor_questions' => 3,
            'submission_questions' => 2,
            'randomize_answers' => true,
            'time_per_question' => 15,
            'show_countdown' => true
        ];
    }
    
    /**
     * Validate quiz settings
     * 
     * @param array $settings Settings to validate
     * @return array|false Validated settings or false if invalid
     */
    private static function validate_settings($settings) {
        $validated = [];
        
        // Enabled field validation
        $validated['enabled'] = !empty($settings['enabled']);
        
        // Questions to generate (1-10)
        $validated['questions_to_generate'] = max(1, min(10, intval($settings['questions_to_generate'] ?? 5)));
        
        // Instructor questions (0-20)
        $validated['instructor_questions'] = max(0, min(20, intval($settings['instructor_questions'] ?? 3)));
        
        // Submission questions (0-20)
        $validated['submission_questions'] = max(0, min(20, intval($settings['submission_questions'] ?? 2)));
        
        // Randomize answers (boolean)
        $validated['randomize_answers'] = !empty($settings['randomize_answers']);
        
        // Time per question (10, 15, 20, 25, 30)
        $valid_times = [10, 15, 20, 25, 30];
        $time = intval($settings['time_per_question'] ?? 15);
        $validated['time_per_question'] = in_array($time, $valid_times) ? $time : 15;
        
        // Show countdown (boolean)
        $validated['show_countdown'] = !empty($settings['show_countdown']);
        
        return $validated;
    }
}
