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
 * Course availability checker for TrustGrade plugin.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

/**
 * Course availability manager class
 */
class course_availability {
    
    /**
     * Check if TrustGrade is available for a specific course
     * 
     * @param int $courseid Course ID
     * @return bool True if available, false otherwise
     */
    public static function is_available_for_course($courseid) {
        // First check if plugin is globally enabled
        if (!get_config('local_trustgrade', 'plugin_enabled')) {
            return false;
        }
        
        // Check if course-specific mode is enabled
        $course_specific = get_config('local_trustgrade', 'course_specific');
        
        // If course-specific mode is disabled, plugin is available for all courses
        if (!$course_specific) {
            return true;
        }
        
        // Get list of enabled courses
        $enabled_courses_json = get_config('local_trustgrade', 'enabled_courses');
        $enabled_courses = json_decode($enabled_courses_json, true);
        
        if (!is_array($enabled_courses)) {
            $enabled_courses = [];
        }
        
        // Check if current course is in the enabled list
        return in_array($courseid, $enabled_courses);
    }
    
    /**
     * Check if TrustGrade is available for a course module
     * 
     * @param int $cmid Course module ID
     * @return bool True if available, false otherwise
     */
    public static function is_available_for_module($cmid) {
        global $DB;
        
        // Get course ID from course module
        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'course', IGNORE_MISSING);
        
        if (!$cm) {
            return false;
        }
        
        return self::is_available_for_course($cm->course);
    }
    
    /**
     * Get list of all enabled courses
     * 
     * @return array Array of course IDs
     */
    public static function get_enabled_courses() {
        $enabled_courses_json = get_config('local_trustgrade', 'enabled_courses');
        $enabled_courses = json_decode($enabled_courses_json, true);
        
        if (!is_array($enabled_courses)) {
            return [];
        }
        
        return $enabled_courses;
    }
}
