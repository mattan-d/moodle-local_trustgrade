<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_assign\event\submission_created',
        'callback' => '\local_trustgrade\observer::submission_created',
    ],
    [
        'eventname' => '\mod_assign\event\submission_updated',
        'callback' => '\local_trustgrade\observer::submission_updated',
    ],
];
