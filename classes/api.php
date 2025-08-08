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
     * @param string $instructions The assignment instructions
     * @return array Response from Gateway or error
     */
    public static function check_instructions($instructions) {
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
            $result = $gateway->checkInstructions($instructions);
            
            if ($result['success']) {
                // The new structure is expected in $result['data']
                $response = [
                    'success' => true,
                    // The external function expects a 'recommendation' key.
                    // The data from gateway is now the recommendation payload.
                    'recommendation' => $result['data']
                ];
                if (isset($result['data']['from_cache'])) {
                    $response['from_cache'] = $result['data']['from_cache'];
                }
                return $response;
            } else {
                return ['error' => $result['error']];
            }
            
        } catch (\Exception $e) {
            return ['error' => 'Gateway error: ' . $e->getMessage()];
        }
    }
}
