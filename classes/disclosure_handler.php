<?php

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
            if ($quiz_settings['show_countdown']) {
                $html .= '<li>' . get_string('ai_disclosure_detail_timer', 'local_trustgrade', $quiz_settings['time_per_question']) . '</li>';
            }
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
}
