<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Debug cache class for storing AI API responses with Gateway support
 */
class debug_cache {
    
    /**
     * Save API request and response for debugging
     * 
     * @param string $request_type Type of request (check_instructions, generate_questions)
     * @param array $request_data The request data sent to AI
     * @param string $raw_response Raw response from AI API
     * @param array $parsed_response Parsed response data
     * @param int $cmid Course module ID (optional)
     * @return bool Success status
     */
    public static function save_debug_data($request_type, $request_data, $raw_response, $parsed_response = null, $cmid = 0) {
        global $DB, $USER;
        
        // Only save if debug mode is enabled
        if (!get_config('local_trustgrade', 'debug_mode')) {
            return false;
        }
        
        try {
            $record = new \stdClass();
            $record->userid = $USER->id ?? 0;
            $record->cmid = $cmid;
            $record->request_type = $request_type;
            $record->request_data = json_encode($request_data);
            $record->raw_response = $raw_response;
            $record->parsed_response = $parsed_response ? json_encode($parsed_response) : null;
            $record->timecreated = time();
            
            $DB->insert_record('local_trustgrade_debug', $record);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to save debug data: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cached response for identical request (if exists)
     * 
     * @param string $request_type Type of request
     * @param array $request_data The request data
     * @param int $max_age Maximum age of cache in seconds (default: 24 hours)
     * @return array|null Cached response or null if not found
     */
    public static function get_cached_response($request_type, $request_data, $max_age = 86400) {
        global $DB;
        
        // Only use cache if debug mode is enabled
        if (!get_config('local_trustgrade', 'debug_mode')) {
            return null;
        }
        
        try {
            $request_json = json_encode($request_data);
            
            // Look for identical request within the specified time frame
            $sql = "SELECT * FROM {local_trustgrade_debug} 
                    WHERE request_type = ? 
                    AND request_data = ? 
                    AND timecreated > ? 
                    AND parsed_response IS NOT NULL
                    ORDER BY timecreated DESC 
                    LIMIT 1";
            
            $params = [$request_type, $request_json, time() - $max_age];
            $record = $DB->get_record_sql($sql, $params);
            
            if ($record && $record->parsed_response) {
                $cached_response = json_decode($record->parsed_response, true);
                if ($cached_response) {
                    // Add cache metadata
                    $cached_response['from_cache'] = true;
                    $cached_response['cache_time'] = $record->timecreated;
                    $cached_response['cache_age'] = time() - $record->timecreated;
                    $cached_response['cache_source'] = 'debug_cache';
                    
                    return $cached_response;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('Failed to get cached response: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if a similar request exists in cache
     * 
     * @param string $request_type Type of request
     * @param array $request_data The request data
     * @param int $max_age Maximum age of cache in seconds
     * @return bool True if cache exists
     */
    public static function has_cached_response($request_type, $request_data, $max_age = 86400) {
        return self::get_cached_response($request_type, $request_data, $max_age) !== null;
    }
    
    /**
     * Clean old debug records (older than specified days)
     * 
     * @param int $days Number of days to keep (default: 30)
     * @return bool Success status
     */
    public static function cleanup_old_records($days = 30) {
        global $DB;
        
        try {
            $cutoff_time = time() - ($days * 24 * 60 * 60);
            $deleted = $DB->delete_records_select('local_trustgrade_debug', 'timecreated < ?', [$cutoff_time]);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to cleanup debug records: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear cache for specific request type
     * 
     * @param string $request_type Type of request to clear
     * @return bool Success status
     */
    public static function clear_cache_by_type($request_type) {
        global $DB;
        
        try {
            $DB->delete_records('local_trustgrade_debug', ['request_type' => $request_type]);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear cache by type: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get debug statistics with cache information
     * 
     * @return array Statistics about cached requests
     */
    public static function get_debug_stats() {
        global $DB;
        
        try {
            $stats = [];
            
            // Total records
            $stats['total_records'] = $DB->count_records('local_trustgrade_debug');
            
            // Records by type
            $sql = "SELECT request_type, COUNT(*) as count 
                    FROM {local_trustgrade_debug} 
                    GROUP BY request_type";
            $type_counts = $DB->get_records_sql($sql);
            $stats['by_type'] = [];
            foreach ($type_counts as $record) {
                $stats['by_type'][$record->request_type] = $record->count;
            }
            
            // Records in last 24 hours
            $stats['last_24h'] = $DB->count_records_select('local_trustgrade_debug', 
                'timecreated > ?', [time() - (24 * 60 * 60)]);
            
            // Cache hit potential (records that could serve as cache)
            $stats['cache_potential'] = $DB->count_records_select('local_trustgrade_debug', 
                'parsed_response IS NOT NULL AND timecreated > ?', [time() - (24 * 60 * 60)]);
            
            // Average response time (if available)
            $sql = "SELECT AVG(CAST(JSON_EXTRACT(raw_response, '$.response_time') AS DECIMAL)) as avg_time
                    FROM {local_trustgrade_debug} 
                    WHERE timecreated > ? AND raw_response LIKE '%response_time%'";
            $avg_time = $DB->get_field_sql($sql, [time() - (24 * 60 * 60)]);
            if ($avg_time) {
                $stats['avg_response_time'] = round($avg_time, 2);
            }
            
            // Cache efficiency
            if ($stats['total_records'] > 0) {
                $stats['cache_efficiency'] = round(($stats['cache_potential'] / $stats['total_records']) * 100, 1);
            } else {
                $stats['cache_efficiency'] = 0;
            }
            
            return $stats;
        } catch (\Exception $e) {
            error_log('Failed to get debug stats: ' . $e->getMessage());
            return ['error' => 'Failed to retrieve statistics'];
        }
    }
    
    /**
     * Get recent cache activity
     * 
     * @param int $limit Number of recent records to return
     * @return array Recent cache records
     */
    public static function get_recent_activity($limit = 10) {
        global $DB;
        
        if (!get_config('local_trustgrade', 'debug_mode')) {
            return [];
        }
        
        try {
            $sql = "SELECT id, request_type, timecreated, 
                           CASE WHEN parsed_response IS NOT NULL THEN 1 ELSE 0 END as cacheable
                    FROM {local_trustgrade_debug} 
                    ORDER BY timecreated DESC 
                    LIMIT ?";
            
            $records = $DB->get_records_sql($sql, [$limit]);
            
            $activity = [];
            foreach ($records as $record) {
                $activity[] = [
                    'id' => $record->id,
                    'type' => $record->request_type,
                    'time' => $record->timecreated,
                    'time_ago' => self::time_ago($record->timecreated),
                    'cacheable' => (bool)$record->cacheable
                ];
            }
            
            return $activity;
        } catch (\Exception $e) {
            error_log('Failed to get recent activity: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper function to format time ago
     * 
     * @param int $timestamp Unix timestamp
     * @return string Human readable time ago
     */
    private static function time_ago($timestamp) {
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return $time_diff . ' seconds ago';
        } elseif ($time_diff < 3600) {
            return floor($time_diff / 60) . ' minutes ago';
        } elseif ($time_diff < 86400) {
            return floor($time_diff / 3600) . ' hours ago';
        } else {
            return floor($time_diff / 86400) . ' days ago';
        }
    }
}
