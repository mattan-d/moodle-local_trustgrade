define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str'
], function($, Ajax, Notification, Str) {

    var cmid;

    /**
     * Get instructions from the editor.
     * @returns {string} The instructions text.
     */
    function getInstructions() {
        if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.get('id_introeditoreditable')) {
            return window.tinyMCE.get('id_introeditoreditable').getContent({ format: 'text' });
        } else if ($('#id_introeditoreditable').length) {
            return $('#id_introeditoreditable').text();
        }
        return '';
    }

    /**
     * Bind events for the TrustGrade panel.
     */
    function bindEvents() {
        $('#check-instructions-btn').on('click', function() {
            var $button = $(this);
            var $loading = $('#ai-loading');
            var instructions = getInstructions();
            var $recommendationContainer = $('#ai-recommendation-container');
            var $recommendationDiv = $('#ai-recommendation');

            if (!instructions.trim()) {
                Str.get_string('no_instructions', 'local_trustgrade').then(function(str) {
                    Notification.addNotification({ message: str, type: 'error' });
                });
                return;
            }

            $button.prop('disabled', true);
            $loading.show();
            $recommendationContainer.show();
            $recommendationDiv.html('');

            var promise = Ajax.call([{
                methodname: 'local_trustgrade_check_instructions',
                args: {
                    cmid: cmid,
                    instructions: instructions
                }
            }])[0];

            promise.done(function(response) {
                if (response.success && response.recommendation) {
                    var recommendation = response.recommendation;
                    var html = '';

                    // Evaluation Text
                    if (recommendation.EvaluationText && recommendation.EvaluationText.content) {
                        html += '<h3>Evaluation Summary</h3>';
                        html += '<div class="trustgrade-evaluation-text">' + recommendation.EvaluationText.content.replace(/\n/g, '<br>') + '</div>';
                    }

                    // Criteria Table
                    if (recommendation.table && recommendation.table.rows && recommendation.table.rows.length > 0) {
                        html += '<h3>' + (recommendation.table.title || 'Criteria Analysis') + '</h3>';
                        html += '<table class="table table-bordered table-striped generictable">';
                        html += '<thead><tr><th>Criterion</th><th>Met</th><th>Suggestions</th></tr></thead>';
                        html += '<tbody>';
                        recommendation.table.rows.forEach(function(row) {
                            html += '<tr>';
                            html += '<td>' + (row.Criterion || '') + '</td>';
                            html += '<td>' + (row.Met || '') + '</td>';
                            html += '<td>' + (row.Suggestions || '') + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                    }

                    // Improved Assignment Text
                    if (recommendation.ImprovedAssignment && recommendation.ImprovedAssignment.content) {
                        html += '<h3>Improved Assignment Instructions</h3>';
                        html += '<div class="trustgrade-improved-assignment">' + recommendation.ImprovedAssignment.content.replace(/\n/g, '<br>') + '</div>';
                    }

                    if (html === '') {
                        html = '<p>No recommendation provided or the format is unrecognized.</p>';
                    }

                    $recommendationDiv.html(html);

                    if (response.from_cache) {
                        Str.get_string('cache_hit', 'local_trustgrade').then(function(str) {
                            Notification.addNotification({ message: str, type: 'info' });
                        });
                    }
                } else {
                    var error = response.error || 'An unknown error occurred.';
                    $recommendationDiv.html('<p class="text-danger">' + error + '</p>');
                }
            }).fail(Notification.exception).always(function() {
                $button.prop('disabled', false);
                $loading.hide();
            });
        });
    }

    return {
        init: function(courseModuleId) {
            cmid = courseModuleId || 0;
            bindEvents();
        }
    };
});
