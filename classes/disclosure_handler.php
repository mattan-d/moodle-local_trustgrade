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
 * Disclosure handler for AI-generated content notifications.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

class disclosure_handler {

    /**
     * Check if disclosure should be shown for this assignment
     * 
     * @param int $cmid Course module ID
     * @return bool True if disclosure should be shown
     */
    public static function should_show_disclosure($cmid) {
        // Check if disclosure is enabled in admin settings
        if (!get_config('local_trustgrade', 'show_disclosure')) {
            return false;
        }
        
        // Get quiz settings to check if AI features are enabled
        $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cmid);
        
        // Show disclosure if submission questions are enabled
        return $quiz_settings['submission_questions'] > 0;
    }

    /**
     * Get the disclosure message HTML
     * 
     * @param int $cmid Course module ID
     * @return string HTML for disclosure message
     */
    public static function get_disclosure_html($cmid) {
        if (!self::should_show_disclosure($cmid)) {
            return '';
        }
        
        $quiz_settings = \local_trustgrade\quiz_settings::get_settings($cmid);
        
        // Check for custom disclosure message
        $custom_message = get_config('local_trustgrade', 'custom_disclosure_message');
        
        if (!empty($custom_message)) {
            $disclosure_content = $custom_message;
        } else {
            $disclosure_content = get_string('ai_disclosure_message', 'local_trustgrade');
        }
        
        $html = '';
        $html .= '<div class="ai-disclosure-container">';
        $html .= '<h5><i class="fa fa-info-circle"></i> ' . get_string('ai_disclosure_title', 'local_trustgrade') . '</h5>';
        $html .= '<div class="ai-disclosure-content">' . $disclosure_content . '</div>';
        
        // Only show details if using default message
        if (empty($custom_message)) {
            // Add collapsible details section
            $details_id = 'ai-disclosure-details-' . $cmid . '-' . time();
        
            $html .= '<div class="ai-disclosure-details">';
            $html .= '<a href="#" class="ai-disclosure-toggle" data-target="#' . $details_id . '">';
            $html .= get_string('ai_disclosure_details_toggle', 'local_trustgrade');
            $html .= ' <i class="fa fa-chevron-down"></i></a>';
        
            $html .= '<div id="' . $details_id . '" style="display: none;">';
            $html .= '<ul>';
            $html .= '<li>' . get_string('ai_disclosure_detail_analysis', 'local_trustgrade') . '</li>';
            $html .= '<li>' . get_string('ai_disclosure_detail_questions', 'local_trustgrade', $quiz_settings['submission_questions']) . '</li>';
            $html .= '<li>' . get_string('ai_disclosure_detail_quiz', 'local_trustgrade', $quiz_settings['total_quiz_questions']) . '</li>';
            $html .= '<li>' . get_string('ai_disclosure_detail_timer', 'local_trustgrade', $quiz_settings['time_per_question']) . '</li>';
            $html .= '<li>' . get_string('ai_disclosure_detail_privacy', 'local_trustgrade') . '</li>';
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Initialize disclosure on assignment submission pages
     * 
     * @param int $cmid Course module ID
     * @return void
     */
    public static function init_disclosure($cmid) {
        global $PAGE;
        
        if (!self::should_show_disclosure($cmid)) {
            return;
        }
        
        // Load CSS
        $PAGE->requires->css('/local/trustgrade/disclosure_styles.css');
        
        // Get disclosure HTML
        $disclosure_html = self::get_disclosure_html($cmid);
        
        if (!empty($disclosure_html)) {
            // Load and initialize JavaScript
            $PAGE->requires->js_call_amd('local_trustgrade/disclosure', 'init', [$cmid, $disclosure_html]);
        }
    }

    /**
     * Get JavaScript code for injecting disclosure into assignment page
     * 
     * @param int $cmid Course module ID
     * @return string JavaScript code
     */
    public static function get_injection_javascript($cmid) {
        return "
            (function() {
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initDisclosure);
                } else {
                    initDisclosure();
                }
                
                function initDisclosure() {
                    var submissionForm = document.querySelector('.mform');
                    var disclosureElement = document.querySelector('.ai-assignment-disclosure');
                    
                    if (submissionForm && disclosureElement) {
                        // Insert disclosure before the submission form
                        submissionForm.parentNode.insertBefore(disclosureElement, submissionForm);
                    }
                }
            })();
        ";
    }
}
