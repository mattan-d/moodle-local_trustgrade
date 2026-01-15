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
        global $DB, $OUTPUT, $PAGE;

        $course_specific = get_config('local_trustgrade', 'course_specific');
        
        if (!$course_specific && $course_specific !== false) {
            $html = \html_writer::div(
                get_string('enable_course_specific_first', 'local_trustgrade'),
                'alert alert-info'
            );
            $html .= \html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $this->get_full_name() . '[]',
                'value' => '',
            ]);
            return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
        }

        // Get all courses (excluding site course)
        $courses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id, fullname, shortname');
        
        // Get currently selected courses
        $selected_courses = $this->get_setting();
        if (!is_array($selected_courses)) {
            $selected_courses = [];
        }

        $html = \html_writer::start_tag('div', ['class' => 'trustgrade-course-selector']);
        
        if (empty($courses)) {
            $html .= $OUTPUT->notification(
                get_string('no_courses_available', 'local_trustgrade'),
                \core\output\notification::NOTIFY_WARNING
            );
        } else {
            // Statistics bar
            $total = count($courses);
            $selected = count($selected_courses);
            $html .= \html_writer::div(
                \html_writer::tag('span', 
                    get_string('courses_selected', 'local_trustgrade', ['selected' => $selected, 'total' => $total]),
                    ['class' => 'badge badge-info']
                ),
                'mb-2'
            );
            
            // Action buttons with better styling
            $html .= \html_writer::start_tag('div', ['class' => 'btn-group mb-3', 'role' => 'group']);
            $html .= \html_writer::tag('button', 
                $OUTPUT->pix_icon('t/check', '') . ' ' . get_string('selectall'),
                [
                    'type' => 'button',
                    'class' => 'btn btn-secondary btn-sm',
                    'id' => 'selectall_courses',
                ]
            );
            $html .= \html_writer::tag('button',
                $OUTPUT->pix_icon('t/delete', '') . ' ' . get_string('deselectall'),
                [
                    'type' => 'button',
                    'class' => 'btn btn-secondary btn-sm',
                    'id' => 'deselectall_courses',
                ]
            );
            $html .= \html_writer::end_tag('div');
            
            // Search box with icon
            $search_id = 'course_search_' . uniqid();
            $container_id = 'course_container_' . uniqid();
            
            $html .= \html_writer::start_tag('div', ['class' => 'form-group']);
            $html .= \html_writer::tag('label', 
                $OUTPUT->pix_icon('i/search', '') . ' ' . get_string('search'),
                ['for' => $search_id, 'class' => 'font-weight-bold']
            );
            $html .= \html_writer::tag('input', '', [
                'type' => 'text',
                'id' => $search_id,
                'class' => 'form-control',
                'placeholder' => get_string('search_courses', 'local_trustgrade'),
            ]);
            $html .= \html_writer::end_tag('div');
            
            // Scrollable container with better styling
            $html .= \html_writer::start_tag('div', [
                'id' => $container_id,
                'class' => 'border rounded p-3 bg-light',
                'style' => 'max-height: 450px; overflow-y: auto;'
            ]);
            
            // Course checkboxes with improved layout
            foreach ($courses as $course) {
                $checked = in_array($course->id, $selected_courses);
                $checkbox_id = 'course_' . $course->id;
                
                $html .= \html_writer::start_tag('div', [
                    'class' => 'custom-control custom-checkbox course-item mb-2',
                    'data-coursename' => strtolower($course->fullname . ' ' . $course->shortname)
                ]);
                
                $html .= \html_writer::empty_tag('input', [
                    'type' => 'checkbox',
                    'class' => 'custom-control-input course-checkbox',
                    'id' => $checkbox_id,
                    'name' => $this->get_full_name() . '[]',
                    'value' => $course->id,
                    'checked' => $checked ? 'checked' : null,
                ]);
                
                $html .= \html_writer::tag('label',
                    \html_writer::tag('strong', $course->fullname) . 
                    \html_writer::tag('small', ' (' . $course->shortname . ')', ['class' => 'text-muted ml-1']),
                    ['class' => 'custom-control-label', 'for' => $checkbox_id]
                );
                
                $html .= \html_writer::end_tag('div');
            }
            
            $html .= \html_writer::end_tag('div');
            
            $html .= \html_writer::script("
                (function() {
                    function init() {
                        var searchInput = document.getElementById('{$search_id}');
                        var selectAllBtn = document.getElementById('selectall_courses');
                        var deselectAllBtn = document.getElementById('deselectall_courses');
                        var courseItems = document.querySelectorAll('.course-item');
                        var checkboxes = document.querySelectorAll('.course-checkbox');
                        var badge = document.querySelector('.trustgrade-course-selector .badge');
                        
                        // Search functionality
                        if (searchInput) {
                            searchInput.addEventListener('keyup', function() {
                                var searchTerm = this.value.toLowerCase();
                                courseItems.forEach(function(item) {
                                    var courseName = item.getAttribute('data-coursename');
                                    if (courseName && courseName.indexOf(searchTerm) !== -1) {
                                        item.style.display = '';
                                    } else {
                                        item.style.display = 'none';
                                    }
                                });
                                updateStats();
                            });
                        }
                        
                        // Select all visible courses
                        if (selectAllBtn) {
                            selectAllBtn.addEventListener('click', function() {
                                courseItems.forEach(function(item) {
                                    if (item.style.display !== 'none') {
                                        var checkbox = item.querySelector('.course-checkbox');
                                        if (checkbox) checkbox.checked = true;
                                    }
                                });
                                updateStats();
                            });
                        }
                        
                        // Deselect all visible courses
                        if (deselectAllBtn) {
                            deselectAllBtn.addEventListener('click', function() {
                                courseItems.forEach(function(item) {
                                    if (item.style.display !== 'none') {
                                        var checkbox = item.querySelector('.course-checkbox');
                                        if (checkbox) checkbox.checked = false;
                                    }
                                });
                                updateStats();
                            });
                        }
                        
                        // Update statistics when checkboxes change
                        checkboxes.forEach(function(checkbox) {
                            checkbox.addEventListener('change', updateStats);
                        });
                        
                        function updateStats() {
                            var total = checkboxes.length;
                            var selected = document.querySelectorAll('.course-checkbox:checked').length;
                            if (badge) {
                                badge.textContent = selected + ' / ' + total + ' " . get_string('courses_selected_short', 'local_trustgrade') . "';
                            }
                        }
                        
                        // Initial stats update
                        updateStats();
                    }
                    
                    // Run when DOM is ready
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', init);
                    } else {
                        init();
                    }
                })();
            ");
        }
        
        $html .= \html_writer::end_tag('div');

        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
    }
}
