define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/config',
    'core/str'
], function($, Ajax, Notification, M, Str) {

    var cmid = 0;

    /**
     * Initialize the TrustGrade functionality
     * @param {int} courseModuleId Course module ID
     */
    function init(courseModuleId) {
        cmid = courseModuleId || 0;
        
        // Bind event handlers
        bindCheckInstructionsEvent();
        bindGenerateQuestionsEvent();
        bindQuizSettingsEvents();
    }

    /**
     * Bind check instructions button event
     */
    function bindCheckInstructionsEvent() {
        $('#check-instructions-btn').on('click', function() {
            var $button = $(this);
            var $container = $('#ai-recommendation-container');
            var instructions = getInstructionsText();

            if (!instructions.trim()) {
                Notification.addNotification({
                    message: Str.get_string('no_instructions_error', 'local_trustgrade'),
                    type: 'error'
                });
                return;
            }

            // Show loading state
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + 
                Str.get_string('processing', 'local_trustgrade'));
            $container.html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> ' + 
                Str.get_string('processing', 'local_trustgrade') + '</div>');

            // Make AJAX call
            var promise = Ajax.call([{
                methodname: 'local_trustgrade_check_instructions',
                args: {
                    cmid: cmid,
                    instructions: instructions
                }
            }])[0];

            promise.done(function(response) {
                if (response.success) {
                    displayInstructionsResponse(response, $container);
                    
                    // Show cache indicator if applicable
                    if (response.from_cache) {
                        Notification.addNotification({
                            message: Str.get_string('cache_hit', 'local_trustgrade'),
                            type: 'info'
                        });
                    }
                } else {
                    $container.html('<div class="alert alert-danger">' + 
                        '<strong>' + Str.get_string('gateway_error', 'local_trustgrade') + ':</strong> ' + 
                        (response.error || 'Unknown error') + '</div>');
                }
            }).fail(function(ex) {
                $container.html('<div class="alert alert-danger">' + 
                    '<strong>' + Str.get_string('gateway_error', 'local_trustgrade') + ':</strong> ' + 
                    ex.message + '</div>');
                Notification.exception(ex);
            }).always(function() {
                $button.prop('disabled', false).html('<i class="fa fa-check"></i> ' + 
                    Str.get_string('check_instructions', 'local_trustgrade'));
            });
        });
    }

    /**
     * Display the new structured instructions response
     * @param {Object} response The API response
     * @param {jQuery} $container The container to display in
     */
    function displayInstructionsResponse(response, $container) {
        var html = '<div class="trustgrade-instructions-response">';
        
        try {
            // Parse the recommendation if it's a JSON string
            var data = response.recommendation;
            if (typeof data === 'string') {
                data = JSON.parse(data);
            }

            // Display Table Section
            if (data.table && data.table.rows && data.table.rows.length > 0) {
                html += '<div class="criteria-table-section mb-4">';
                html += '<h4 class="mb-3"><i class="fa fa-table"></i> ' + (data.table.title || 'Evaluation Criteria') + '</h4>';
                html += '<div class="table-responsive">';
                html += '<table class="table table-striped table-bordered">';
                html += '<thead class="thead-dark">';
                html += '<tr><th>Criterion</th><th>Status</th><th>Suggestions</th></tr>';
                html += '</thead><tbody>';
                
                data.table.rows.forEach(function(row) {
                    var statusClass = row.Met.toLowerCase() === 'yes' ? 'text-success' : 'text-warning';
                    var statusIcon = row.Met.toLowerCase() === 'yes' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                    
                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(row.Criterion) + '</strong></td>';
                    html += '<td class="' + statusClass + '"><i class="fa ' + statusIcon + '"></i> ' + escapeHtml(row.Met) + '</td>';
                    html += '<td>' + escapeHtml(row.Suggestions) + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div></div>';
            }

            // Display Evaluation Text Section
            if (data.EvaluationText && data.EvaluationText.content) {
                html += '<div class="evaluation-text-section mb-4">';
                html += '<h4 class="mb-3"><i class="fa fa-comments"></i> Evaluation Summary</h4>';
                html += '<div class="alert alert-info">';
                html += '<p>' + escapeHtml(data.EvaluationText.content).replace(/\n/g, '<br>') + '</p>';
                html += '</div></div>';
            }

            // Display Improved Assignment Section
            if (data.ImprovedAssignment && data.ImprovedAssignment.content) {
                html += '<div class="improved-assignment-section mb-4">';
                html += '<h4 class="mb-3"><i class="fa fa-lightbulb-o"></i> Suggested Improved Assignment</h4>';
                html += '<div class="alert alert-success">';
                html += '<div class="improved-assignment-content">';
                html += '<pre class="improved-assignment-text">' + escapeHtml(data.ImprovedAssignment.content) + '</pre>';
                html += '</div>';
                html += '<div class="mt-3">';
                html += '<button type="button" class="btn btn-sm btn-outline-primary copy-improved-text" data-content="' + 
                    escapeHtml(data.ImprovedAssignment.content) + '">';
                html += '<i class="fa fa-copy"></i> Copy Improved Text</button>';
                html += '</div>';
                html += '</div></div>';
            }

        } catch (e) {
            // Fallback to old format if JSON parsing fails
            html += '<div class="alert alert-info">';
            html += '<h4><i class="fa fa-lightbulb-o"></i> ' + Str.get_string('ai_recommendation', 'local_trustgrade') + '</h4>';
            html += '<div class="recommendation-content">' + escapeHtml(response.recommendation).replace(/\n/g, '<br>') + '</div>';
            html += '</div>';
        }

        html += '</div>';
        $container.html(html);

        // Bind copy button event
        $container.find('.copy-improved-text').on('click', function() {
            var content = $(this).data('content');
            copyToClipboard(content);
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-check"></i> Copied!').addClass('btn-success');
            
            setTimeout(function() {
                $btn.html(originalText).removeClass('btn-success');
            }, 2000);
        });
    }

    /**
     * Copy text to clipboard
     * @param {string} text Text to copy
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
    }

    /**
     * Escape HTML characters
     * @param {string} text Text to escape
     * @return {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Bind generate questions button event
     */
    function bindGenerateQuestionsEvent() {
        $('#generate-questions-btn').on('click', function() {
            var $button = $(this);
            var $container = $('#generated-questions-container');
            var instructions = getInstructionsText();

            if (!instructions.trim()) {
                Notification.addNotification({
                    message: Str.get_string('no_instructions_questions_error', 'local_trustgrade'),
                    type: 'error'
                });
                return;
            }

            // Show loading state
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + 
                Str.get_string('generating_questions', 'local_trustgrade'));
            $container.html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> ' + 
                Str.get_string('generating_questions', 'local_trustgrade') + '</div>');

            // Make AJAX call
            var promise = Ajax.call([{
                methodname: 'local_trustgrade_generate_questions',
                args: {
                    cmid: cmid,
                    instructions: instructions
                }
            }])[0];

            promise.done(function(response) {
                if (response.success) {
                    $container.html('<div class="alert alert-success">' + response.message + '</div>');
                    
                    // Load and display the question bank
                    loadQuestionBank();
                    
                    // Show cache indicator if applicable
                    if (response.from_cache) {
                        Notification.addNotification({
                            message: Str.get_string('cache_hit', 'local_trustgrade'),
                            type: 'info'
                        });
                    }
                } else {
                    $container.html('<div class="alert alert-danger">' + 
                        '<strong>' + Str.get_string('gateway_error', 'local_trustgrade') + ':</strong> ' + 
                        (response.error || 'Unknown error') + '</div>');
                }
            }).fail(function(ex) {
                $container.html('<div class="alert alert-danger">' + 
                    '<strong>' + Str.get_string('gateway_error', 'local_trustgrade') + ':</strong> ' + 
                    ex.message + '</div>');
                Notification.exception(ex);
            }).always(function() {
                $button.prop('disabled', false).html('<i class="fa fa-cogs"></i> ' + 
                    Str.get_string('generate_questions', 'local_trustgrade'));
            });
        });
    }

    /**
     * Load and display the question bank
     */
    function loadQuestionBank() {
        var $container = $('#question-bank-container');
        
        $container.html('<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> ' + 
            Str.get_string('loading_question_bank', 'local_trustgrade') + '</div>');

        var promise = Ajax.call([{
            methodname: 'local_trustgrade_get_question_bank',
            args: {
                cmid: cmid
            }
        }])[0];

        promise.done(function(response) {
            if (response.success) {
                if (response.html) {
                    $container.html('<div class="question-bank-wrapper">' + 
                        '<h4><i class="fa fa-bank"></i> ' + Str.get_string('question_bank_title', 'local_trustgrade') + '</h4>' + 
                        response.html + '</div>');
                } else {
                    $container.html('<div class="alert alert-info">No questions available yet.</div>');
                }
            } else {
                $container.html('<div class="alert alert-danger">Error loading question bank: ' + 
                    (response.error || 'Unknown error') + '</div>');
            }
        }).fail(function(ex) {
            $container.html('<div class="alert alert-danger">Error loading question bank: ' + ex.message + '</div>');
        });
    }

    /**
     * Bind quiz settings events
     */
    function bindQuizSettingsEvents() {
        // Handle settings updates
        $(document).on('change', '.quiz-setting-input', function() {
            var $input = $(this);
            var settingName = $input.data('setting');
            var settingValue = $input.val();

            // Convert checkbox values to boolean
            if ($input.is(':checkbox')) {
                settingValue = $input.is(':checked') ? '1' : '0';
            }

            updateQuizSetting(settingName, settingValue, $input);
        });

        // Load question bank on page load
        if ($('#question-bank-container').length > 0) {
            loadQuestionBank();
        }
    }

    /**
     * Update a quiz setting
     * @param {string} settingName Name of the setting
     * @param {string} settingValue Value of the setting
     * @param {jQuery} $input Input element
     */
    function updateQuizSetting(settingName, settingValue, $input) {
        var promise = Ajax.call([{
            methodname: 'local_trustgrade_update_quiz_setting',
            args: {
                cmid: cmid,
                setting_name: settingName,
                setting_value: settingValue
            }
        }])[0];

        promise.done(function(response) {
            if (response.success) {
                // Show success indicator
                $input.addClass('setting-saved');
                setTimeout(function() {
                    $input.removeClass('setting-saved');
                }, 2000);
                
                Notification.addNotification({
                    message: Str.get_string('setting_updated_success', 'local_trustgrade').replace('{$a}', settingName),
                    type: 'success'
                });
            } else {
                Notification.addNotification({
                    message: Str.get_string('setting_update_error', 'local_trustgrade').replace('{$a}', response.error || 'Unknown error'),
                    type: 'error'
                });
            }
        }).fail(function(ex) {
            Notification.addNotification({
                message: Str.get_string('setting_update_error', 'local_trustgrade').replace('{$a}', ex.message),
                type: 'error'
            });
        });
    }

    /**
     * Get instructions text from the page
     * @return {string} Instructions text
     */
    function getInstructionsText() {
        // Try multiple selectors to find instructions
        var selectors = [
            '#id_assignmentintro_editor',
            '#id_intro_editor', 
            '[name="assignmentintro[text]"]',
            '[name="intro[text]"]',
            '.assignment-intro',
            '.intro'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var $element = $(selectors[i]);
            if ($element.length > 0) {
                // Handle TinyMCE editors
                if (typeof tinyMCE !== 'undefined') {
                    var editorId = $element.attr('id');
                    var editor = tinyMCE.get(editorId);
                    if (editor) {
                        return editor.getContent({format: 'text'});
                    }
                }
                
                // Handle regular textareas and inputs
                var content = $element.val() || $element.text() || $element.html();
                if (content && content.trim()) {
                    // Strip HTML tags if present
                    return $('<div>').html(content).text().trim();
                }
            }
        }

        return '';
    }

    return {
        init: init
    };
});
