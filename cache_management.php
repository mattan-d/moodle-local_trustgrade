<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_TEXT);

$PAGE->set_url('/local/trustgrade/cache_management.php');
$PAGE->set_title('TrustGrade Cache Management');
$PAGE->set_heading('TrustGrade Cache Management');

// Handle actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'clear_all':
            \local_trustgrade\debug_cache::cleanup_old_records(0); // Clear all
            \core\notification::add('All cache cleared successfully', \core\notification::SUCCESS);
            break;
            
        case 'clear_instructions':
            \local_trustgrade\debug_cache::clear_cache_by_type('check_instructions');
            \core\notification::add('Instruction analysis cache cleared', \core\notification::SUCCESS);
            break;
            
        case 'clear_questions':
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_questions');
            \core\notification::add('Question generation cache cleared', \core\notification::SUCCESS);
            break;
            
        case 'clear_submissions':
            \local_trustgrade\debug_cache::clear_cache_by_type('generate_submission_questions');
            \core\notification::add('Submission questions cache cleared', \core\notification::SUCCESS);
            break;
            
        case 'cleanup_old':
            \local_trustgrade\debug_cache::cleanup_old_records(7); // Keep only 7 days
            \core\notification::add('Old cache records cleaned up', \core\notification::SUCCESS);
            break;
    }
    
    redirect($PAGE->url);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', 'TrustGrade Cache Management');

// Check if debug mode is enabled
$debug_mode = get_config('local_trustgrade', 'debug_mode');

if (!$debug_mode) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-info-circle']) . 
        ' Debug mode is currently disabled. Enable debug mode in plugin settings to use caching features.',
        'alert alert-info'
    );
} else {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-check-circle']) . 
        ' Debug mode is enabled. Gateway responses are being cached to improve performance.',
        'alert alert-success'
    );
}

// Get cache statistics
try {
    $gateway = new \local_trustgrade\gateway_client();
    $stats = $gateway->getCacheStats();
    
    if ($stats['cache_enabled']) {
        echo html_writer::tag('h3', 'Cache Statistics');
        
        echo html_writer::start_div('row');
        
        // Total records card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['total_records'] ?? 0, ['class' => 'text-primary']);
        echo html_writer::tag('p', 'Total Cached Responses', ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Last 24h card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['last_24h'] ?? 0, ['class' => 'text-info']);
        echo html_writer::tag('p', 'Last 24 Hours', ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Cache potential card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', $stats['cache_potential'] ?? 0, ['class' => 'text-success']);
        echo html_writer::tag('p', 'Cacheable Responses', ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Cache efficiency card
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h4', ($stats['cache_efficiency'] ?? 0) . '%', ['class' => 'text-warning']);
        echo html_writer::tag('p', 'Cache Efficiency', ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        echo html_writer::end_div();
        
        // Cache by type
        if (isset($stats['by_type']) && !empty($stats['by_type'])) {
            echo html_writer::tag('h4', 'Cache by Request Type');
            echo html_writer::start_tag('table', ['class' => 'table table-striped']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', 'Request Type');
            echo html_writer::tag('th', 'Cached Responses');
            echo html_writer::tag('th', 'Actions');
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            
            foreach ($stats['by_type'] as $type => $count) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', ucfirst(str_replace('_', ' ', $type)));
                echo html_writer::tag('td', $count);
                echo html_writer::start_tag('td');
                
                $clear_url = new moodle_url($PAGE->url, [
                    'action' => 'clear_' . str_replace('generate_', '', str_replace('_questions', '', $type)),
                    'sesskey' => sesskey()
                ]);
                echo html_writer::link($clear_url, 'Clear', ['class' => 'btn btn-sm btn-outline-danger']);
                
                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');
            }
            
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }
        
        // Recent activity
        $recent_activity = \local_trustgrade\debug_cache::get_recent_activity(10);
        if (!empty($recent_activity)) {
            echo html_writer::tag('h4', 'Recent Cache Activity');
            echo html_writer::start_tag('table', ['class' => 'table table-sm']);
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', 'Type');
            echo html_writer::tag('th', 'Time');
            echo html_writer::tag('th', 'Status');
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');
            
            foreach ($recent_activity as $activity) {
                echo html_writer::start_tag('tr');
                echo html_writer::tag('td', ucfirst(str_replace('_', ' ', $activity['type'])));
                echo html_writer::tag('td', $activity['time_ago']);
                $status = $activity['cacheable'] ? 
                    '<span class="badge badge-success">Cached</span>' : 
                    '<span class="badge badge-secondary">Not Cached</span>';
                echo html_writer::tag('td', $status);
                echo html_writer::end_tag('tr');
            }
            
            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        }
    }
    
} catch (Exception $e) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . 
        ' Error loading cache statistics: ' . $e->getMessage(),
        'alert alert-danger'
    );
}

// Cache management actions
echo html_writer::tag('h3', 'Cache Management Actions');

echo html_writer::start_div('row');

// Clear all cache
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', 'Clear All Cache', ['class' => 'card-title']);
echo html_writer::tag('p', 'Remove all cached Gateway responses. This will force fresh requests to the Gateway.', ['class' => 'card-text']);
$clear_all_url = new moodle_url($PAGE->url, ['action' => 'clear_all', 'sesskey' => sesskey()]);
echo html_writer::link($clear_all_url, 'Clear All Cache', [
    'class' => 'btn btn-danger',
    'onclick' => 'return confirm("Are you sure you want to clear all cache?")'
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Cleanup old records
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', 'Cleanup Old Records', ['class' => 'card-title']);
echo html_writer::tag('p', 'Remove cache records older than 7 days to free up database space.', ['class' => 'card-text']);
$cleanup_url = new moodle_url($PAGE->url, ['action' => 'cleanup_old', 'sesskey' => sesskey()]);
echo html_writer::link($cleanup_url, 'Cleanup Old Records', ['class' => 'btn btn-warning']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

// Links
echo html_writer::tag('h3', 'Related Pages');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::link(
    new moodle_url('/admin/settings.php', ['section' => 'local_trustgrade']),
    'Plugin Settings'
));
echo html_writer::tag('li', html_writer::link(
    new moodle_url('/local/trustgrade/gateway_test.php'),
    'Gateway Connection Test'
));
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();
