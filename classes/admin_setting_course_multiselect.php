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
 * Custom admin setting for multi-selecting courses.
 *
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trustgrade;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting class for selecting multiple courses
 */
class admin_setting_course_multiselect extends \admin_setting {
    
    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible name
     * @param string $description Description
     * @param mixed $defaultsetting Default setting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        $result = $this->config_read($this->name);
        if (is_null($result)) {
            return null;
        }
        
        // Return as array if stored as JSON
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return $result;
    }

    /**
     * Save the setting
     *
     * @param array $data Form data
     * @return string Empty string on success, error message otherwise
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }
        
        // Store as JSON
        $result = $this->config_write($this->name, json_encode(array_values($data)));
        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Return an HTML string for the setting
     *
     * @param array $data Form data
     * @param string $query Query string for searching courses
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        global $DB, $OUTPUT;

        // Check if course-specific mode is enabled
        $course_specific = get_config('local_trustgrade', 'course_specific');
        
        // If not enabled, show disabled state
        if (!$course_specific) {
            $html = \html_writer::div(
                get_string('enable_course_specific_first', 'local_trustgrade'),
                'alert alert-info'
            );
            return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
        }

        // Get all courses (excluding site course)
        $courses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id, fullname, shortname');
        
        // Get currently selected courses
        $selected_courses = $this->get_setting();
        if (!is_array($selected_courses)) {
            $selected_courses = [];
        }

        // Build the multi-select HTML
        $html = \html_writer::start_tag('div', ['class' => 'form-multicheckbox']);
        
        if (empty($courses)) {
            $html .= \html_writer::div(get_string('no_courses_available', 'local_trustgrade'), 'alert alert-warning');
        } else {
            // Add "Select All" / "Deselect All" buttons
            $html .= \html_writer::div(
                \html_writer::tag('button', get_string('selectall'), [
                    'type' => 'button',
                    'class' => 'btn btn-sm btn-secondary mr-2',
                    'onclick' => 'document.querySelectorAll(\'input[name="' . $this->get_full_name() . '[]\"]").forEach(cb => cb.checked = true);'
                ]) .
                \html_writer::tag('button', get_string('deselectall'), [
                    'type' => 'button',
                    'class' => 'btn btn-sm btn-secondary',
                    'onclick' => 'document.querySelectorAll(\'input[name="' . $this->get_full_name() . '[]\"]").forEach(cb => cb.checked = false);'
                ]),
                'mb-2'
            );
            
            // Add search box
            $html .= \html_writer::div(
                \html_writer::tag('input', '', [
                    'type' => 'text',
                    'class' => 'form-control mb-2',
                    'placeholder' => get_string('search_courses', 'local_trustgrade'),
                    'onkeyup' => 'this.nextElementSibling.querySelectorAll(\'label\').forEach(label => {
                        const text = label.textContent.toLowerCase();
                        const search = this.value.toLowerCase();
                        label.style.display = text.includes(search) ? \'block\' : \'none\';
                    });'
                ]),
                'mb-2'
            );
            
            // Container for checkboxes
            $html .= \html_writer::start_tag('div', [
                'class' => 'course-checkbox-container',
                'style' => 'max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;'
            ]);
            
            foreach ($courses as $course) {
                $checked = in_array($course->id, $selected_courses);
                $checkbox_id = 'course_' . $course->id;
                
                $html .= \html_writer::div(
                    \html_writer::checkbox(
                        $this->get_full_name() . '[]',
                        $course->id,
                        $checked,
                        $course->fullname . ' (' . $course->shortname . ')',
                        ['id' => $checkbox_id]
                    ),
                    'form-check'
                );
            }
            
            $html .= \html_writer::end_tag('div');
        }
        
        $html .= \html_writer::end_tag('div');

        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
    }
}
