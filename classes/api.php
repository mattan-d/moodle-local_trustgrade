<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * API class with Gateway-only integration
 */
class api {

    /**
     * Send instructions to AI for analysis via Gateway
     *
     * @param string $instructions The assignment instructions (plain text)
     * @param array $files Array of attachment objects: [
     *   ['filename' => string, 'mimetype' => string, 'size' => int, 'content' => string]
     * ]
     * @return array Response from Gateway or error
     */
    public static function check_instructions($instructions, array $files = []) {
        // Ensure instructions is a string
        if (!is_string($instructions)) {
            return ['error' => 'Instructions must be a string'];
        }

        $instructions = trim($instructions);
        if (empty($instructions)) {
            return ['error' => get_string('no_instructions', 'local_trustgrade')];
        }

        try {
            $gateway = new gateway_client();
            $result = $gateway->checkInstructions($instructions, $files);

            if ($result['success']) {
                return [
                    'success' => true,
                    'recommendation' => json_encode($result['data']['recommendation']) ?? json_encode($result['data']['content'])
                ];
            } else {
                return ['error' => $result['error']];
            }

        } catch (\Exception $e) {
            return ['error' => 'Gateway error: ' . $e->getMessage()];
        }
    }
}
