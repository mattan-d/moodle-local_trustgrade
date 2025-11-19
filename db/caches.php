<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Cache definitions for TrustGrade plugin
 *
 * @package    local_trustgrade
 * @copyright  2025 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Session cache for quiz redirect flags
    'quiz_redirect' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10,
    ],
];
