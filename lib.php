<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * @deprecated since Moodle 4.4 - Use \core_course\hook\after_form_definition instead
 * Add TrustGrade elements to assignment form
 */
function local_trustgrade_coursemodule_standard_elements($formwrapper, $mform) {
    debugging('local_trustgrade_coursemodule_standard_elements() is deprecated. Use \core_course\hook\after_form_definition hook instead.', DEBUG_DEVELOPER);
    
    // For backwards compatibility, create a mock hook and call the new callback
    if (class_exists('\core_course\hook\after_form_definition')) {
        $hook = new \core_course\hook\after_form_definition($formwrapper, $mform);
        \local_trustgrade\hook\callbacks::after_form_definition($hook);
    }
}

/**
 * @deprecated since Moodle 4.4 - Use \core\hook\output\before_standard_head_html_generation instead
 * Hook called when assignment page is viewed
 */
function local_trustgrade_before_standard_html_head() {
    debugging('local_trustgrade_before_standard_html_head() is deprecated. Use \core\hook\output\before_standard_head_html_generation hook instead.', DEBUG_DEVELOPER);
    
    // For backwards compatibility, call the new callback if available
    if (class_exists('\core\hook\output\before_standard_head_html_generation')) {
        // Note: We can't create a proper hook instance here as it requires renderer context
        // The legacy functionality will still work but should be migrated
        global $OUTPUT;
        if (method_exists($OUTPUT, 'get_renderer')) {
            $hook = new \core\hook\output\before_standard_head_html_generation($OUTPUT, null);
            \local_trustgrade\hook\callbacks::before_standard_head_html_generation($hook);
        }
    }
}

/**
 * @deprecated since Moodle 4.4 - Use \core_course\hook\after_form_submission instead
 * Hook called after assignment form is submitted
 */
function local_trustgrade_coursemodule_edit_post_actions($data, $course) {
    debugging('local_trustgrade_coursemodule_edit_post_actions() is deprecated. Use \core_course\hook\after_form_submission hook instead.', DEBUG_DEVELOPER);
    
    // For backwards compatibility, create a mock hook and call the new callback
    if (class_exists('\core_course\hook\after_form_submission')) {
        $hook = new \core_course\hook\after_form_submission($data, $course);
        \local_trustgrade\hook\callbacks::after_form_submission($hook);
    }
    
    return $data;
}
