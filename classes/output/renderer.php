<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for TrustGrade plugin
 */
class renderer extends \plugin_renderer_base {
    
    /**
     * Render the AI disclosure message
     * 
     * @param int $cmid Course module ID
     * @return string HTML output
     */
    public function render_ai_disclosure($cmid) {
        return \local_trustgrade\disclosure_handler::get_disclosure_html($cmid);
    }
    
    /**
     * Render disclosure for assignment view page
     * 
     * @param int $cmid Course module ID
     * @return string HTML output
     */
    public function render_assignment_disclosure($cmid) {
        if (!\local_trustgrade\disclosure_handler::should_show_disclosure($cmid)) {
            return '';
        }
        
        $html = '';
        $html .= '<div class="ai-assignment-disclosure">';
        $html .= $this->render_ai_disclosure($cmid);
        $html .= '</div>';
        
        // Add JavaScript to position the disclosure appropriately
        $html .= '<script type="text/javascript">';
        $html .= \local_trustgrade\disclosure_handler::get_injection_javascript($cmid);
        $html .= '</script>';
        
        return $html;
    }
}
