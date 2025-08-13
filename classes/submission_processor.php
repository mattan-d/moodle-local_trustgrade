<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Submission processor class for AI-powered question generation from student work via Gateway
 */
class submission_processor {
    
    /**
     * Generate questions based on student submission with custom count
     * 
     * @param array $submission_content The student's submission content (text and files)
     * @param string $assignment_instructions The original assignment instructions
     * @param int $questions_count Number of questions to generate
     * @return array Response from Gateway or error
     */
    public static function generate_submission_questions_with_count($submission_content, $assignment_instructions = '', $questions_count = 3) {
        // Ensure submission_content is an array with expected keys
        if (!is_array($submission_content) || (!isset($submission_content['text']) && !isset($submission_content['files']))) {
            return ['error' => 'Submission content must be a structured array'];
        }
        
        $submission_text = trim($submission_content['text'] ?? '');
        $submission_files = $submission_content['files'] ?? [];
        
        if (empty($submission_text) && empty($submission_files)) {
            return ['error' => 'No submission content found to analyze'];
        }
        
        // Validate questions count
        $questions_count = max(1, min(10, intval($questions_count)));
        
        try {
            $gateway = new gateway_client();
            $result = $gateway->generateSubmissionQuestions($submission_text, $assignment_instructions, $questions_count, $submission_files);
            
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

    // Keep the original method for backward compatibility
    public static function generate_submission_questions($submission_text, $assignment_instructions = '') {
        $submission_content = [
            'text' => $submission_text,
            'files' => []
        ];
        return self::generate_submission_questions_with_count($submission_content, $assignment_instructions, 3);
    }
    
    /**
     * Save submission-based questions to database
     * 
     * @param int $submission_id Submission ID
     * @param int $cmid Course module ID
     * @param array $questions Array of questions
     * @return bool Success status
     */
    public static function save_submission_questions($submission_id, $cmid, $questions) {
        global $DB, $USER;
        
        try {
            // Delete existing submission questions for this submission
            $DB->delete_records('local_trustgd_sub_questions', [
                'submission_id' => $submission_id,
                'cmid' => $cmid
            ]);
            
            // Save new questions
            foreach ($questions as $question) {
                $record = new \stdClass();
                $record->submission_id = $submission_id;
                $record->cmid = $cmid;
                $record->userid = $USER->id;
                $record->question_data = json_encode($question);
                $record->timecreated = time();
                $record->timemodified = time();
                
                $DB->insert_record('local_trustgd_sub_questions', $record);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Failed to save submission questions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get quiz questions based on settings
     * 
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID (optional)
     * @param array $settings Quiz settings
     * @return array Selected questions for quiz
     */
    public static function get_quiz_questions_with_settings($cmid, $submission_id, $settings) {
        global $DB;
        
        $selected_questions = [];
        
        // Get instructor-generated questions
        $instructor_questions = question_generator::get_questions($cmid);
        foreach ($instructor_questions as &$question) {
            $question['source'] = 'instructor';
        }
        
        // Get submission-based questions if submission_id is provided
        $submission_questions = [];
        if ($submission_id) {
            $records = $DB->get_records('local_trustgd_sub_questions', [
                'submission_id' => $submission_id,
                'cmid' => $cmid
            ], 'timecreated ASC');
            
            foreach ($records as $record) {
                $question_data = json_decode($record->question_data, true);
                if ($question_data) {
                    $question_data['source'] = 'submission';
                    $submission_questions[] = $question_data;
                }
            }
        }
        
        // Select questions based on settings
        $instructor_count = min($settings['instructor_questions'], count($instructor_questions));
        $submission_count = min($settings['submission_questions'], count($submission_questions));
        
        // Randomly select instructor questions
        if ($instructor_count > 0 && !empty($instructor_questions)) {
            $selected_instructor = array_rand($instructor_questions, min($instructor_count, count($instructor_questions)));
            if (!is_array($selected_instructor)) {
                $selected_instructor = [$selected_instructor];
            }
            foreach ($selected_instructor as $index) {
                $selected_questions[] = $instructor_questions[$index];
            }
        }
        
        // Randomly select submission questions
        if ($submission_count > 0 && !empty($submission_questions)) {
            $selected_submission = array_rand($submission_questions, min($submission_count, count($submission_questions)));
            if (!is_array($selected_submission)) {
                $selected_submission = [$selected_submission];
            }
            foreach ($selected_submission as $index) {
                $selected_questions[] = $submission_questions[$index];
            }
        }
        
        // Shuffle the final question order
        shuffle($selected_questions);
        
        // Limit to total quiz questions setting
        $selected_questions = array_slice($selected_questions, 0, $settings['total_quiz_questions']);
        
        // Apply answer randomization if enabled
        if ($settings['randomize_answers']) {
            foreach ($selected_questions as &$question) {
                if ($question['type'] === 'multiple_choice' && isset($question['options'])) {
                    $question = self::randomize_question_options($question);
                }
            }
        }
        
        return $selected_questions;
    }
    
    /**
     * Randomize multiple choice question options
     * 
     * @param array $question Question data
     * @return array Question with randomized options
     */
    private static function randomize_question_options($question) {
        if ($question['type'] !== 'multiple_choice' || !isset($question['options'])) {
            return $question;
        }
        
        $options = $question['options'];
        $correct_answer = $question['correct_answer'];
        $correct_option = $options[$correct_answer];
        
        // Shuffle options
        shuffle($options);
        
        // Find new position of correct answer
        $new_correct_answer = array_search($correct_option, $options);
        
        $question['options'] = $options;
        $question['correct_answer'] = $new_correct_answer;
        
        return $question;
    }
    
    /**
     * Get all questions for a student (instructor + submission-based)
     * 
     * @param int $cmid Course module ID
     * @param int $submission_id Submission ID (optional)
     * @return array Combined questions array
     */
    public static function get_all_questions_for_student($cmid, $submission_id = null) {
        global $DB;
        
        $all_questions = [];
        
        // Get instructor-generated questions
        $instructor_questions = question_generator::get_questions($cmid);
        foreach ($instructor_questions as $question) {
            $question['source'] = 'instructor';
            $all_questions[] = $question;
        }
        
        // Get submission-based questions if submission_id is provided
        if ($submission_id) {
            $records = $DB->get_records('local_trustgd_sub_questions', [
                'submission_id' => $submission_id,
                'cmid' => $cmid
            ], 'timecreated ASC');
            
            foreach ($records as $record) {
                $question_data = json_decode($record->question_data, true);
                if ($question_data) {
                    $question_data['source'] = 'submission';
                    $all_questions[] = $question_data;
                }
            }
        }
        
        return $all_questions;
    }
    
    /**
     * Extract text and file content from submission
     * 
     * @param object $submission Submission object
     * @param \context $context Assignment context
     * @return array Extracted content including text and base64 encoded files
     */
    public static function extract_submission_content($submission, $context) {
        global $DB;
        
        $content = [
            'text' => '',
            'files' => []
        ];
        
        // Get submission plugins data for online text
        $submission_plugins = $DB->get_records('assignsubmission_onlinetext', 
            ['submission' => $submission->id]);
        
        foreach ($submission_plugins as $plugin_data) {
            if (!empty($plugin_data->onlinetext)) {
                $content['text'] .= strip_tags($plugin_data->onlinetext) . "\n";
            }
        }
        
        // Handle file submissions - get correct itemid from assignsubmission_file table
        $file_submissions = $DB->get_records('assignsubmission_file', 
            ['submission' => $submission->id]);
        
        $fs = get_file_storage();
        
        foreach ($file_submissions as $file_submission) {
            $files = $fs->get_area_files(
                $context->id,
                'assignsubmission_file',
                'submission_files',
                $file_submission->id, // Use the correct itemid from assignsubmission_file table
                'timemodified',
                false
            );
            
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                
                $file_content = $file->get_content();
                if (!empty($file_content)) {
                    $content['files'][] = [
                        'filename' => $file->get_filename(),
                        'mimetype' => $file->get_mimetype(),
                        'size' => $file->get_filesize(),
                        'content' => base64_encode($file_content)
                    ];
                }
            }
        }
        
        $content['text'] = trim($content['text']);
        return $content;
    }
}
