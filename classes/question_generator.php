<?php

namespace local_trustgrade;

class question_generator {
    /**
     * Generate questions via Gateway with a specified count and optional files payload.
     *
     * @param string $instructions
     * @param int $count
     * @param array $files Array of attachments:
     *   [ ['filename'=>string, 'mimetype'=>string, 'size'=>int, 'content'=>string(base64)] ]
     * @return array { success: bool, questions?: array, error?: string }
     */
    public static function generate_questions_with_count_and_files($instructions, $count, array $files = []) {
        try {
            $gateway = new \local_trustgrade\gateway_client();
            $result = $gateway->generateQuestions($instructions, (int)$count, $files);

            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Unknown Gateway error'];
            }

            $data = $result['data'] ?? [];
            $questions = $data['questions'] ?? $data['content'] ?? [];

            if (!is_array($questions)) {
                if (is_string($questions)) {
                    $decoded = json_decode($questions, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $questions = $decoded;
                    } else {
                        $questions = [];
                    }
                } else {
                    $questions = [];
                }
            }

            return ['success' => true, 'questions' => $questions];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Gateway error: ' . $e->getMessage()];
        }
    }

    // Other methods can be added here
}
