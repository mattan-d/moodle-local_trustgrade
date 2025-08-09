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
     * Adds support for the new teacher JSON pattern:
     * [
     *   {
     *     "id": 1,
     *     "type": "multiple_choice",
     *     "text": "Question text here",
     *     "options": [
     *       { "id": 1, "text": "Option A", "is_correct": true, "explanation": "Why A is correct" },
     *       { "id": 2, "text": "Option B", "is_correct": false, "explanation": "Why B is incorrect" },
     *       ...
     *     ],
     *     "metadata": { "blooms_level": "Understand", "points": 10 }
     *   }
     * ]
     *
     * We normalize this to the internal canonical shape the UI expects:
     * {
     *   "question": string,
     *   "type": "multiple_choice" | "true_false" | "short_answer",
     *   "difficulty": string,          // mapped from metadata.blooms_level when present
     *   "points": int,                 // from metadata.points when present
     *   "explanation": string,         // aggregated from option explanations or left empty
     *   "options": string[]            // option texts
     *   "correct_answer": int          // index into options (0-based)
     * }
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

            if (!isset($result['success'])) {
                return ['error' => 'Gateway response missing success flag'];
            }

            if ($result['success']) {
                $questions = isset($result['data']['questions']) ? $result['data']['questions'] : [];

                // If the gateway returns the new teacher pattern, normalize to the internal canonical shape
                if (self::is_new_teacher_pattern($questions)) {
                    $questions = self::normalize_questions_from_teacher_pattern($questions);
                }

                // If the gateway already returns canonical questions, pass through
                return [
                    'success' => true,
                    'questions' => $questions,
                ];
            } else {
                // Gateway error message passthrough
                return ['error' => isset($result['error']) ? $result['error'] : 'Unknown gateway error'];
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

    // -----------------------------
    // Internal normalization helpers
    // -----------------------------

    /**
     * Detects the new teacher JSON pattern shape.
     *
     * @param mixed $questions
     * @return bool
     */
    private static function is_new_teacher_pattern($questions) {
        if (!is_array($questions) || empty($questions)) {
            return false;
        }

        $first = $questions[0];

        // Must be associative with "text" and "options"
        if (!is_array($first) || !isset($first['text']) || !isset($first['options']) || !is_array($first['options'])) {
            return false;
        }

        // Options are objects with "text" or at least associative arrays
        $firstOption = isset($first['options'][0]) ? $first['options'][0] : null;
        if (!is_array($firstOption)) {
            return false;
        }

        // In the new pattern, option has "text" and may have "is_correct" and "explanation"
        return array_key_exists('text', $firstOption);
    }

    /**
     * Normalizes an array of new-teacher-pattern questions to the internal canonical shape.
     *
     * @param array $questions
     * @return array
     */
    private static function normalize_questions_from_teacher_pattern(array $questions) {
        $normalized = [];

        foreach ($questions as $q) {
            $normalized[] = self::normalize_single_teacher_question($q);
        }

        return $normalized;
    }

    /**
     * Normalize a single teacher-pattern question to the internal canonical shape.
     *
     * @param array $q
     * @return array
     */
    private static function normalize_single_teacher_question(array $q) {
        $type = isset($q['type']) ? strtolower((string)$q['type']) : 'multiple_choice';

        // Map incoming type to internal type names
        $internalType = self::map_question_type($type);

        // Difficulty from Blooms level if available, else default "Medium"
        $difficulty = isset($q['metadata']['blooms_level']) && is_string($q['metadata']['blooms_level'])
            ? (string)$q['metadata']['blooms_level']
            : 'Medium';

        // Points if present
        $points = isset($q['metadata']['points']) ? intval($q['metadata']['points']) : 1;

        $questionText = isset($q['text']) ? (string)$q['text'] : '';

        $optionsTexts = [];
        $correctIndex = 0;
        $foundCorrect = false;

        $explanations = [];
        if (isset($q['options']) && is_array($q['options'])) {
            foreach ($q['options'] as $idx => $opt) {
                $text = isset($opt['text']) ? (string)$opt['text'] : '';
                $optionsTexts[] = $text;

                $isCorrect = isset($opt['is_correct']) ? (bool)$opt['is_correct'] : false;
                if ($isCorrect && !$foundCorrect) {
                    $correctIndex = $idx;
                    $foundCorrect = true;
                }

                if (isset($opt['explanation']) && is_string($opt['explanation']) && trim($opt['explanation']) !== '') {
                    $explanations[] = 'Option ' . chr(65 + $idx) . ': ' . $opt['explanation'];
                }
            }
        }

        // Aggregate explanations to a single string (optional field in UI)
        $explanation = '';
        if (!empty($explanations)) {
            $explanation = implode("\n", $explanations);
        }

        $normalized = [
            'question' => $questionText,
            'type' => $internalType,
            'difficulty' => $difficulty,
            'points' => $points,
            'explanation' => $explanation,
        ];

        if ($internalType === 'multiple_choice') {
            $normalized['options'] = $optionsTexts;
            $normalized['correct_answer'] = $correctIndex;
        } elseif ($internalType === 'true_false') {
            // If options are True/False, we try to infer boolean from the correct option.
            // Fallback: default to true if not inferrable.
            $normalized['correct_answer'] = self::infer_true_false_from_options($q);
        }

        return $normalized;
    }

    /**
     * Map external type strings to internal type identifiers.
     *
     * @param string $type
     * @return string
     */
    private static function map_question_type($type) {
        switch ($type) {
            case 'multiple_choice':
            case 'mcq':
            case 'multi_choice':
                return 'multiple_choice';
            case 'true_false':
            case 'boolean':
            case 'tf':
                return 'true_false';
            case 'short_answer':
            case 'free_text':
            case 'open_ended':
                return 'short_answer';
            default:
                return 'multiple_choice';
        }
    }

    /**
     * Infer a boolean answer from teacher options for a true/false style question.
     *
     * @param array $q
     * @return bool
     */
    private static function infer_true_false_from_options(array $q) {
        if (!isset($q['options']) || !is_array($q['options'])) {
            return true;
        }

        foreach ($q['options'] as $opt) {
            if (!isset($opt['text']) || !isset($opt['is_correct'])) {
                continue;
            }
            $t = strtolower(trim((string)$opt['text']));
            $isCorrect = (bool)$opt['is_correct'];
            if (($t === 'true' || $t === 't') && $isCorrect) {
                return true;
            }
            if (($t === 'false' || $t === 'f') && $isCorrect) {
                return false;
            }
        }

        // Default fallback
        return true;
    }
}
