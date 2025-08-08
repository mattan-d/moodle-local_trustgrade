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
     * @return array Response from Gateway with structured format or error
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
                // Handle new structured response format
                $response_data = $result['data'];
                
                // Validate the expected structure
                if (isset($response_data['table']) && isset($response_data['EvaluationText']) && isset($response_data['ImprovedAssignment'])) {
                    return [
                        'success' => true,
                        'table' => $response_data['table'],
                        'evaluation_text' => $response_data['EvaluationText'],
                        'improved_assignment' => $response_data['ImprovedAssignment']
                    ];
                } else {
                    // Fallback for old format or malformed response
                    $recommendation = $response_data['recommendation'] ?? $response_data['content'] ?? 'No recommendation provided';
                    return [
                        'success' => true,
                        'recommendation' => $recommendation
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
