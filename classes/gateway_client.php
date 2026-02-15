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
 * Gateway client for external AI API communication.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
* Gateway client for external AI API communication
*/
class gateway_client {

  private $endpoint;
  private $token;
  private $timeout;

  public function __construct() {
      $this->endpoint = get_config('local_trustgrade', 'gateway_endpoint');
      $this->token = get_config('local_trustgrade', 'gateway_token');
      $this->timeout = 30000;

      if (empty($this->endpoint)) {
          throw new \Exception('Gateway endpoint not configured. Please configure the Gateway endpoint in plugin settings.');
      }

      if (empty($this->token)) {
          throw new \Exception('Gateway token not configured. Please configure the Gateway authentication token in plugin settings.');
      }
  }

  /**
   * Check instructions via Gateway
   *
   * @param string $instructions Assignment instructions
   * @param array $files Array of attachments to send to the gateway
   * @return array Response from Gateway
   */
  public function checkInstructions($instructions, array $files = []) {
      $requestData = [
          'action' => 'check_instructions',
          'instructions' => $instructions,
          'files' => $files,
      ];

      return $this->makeRequest($requestData);
  }

  /**
   * Generate questions via Gateway
   *
   * @param string $instructions Assignment instructions
   * @param int $questionCount Number of questions to generate
   * @param array $files Array of file data (filename, mimetype, size, content[base64])
   * @return array Response from Gateway
   */
  public function generateQuestions($instructions, $questionCount = 5, array $files = []) {
      $requestData = [
              'action' => 'generate_questions',
              'instructions' => $instructions,
              'question_count' => $questionCount,
              'files' => $files,
      ];

      return $this->makeRequest($requestData);
  }

  /**
   * Generate submission questions via Gateway
   *
   * @param string $submissionText Student submission content
   * @param string $instructions Assignment instructions
   * @param int $questionCount Number of questions to generate
   * @param array $files Array of file data (filename, mimetype, content)
   * @param array $metadata Additional metadata (course_id, course_name, course_module_id, user_id)
   * @return array Response from Gateway
   */
  public function generateSubmissionQuestions($submissionText, $instructions = '', $questionCount = 3, $files = [], $metadata = []) {
      $requestData = [
          'action' => 'generate_submission_questions',
          'submission_text' => $submissionText,
          'instructions' => $instructions,
          'question_count' => $questionCount,
          'files' => $files,
          'metadata' => $metadata
      ];

      return $this->makeRequest($requestData);
  }

  /**
   * Make HTTP request to Gateway
   *
   * @param array $data Request data
   * @return array Response data
   */
  private function makeRequest($data) {
      global $CFG;
      
      if (!isset($data['metadata'])) {
          $data['metadata'] = [];
      }
      if (!isset($data['metadata']['moodle_domain'])) {
          $data['metadata']['moodle_domain'] = $CFG->wwwroot;
      }
      
      $curl = new \curl();
      
      $curl->setopt([
          'CURLOPT_TIMEOUT' => $this->timeout,
          'CURLOPT_CONNECTTIMEOUT' => 10,
      ]);
      
      $headers = [
          'Authorization: Bearer ' . $this->token,
          'Auth: Bearer ' . $this->token, // Cloudflare compatibility
          'Content-Type: application/json',
          'User-Agent: Moodle TrustGrade Plugin'
      ];
      
      $response = $curl->post($this->endpoint, json_encode($data), ['CURLOPT_HTTPHEADER' => $headers]);
      
      $info = $curl->get_info();
      $httpCode = isset($info['http_code']) ? $info['http_code'] : 0;
      $error = $curl->get_errno() ? $curl->error : '';

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
          // Try to parse the error response for more specific error messages
          $decoded = json_decode($response, true);
          $error_message = 'Gateway HTTP error: ' . $httpCode;
          
          if ($decoded && isset($decoded['error'])) {
              // Check for specific error types (store key for frontend to resolve via core/str)
              if (strpos($decoded['error'], 'Invalid file type') !== false) {
                  $error_message = 'invalid_file_type_error';
              } else {
                  $error_message = $decoded['error'];
              }
          } else {
              $error_message .= '. Response: ' . substr($response, 0, 200);
          }
          
          return [
              'success' => false,
              'error' => $error_message
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
   * Test Gateway connection
   *
   * @return array Test result
   */
  public function testConnection() {
      try {
          $testData = [
              'action' => 'check_instructions',
              'instructions' => 'Test connection to Gateway'
          ];

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
   * Fetch usage data from Gateway (POST {endpoint}/usage with Authorization: Bearer or api_key in body).
   * Response includes request counts (total, today, week, month, year), limits, and remaining balance.
   *
   * @return array { success: bool, usage: array|null, error: string|null }
   */
  public function getUsage() {
      $base = rtrim($this->endpoint, '/');
      $url = $base . '/usage';

      $curl = new \curl();
      $curl->setopt([
          'CURLOPT_TIMEOUT' => 15,
          'CURLOPT_CONNECTTIMEOUT' => 10,
      ]);
      $headers = [
          'Authorization: Bearer ' . $this->token,
          'Auth: Bearer ' . $this->token, // Cloudflare compatibility
          'Content-Type: application/json',
          'User-Agent: Moodle TrustGrade Plugin',
      ];
      $body = json_encode(['api_key' => $this->token]);
      $response = $curl->post($url, $body, ['CURLOPT_HTTPHEADER' => $headers]);
      $info = $curl->get_info();
      $httpCode = isset($info['http_code']) ? (int) $info['http_code'] : 0;
      $errno = $curl->get_errno();
      $error = $errno ? $curl->error : '';

      if ($errno || $error) {
          return [
              'success' => false,
              'usage' => null,
              'error' => get_string('gateway_usage_connection_error', 'local_trustgrade') . ' ' . $error,
          ];
      }

      if ($httpCode === 401) {
          return [
              'success' => false,
              'usage' => null,
              'error' => get_string('gateway_usage_auth_error', 'local_trustgrade'),
          ];
      }

      if ($httpCode !== 200) {
          return [
              'success' => false,
              'usage' => null,
              'error' => get_string('gateway_usage_http_error', 'local_trustgrade', $httpCode),
          ];
      }

      $decoded = json_decode($response, true);
      if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
          return [
              'success' => false,
              'usage' => null,
              'error' => get_string('gateway_usage_invalid_response', 'local_trustgrade'),
          ];
      }

      if (empty($decoded['success']) || !isset($decoded['usage'])) {
          return [
              'success' => false,
              'usage' => null,
              'error' => $decoded['error'] ?? get_string('gateway_usage_invalid_response', 'local_trustgrade'),
          ];
      }

      return [
          'success' => true,
          'usage' => $decoded['usage'],
          'error' => null,
      ];
  }
}
