<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_course\hook\after_form_definition::class,
        'callback' => [\local_trustgrade\hook\callbacks::class, 'after_form_definition'],
        'priority' => 500,
    ],
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [\local_trustgrade\hook\callbacks::class, 'before_standard_head_html_generation'],
        'priority' => 500,
    ],
    [
        'hook' => \core_course\hook\after_form_submission::class,
        'callback' => [\local_trustgrade\hook\callbacks::class, 'after_form_submission'],
        'priority' => 500,
    ],
];
