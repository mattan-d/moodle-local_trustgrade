<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Gateway client for external AI API communication with caching support
 */
class gateway_client {

    private $endpoint;
    private $token;
    private $timeout;
    private $debugMode;

    public function __construct() {
        $this->endpoint = get_config('local_trustgrade', 'gateway_endpoint');
        $this->token = get_config('local_trustgrade', 'gateway_token');
        $this->timeout = 30;
        $this->debugMode = get_config('local_trustgrade', 'debug_mode');

        if (empty($this->endpoint)) {
            throw new \Exception('Gateway endpoint not configured. Please configure the Gateway endpoint in plugin settings.');
        }

        if (empty($this->token)) {
            throw new \Exception('Gateway token not configured. Please configure the Gateway authentication token in plugin settings.');
        }
    }

    /**
     * Check instructions via Gateway with caching support
     *
     * @param string $instructions Assignment instructions
     * @param array $files Array of attachments to send to the gateway
     * @return array Response from Gateway or cache
     */
    public function checkInstructions($instructions, array $files = []) {
        $requestData = [
            'action' => 'check_instructions',
            'instructions' => $instructions,
            'files' => $files,
        ];

        return $this->makeRequestWithCache('check_instructions', $requestData);
    }

    /**
     * Generate questions via Gateway with caching support
     *
     * @param string $instructions Assignment instructions
     * @param int $questionCount Number of questions to generate
     * @return array Response from Gateway or cache
     */
    public function generateQuestions($instructions, $questionCount = 5) {
        $requestData = [
            'action' => 'generate_questions',
            'instructions' => $instructions,
            'question_count' => $questionCount
        ];

        return $this->makeRequestWithCache('generate_questions', $requestData);
    }

    /**
     * Generate submission questions via Gateway with caching support
     *
     * @param string $submissionText Student submission content
     * @param string $instructions Assignment instructions
     * @param int $questionCount Number of questions to generate
     * @param array $files Array of file data (filename, mimetype, content)
     * @return array Response from Gateway or cache
     */
    public function generateSubmissionQuestions($submissionText, $instructions = '', $questionCount = 3, $files = []) {
        $requestData = [
            'action' => 'generate_submission_questions',
            'submission_text' => $submissionText,
            'instructions' => $instructions,
            'question_count' => $questionCount,
            'files' => $files
        ];

        return $this->makeRequestWithCache('generate_submission_questions', $requestData);
    }

    /**
     * Make request with caching support
     *
     * @param string $requestType Type of request for caching
     * @param array $requestData Request data
     * @return array Response data
     */
    private function makeRequestWithCache($requestType, $requestData) {
        // Check cache first if debug mode is enabled
        if ($this->debugMode) {
            $cachedResponse = debug_cache::get_cached_response($requestType, $requestData);
            if ($cachedResponse !== null) {
                // Add cache indicators to response
                $cachedResponse['from_cache'] = true;
                $cachedResponse['cache_source'] = 'debug_cache';

                return [
                    'success' => true,
                    'data' => $cachedResponse
                ];
            }
        }

        // Make actual Gateway request
        $result = $this->makeRequest($requestData);

        // Cache the response if debug mode is enabled and request was successful
        if ($this->debugMode && $result['success']) {
            $this->cacheResponse($requestType, $requestData, $result);
        }

        return $result;
    }

    /**
     * Cache successful Gateway response
     *
     * @param string $requestType Type of request
     * @param array $requestData Original request data
     * @param array $response Gateway response
     */
    private function cacheResponse($requestType, $requestData, $response) {
        try {
            // Prepare response data for caching
            $responseData = $response['data'];

            // Add metadata
            $responseData['gateway_response'] = true;
            $responseData['cached_at'] = time();

            // Use debug_cache to store the response
            debug_cache::save_debug_data(
                $requestType,
                $requestData,
                json_encode($response), // Raw response
                $responseData, // Parsed response for caching
                0 // No specific cmid for Gateway requests
            );

        } catch (\Exception $e) {
            // Log error but don't fail the request
            error_log('Failed to cache Gateway response: ' . $e->getMessage());
        }
    }

    /**
     * Make HTTP request to Gateway
     *
     * @param array $data Request data
     * @return array Response data
     */
    private function makeRequest($data) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Auth: Bearer ' . $this->token, // Cloudflare compatibility
                'Content-Type: application/json',
                'User-Agent: Moodle TrustGrade Plugin'
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return [
                'success' => false,
                'error' => 'Gateway connection error: ' . $error . '. Please check your Gateway endpoint configuration.'
            ];
        }

        if ($httpCode === 401) {
            return [
                'success' => false,
                'error' => 'Gateway authentication failed. Please check your Gateway token configuration.'
            ];
        }

        if ($httpCode === 404) {
            return [
                'success' => false,
                'error' => 'Gateway endpoint not found. Please verify your Gateway endpoint URL.'
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Gateway HTTP error: ' . $httpCode . '. Response: ' . substr($response, 0, 200)
            ];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid Gateway response format. Please check Gateway configuration.'
            ];
        }

        if (!isset($decoded['success'])) {
            return [
                'success' => false,
                'error' => 'Invalid Gateway response structure.'
            ];
        }

        if (!$decoded['success']) {
            return [
                'success' => false,
                'error' => $decoded['error'] ?? 'Unknown Gateway error'
            ];
        }

        return [
            'success' => true,
            'data' => $decoded['data']
        ];
    }

    /**
     * Test Gateway connection (bypasses cache)
     *
     * @return array Test result
     */
    public function testConnection() {
        try {
            $testData = [
                'action' => 'check_instructions',
                'instructions' => 'Test connection to Gateway'
            ];

            // Always bypass cache for connection tests
            $result = $this->makeRequest($testData);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Gateway connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear cached responses for debugging
     *
     * @param string $requestType Optional: specific request type to clear
     * @return bool Success status
     */
    public function clearCache($requestType = null) {
        if (!$this->debugMode) {
            return false;
        }

        try {
            if ($requestType) {
                // Clear specific request type cache
                global $DB;
                $DB->delete_records('local_trustgrade_debug', ['request_type' => $requestType]);
            } else {
                // Clear all cache
                debug_cache::cleanup_old_records();
            }

            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear Gateway cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getCacheStats() {
        if (!$this->debugMode) {
            return ['cache_enabled' => false];
        }

        $stats = debug_cache::get_debug_stats();
        $stats['cache_enabled'] = true;
        $stats['debug_mode'] = $this->debugMode;

        return $stats;
    }
}
