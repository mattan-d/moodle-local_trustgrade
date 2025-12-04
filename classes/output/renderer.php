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
 * Renderer for TrustGrade plugin.
 *
 * @package    local_trustgrade
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
