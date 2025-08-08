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
                // The new API returns structured data
                $responseData = $result['data'];
                
                // Check if we have the new structured format
                if (isset($responseData['table']) || isset($responseData['EvaluationText']) || isset($responseData['ImprovedAssignment'])) {
                    // Return the structured data as JSON string for the frontend
                    return [
                        'success' => true,
                        'recommendation' => json_encode($responseData),
                        'from_cache' => $responseData['from_cache'] ?? false
                    ];
                } else {
                    // Fallback to old format if structure is not as expected
                    return [
                        'success' => true,
                        'recommendation' => $responseData['recommendation'] ?? $responseData['content'] ?? 'No recommendation available',
                        'from_cache' => $responseData['from_cache'] ?? false
                    ];
                }
            } else {
                return ['error' => $result['error']];
            }
            
        } catch (\Exception $e) {
            return ['error' => 'Gateway error: ' . $e->getMessage()];
        }
    }
}
