define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/config',
    'core/str',
    'core/modal_factory',
    'core/modal_events'
], function($, Ajax, Notification, M, Str, ModalFactory, ModalEvents) {

    var cmid;
    var questions = [];

    function init(courseModuleId) {
        cmid = courseModuleId || 0;
        bindCheckInstructions();
        bindGenerateQuestions();
        loadQuestionBank();
        bindQuizSettingsEvents();
        bindQuestionBankEvents();
    }

    function getInstructions() {
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('id_introeditoreditable')) {
            return tinyMCE.get('id_introeditoreditable').getContent({format: 'text'});
        } else if ($('#id_introeditoreditable').length) {
            return $('#id_introeditoreditable').text();
        }
        return '';
    }

    function bindCheckInstructions() {
        $('#check-instructions-btn').on('click', function() {
            var $button = $(this);
            var $spinner = $button.find('.fa-spinner');
            var instructions = getInstructions();
            var $resultContainer = $('#ai-recommendation-result');

            if (!instructions) {
                Notification.addNotification({
                    message: Str.get_string('no_instructions_error', 'local_trustgrade'),
                    type: 'error'
                });
                return;
            }

            $button.prop('disabled', true);
            $spinner.removeClass('hidden');
            $resultContainer.html('<p>' + Str.get_string('processing', 'local_trustgrade') + '</p>').removeClass('hidden');

            var promise = Ajax.call([{
                methodname: 'local_trustgrade_check_instructions',
                args: {
                    cmid: cmid,
                    instructions: instructions
                }
            }])[0];

            promise.done(function(response) {
                $resultContainer.removeClass('hidden');
                if (response.success && response.recommendation) {
                    var recommendation = response.recommendation;
                    var html = '';

                    // Evaluation Text
                    if (recommendation.EvaluationText && recommendation.EvaluationText.content) {
                        html += '<h3>Evaluation</h3>';
                        html += '<div class="trustgrade-evaluation-text">' + recommendation.EvaluationText.content.replace(/\n/g, '<br>') + '</div>';
                    }

                    // Criteria Table
                    if (recommendation.table && recommendation.table.rows && recommendation.table.rows.length > 0) {
                        html += '<h3>' + (recommendation.table.title || 'Criteria Analysis') + '</h3>';
                        html += '<table class="table table-bordered table-striped">';
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

                    $resultContainer.html(html);

                    if (response.from_cache) {
                        Notification.addNotification({
                            message: Str.get_string('cache_hit', 'local_trustgrade'),
                            type: 'info'
                        });
                    }
                } else {
                    var error = response.error || 'An unknown error occurred.';
                    $resultContainer.html('<p class="text-danger">' + error + '</p>');
                }
            }).fail(Notification.exception).always(function() {
                $button.prop('disabled', false);
                $spinner.addClass('hidden');
            });
        });
    }

    function bindGenerateQuestions() {
        $('#generate-questions-btn').on('click', function() {
            var $button = $(this);
            var $spinner = $button.find('.fa-spinner');
            var instructions = getInstructions();
            var $container = $('#generated-questions-container');

            if (!instructions) {
                Notification.addNotification({
                    message: Str.get_string('no_instructions_questions_error', 'local_trustgrade'),
                    type: 'error'
                });
                return;
            }

            $button.prop('disabled', true);
            $spinner.removeClass('hidden');
            $container.html('<p>' + Str.get_string('generating_questions', 'local_trustgrade') + '</p>').removeClass('hidden');

            var promise = Ajax.call([{
                methodname: 'local_trustgrade_generate_questions',
                args: {
                    cmid: cmid,
                    instructions: instructions
                }
            }])[0];

            promise.done(function(response) {
                if (response.success) {
                    Notification.addNotification({
                        message: response.message,
                        type: 'success'
                    });
                    loadQuestionBank();
                } else {
                    $container.html('<p class="text-danger">' + response.error + '</p>');
                }
            }).fail(Notification.exception).always(function() {
                $button.prop('disabled', false);
                $spinner.addClass('hidden');
            });
        });
    }

    function loadQuestionBank() {
        var $container = $('#generated-questions-container');
        $container.html('<p>' + Str.get_string('loading_question_bank', 'local_trustgrade') + '</p>').removeClass('hidden');

        var promise = Ajax.call([{
            methodname: 'local_trustgrade_get_question_bank',
            args: { cmid: cmid }
        }])[0];

        promise.done(function(response) {
            if (response.success) {
                if (response.html) {
                    $container.html(response.html);
                    questions = JSON.parse(response.questions);
                } else {
                    $container.html('').addClass('hidden');
                    questions = [];
                }
            } else {
                $container.html('<p class="text-danger">' + response.error + '</p>');
            }
        }).fail(Notification.exception);
    }

    function bindQuizSettingsEvents() {
        $('.quiz-setting-input').on('change', function() {
            var $input = $(this);
            var settingName = $input.attr('name');
            var settingValue = $input.val();

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
                    Notification.addNotification({
                        message: Str.get_string('setting_updated_success', 'local_trustgrade', {setting: settingName}),
                        type: 'success'
                    });
                } else {
                    Notification.addNotification({
                        message: Str.get_string('setting_update_error', 'local_trustgrade', {error: response.error}),
                        type: 'error'
                    });
                }
            }).fail(Notification.exception);
        });
    }

    function bindQuestionBankEvents() {
        var $container = $('#generated-questions-container');

        $container.on('click', '.edit-question-btn', function() {
            var index = $(this).data('index');
            openQuestionEditor(index);
        });

        $container.on('click', '.delete-question-btn', function() {
            var index = $(this).data('index');
            confirmDeleteQuestion(index);
        });

        $container.on('click', '#add-new-question-btn', function() {
            openQuestionEditor(-1); // -1 for new question
        });
    }

    function openQuestionEditor(index) {
        var isNew = index === -1;
        var question = isNew ? {
            question: '',
            options: ['', '', '', ''],
            answer: 0,
            explanation: ''
        } : questions[index];

        var optionsHtml = question.options.map(function(option, i) {
            return '<div class="form-check">' +
                '<input type="radio" name="answer" class="form-check-input" id="option' + i + '" value="' + i + '" ' + (parseInt(question.answer, 10) === i ? 'checked' : '') + '>' +
                '<label class="form-check-label" for="option' + i + '">Option ' + (i + 1) + '</label>' +
                '<input type="text" class="form-control" value="' + option + '">' +
                '</div>';
        }).join('');

        var modalBody = '<form>' +
            '<div class="form-group">' +
            '<label for="question-text">Question Text</label>' +
            '<textarea id="question-text" class="form-control">' + question.question + '</textarea>' +
            '</div>' +
            '<div class="form-group">' +
            '<label>Answer Options & Correct Answer</label>' +
            optionsHtml +
            '</div>' +
            '<div class="form-group">' +
            '<label for="explanation-text">Explanation</label>' +
            '<textarea id="explanation-text" class="form-control">' + question.explanation + '</textarea>' +
            '</div>' +
            '</form>';

        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: isNew ? 'Add New Question' : 'Edit Question',
            body: modalBody
        }).then(function(modal) {
            modal.getRoot().on(ModalEvents.save, function() {
                saveQuestion(index, modal);
            });
            modal.show();
        });
    }

    function saveQuestion(index, modal) {
        var $root = modal.getRoot();
        var questionText = $root.find('#question-text').val().trim();
        var explanation = $root.find('#explanation-text').val().trim();
        var answer = $root.find('input[name="answer"]:checked').val();
        var options = [];
        $root.find('.form-check input[type="text"]').each(function() {
            options.push($(this).val().trim());
        });

        if (!questionText) {
            Notification.addNotification({ message: Str.get_string('question_text_required', 'local_trustgrade'), type: 'error' });
            return;
        }
        if (options.some(function(opt) { return opt === ''; })) {
            Notification.addNotification({ message: Str.get_string('all_options_required', 'local_trustgrade'), type: 'error' });
            return;
        }

        var questionData = {
            question: questionText,
            options: options,
            answer: answer,
            explanation: explanation
        };

        var promise = Ajax.call([{
            methodname: 'local_trustgrade_save_question',
            args: {
                cmid: cmid,
                question_index: index,
                question_data: JSON.stringify(questionData)
            }
        }])[0];

        promise.done(function(response) {
            if (response.success) {
                Notification.addNotification({ message: Str.get_string('question_saved_success', 'local_trustgrade'), type: 'success' });
                modal.hide();
                loadQuestionBank();
            } else {
                Notification.addNotification({ message: response.error, type: 'error' });
            }
        }).fail(Notification.exception);
    }

    function confirmDeleteQuestion(index) {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: Str.get_string('confirm_delete_question_title', 'local_trustgrade'),
            body: Str.get_string('confirm_delete_question_message', 'local_trustgrade')
        }).then(function(modal) {
            modal.setSaveButtonText(Str.get_string('delete', 'local_trustgrade'));
            modal.getRoot().on(ModalEvents.save, function() {
                var promise = Ajax.call([{
                    methodname: 'local_trustgrade_delete_question',
                    args: {
                        cmid: cmid,
                        question_index: index
                    }
                }])[0];

                promise.done(function(response) {
                    if (response.success) {
                        Notification.addNotification({ message: Str.get_string('question_deleted_success', 'local_trustgrade'), type: 'success' });
                        loadQuestionBank();
                    } else {
                        Notification.addNotification({ message: response.error, type: 'error' });
                    }
                }).fail(Notification.exception);
            });
            modal.show();
        });
    }

    return {
        init: init
    };
});
