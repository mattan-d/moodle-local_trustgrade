<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$PAGE->set_url('/local/trustgrade/gateway_test.php');
$PAGE->set_title('AI Gateway Test');
$PAGE->set_heading('AI Gateway Connection Test');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'AI Gateway Connection Test');

// Test Gateway connection
try {
    $gateway = new \local_trustgrade\gateway_client();
    $result = $gateway->testConnection();
    
    if ($result['success']) {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-check-circle']) . ' ' . $result['message'],
            'alert alert-success'
        );
        
        echo html_writer::tag('h3', 'Gateway Configuration');
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', 'Endpoint: ' . get_config('local_trustgrade', 'gateway_endpoint'));
        echo html_writer::tag('li', 'Token: ' . (get_config('local_trustgrade', 'gateway_token') ? 'Configured' : 'Not configured'));
        echo html_writer::end_tag('ul');
        
        echo html_writer::tag('p', 
            html_writer::tag('strong', 'Note: ') . 
            'OpenRouter API Key and Model are configured in the Gateway server, not in the plugin.'
        );
        
    } else {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' Connection failed: ' . $result['error'],
            'alert alert-danger'
        );
        
        echo html_writer::tag('h3', 'Troubleshooting');
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', 'Verify the Gateway endpoint URL is correct and accessible');
        echo html_writer::tag('li', 'Check that the Gateway authentication token is valid');
        echo html_writer::tag('li', 'Ensure the Gateway server is running and responding');
        echo html_writer::tag('li', 'Verify the Gateway has a valid OpenRouter API key configured');
        echo html_writer::end_tag('ul');
    }
    
} catch (Exception $e) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-times-circle']) . ' Configuration error: ' . $e->getMessage(),
        'alert alert-danger'
    );
    
    echo html_writer::tag('h3', 'Configuration Required');
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', 'Configure the Gateway endpoint URL in plugin settings');
    echo html_writer::tag('li', 'Set the Gateway authentication token (use "Demo123" for testing)');
    echo html_writer::tag('li', 'Ensure the Gateway server has OpenRouter API key configured');
    echo html_writer::end_tag('ul');
}

echo html_writer::tag('p', 
    html_writer::link(
        new moodle_url('/admin/settings.php', ['section' => 'local_trustgrade']),
        'Configure Gateway Settings',
        ['class' => 'btn btn-primary']
    )
);

echo $OUTPUT->footer();
