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
                $rawquestions = isset($result['data']['questions']) && is_array($result['data']['questions'])
                    ? $result['data']['questions']
                    : [];

                $normalized = self::normalize_questions($rawquestions);

                return [
                    'success' => true,
                    'questions' => $normalized
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
            // Delete existing questions for this assignment
            $DB->delete_records('local_trustgrade_questions', ['cmid' => $cmid]);
            
            // Save new questions
            foreach ($questions as $question) {
                $record = new \stdClass();
                $record->cmid = $cmid;
                $record->userid = $USER->id;
                $record->question_data = json_encode($question);
                $record->timecreated = time();
                $record->timemodified = time();
                
                $DB->insert_record('local_trustgrade_questions', $record);
            }
            
            return true;
        } catch (\Exception $e) {
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

    private static function normalize_questions($rawquestions) {
        $out = [];

        if (!is_array($rawquestions)) {
            return $out;
        }

        foreach ($rawquestions as $q) {
            // If already in the internal shape, pass through unchanged.
            if (is_array($q) && isset($q['question'])) {
                $out[] = $q;
                continue;
            }

            if (!is_array($q)) {
                continue;
            }

            $type = isset($q['type']) ? (string)$q['type'] : 'multiple_choice';
            $metadata = isset($q['metadata']) && is_array($q['metadata']) ? $q['metadata'] : [];

            $normalized = [
                'question' => isset($q['text']) ? (string)$q['text'] : '',
                'type' => $type,
                'difficulty' => isset($metadata['blooms_level']) ? (string)$metadata['blooms_level'] : '',
                'points' => isset($metadata['points']) ? (int)$metadata['points'] : 1,
                'explanation' => '',
            ];

            if ($type === 'multiple_choice') {
                $options = [];
                $correctIndex = 0;

                if (isset($q['options']) && is_array($q['options'])) {
                    $idx = 0;
                    foreach ($q['options'] as $opt) {
                        $text = isset($opt['text']) ? (string)$opt['text'] : '';
                        $options[] = $text;

                        $isCorrect = !empty($opt['is_correct']);
                        if ($isCorrect) {
                            $correctIndex = $idx;
                            if (!empty($opt['explanation'])) {
                                $normalized['explanation'] = trim((string)$opt['explanation']);
                            }
                        }
                        $idx++;
                    }
                }

                if (empty($options)) {
                    // Ensure at least 4 options exist to keep editor stable.
                    $options = ['Option A', 'Option B', 'Option C', 'Option D'];
                    $correctIndex = 0;
                }

                $normalized['options'] = $options;
                $normalized['correct_answer'] = $correctIndex;

            } else if ($type === 'true_false') {
                // Derive boolean from options list if provided
                $correctBool = null;
                if (isset($q['options']) && is_array($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        if (!empty($opt['is_correct'])) {
                            $text = strtolower(isset($opt['text']) ? (string)$opt['text'] : '');
                            $correctBool = ($text === 'true');
                            if (!empty($opt['explanation'])) {
                                $normalized['explanation'] = trim((string)$opt['explanation']);
                            }
                            break;
                        }
                    }
                }
                if (is_null($correctBool)) {
                    $correctBool = false;
                }
                $normalized['correct_answer'] = $correctBool ? true : false;
            } else {
                // For other types, keep minimal fields. The editor can be extended later.
                $normalized['correct_answer'] = null;
            }

            $out[] = $normalized;
        }

        return $out;
    }
}
