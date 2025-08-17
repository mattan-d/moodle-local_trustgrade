<?php
// This file is part of Moodle - http://moodle.org/

namespace local_trustgrade\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Renderer for the AI Quiz Report with Direct Grading in a table format.
 */
class report_renderer extends \plugin_renderer_base {

    /**
     * Renders the entire quiz report for all completed sessions with grading functionality.
     *
     * @param array $sessions Array of session objects from quiz_session class.
     * @param int $cmid Course module ID for grading context.
     * @return string HTML output for the report.
     */
    public function render_quiz_report($sessions, $cmid) {
        global $PAGE;

        // Load grading JavaScript (CSS is loaded in quiz_report.php)
        $PAGE->requires->js_call_amd('local_trustgrade/grading', 'init', [$cmid]);

        $html = '';

        // Add grading controls header
        $html .= $this->render_grading_controls($sessions, $cmid);

        // Render the report as a table
        $table = new \html_table();
        $table->head = [
            get_string('defaultcoursestudentdescription'),
            get_string('quiz_score', 'local_trustgrade'),
            get_string('completed_on', 'local_trustgrade'),
            get_string('time_taken', 'local_trustgrade'),
            get_string('final_grade', 'local_trustgrade'),
            get_string('details', 'local_trustgrade')
        ];
        $table->attributes['class'] = 'table table-striped table-bordered grading-table';
        $table->id = 'quiz-report-table';

        if (empty($sessions)) {
            $row = new \html_table_row();
            $cell = new \html_table_cell(get_string('no_completed_quizzes', 'local_trustgrade'));
            $cell->colspan = count($table->head);
            $cell->attributes['class'] = 'text-center';
            $row->cells[] = $cell;
            $table->data[] = $row;
        } else {
            foreach ($sessions as $session) {
                $userid = $session->userid;
                $collapse_id = 'collapse' . $userid;

                // Main row for the student
                $row = new \html_table_row();
                $row->attributes['data-userid'] = $userid;

                // Student Name
                $row->cells[] = fullname($session);

                // Quiz Score
                $questions = (array)$session->questions_data;
                $total_points = 0;
                foreach ($questions as $question) {
                    $total_points += isset($question->points) ? $question->points : 10;
                }
                $quiz_score = $session->final_score;
                $percentage = $total_points > 0 ? round(($quiz_score / $total_points) * 100) : 0;
                $row->cells[] = $quiz_score . '/' . $total_points . ' (' . $percentage . '%)';

                // Completed Date
                $completed_date = $session->timecompleted ?: $session->timemodified;
                $row->cells[] = userdate($completed_date, get_string('strftimedatetimeshort'));

                // Time Taken
                $time_taken = $completed_date - $session->timecreated;
                $row->cells[] = $this->format_duration($time_taken);

                // Final Grade (manual grade input)
                $current_grade = $this->get_current_assignment_grade($userid, $cmid);
                $grade_input_html = $this->render_grade_input($userid, $current_grade, $cmid);
                $cell = new \html_table_cell($grade_input_html);
                $cell->attributes['class'] = 'grade-input-cell';
                $row->cells[] = $cell;

                // Details button
                $details_button = html_writer::tag('button', get_string('view_details', 'local_trustgrade'), [
                    'class' => 'btn btn-secondary btn-sm',
                    'data-toggle' => 'collapse',
                    'data-target' => '#' . $collapse_id,
                    'aria-expanded' => 'false',
                    'aria-controls' => $collapse_id
                ]);
                $row->cells[] = $details_button;

                $table->data[] = $row;

                // Hidden row for the details
                $details_html = $this->render_card_body($session);
                $details_cell = new \html_table_cell($details_html);
                $details_cell->colspan = count($table->head);
                $details_cell->attributes['class'] = 'student-details-cell p-3';

                $details_row = new \html_table_row();
                $details_row->cells[] = $details_cell;
                $details_row->attributes['class'] = 'collapse';
                $details_row->id = $collapse_id;

                $table->data[] = $details_row;
            }
        }

        $html .= html_writer::table($table);

        return $html;
    }

    /**
     * Renders grading control buttons and status
     *
     * @param array $sessions Array of session objects
     * @param int $cmid Course module ID for grading context
     * @return string HTML for grading controls
     */
    protected function render_grading_controls($sessions, $cmid) {
        $html = '';

        $html .= html_writer::start_div('grading-controls-container mb-4');

        // Grading actions row
        $html .= html_writer::start_div('row align-items-center');

        // Left side - bulk actions
        $html .= html_writer::start_div('col-md-8');
        $html .= html_writer::start_div('btn-group', ['role' => 'group']);

        $html .= html_writer::tag('button',
            '<i class="fa fa-save"></i> ' . get_string('save_all_pending', 'local_trustgrade'),
            [
                'id' => 'bulk-save-grades',
                'class' => 'btn btn-primary',
                'style' => 'display: none;'
            ]
        );

        // Auto-grade button - only show if there are completed sessions
        if (!empty($sessions)) {
            $html .= html_writer::tag('button',
                '<i class="fa fa-magic"></i> ' . get_string('auto_grade_by_quiz', 'local_trustgrade'),
                [
                    'id' => 'auto-grade-by-quiz',
                    'class' => 'btn btn-success',
                    'title' => get_string('auto_grade_by_quiz_desc', 'local_trustgrade')
                ]
            );
        }

        $html .= html_writer::tag('button',
            '<i class="fa fa-eraser"></i> ' . get_string('clear_all_grades', 'local_trustgrade'),
            [
                'id' => 'clear-all-grades',
                'class' => 'btn btn-outline-secondary'
            ]
        );

        $html .= html_writer::end_div(); // btn-group
        $html .= html_writer::end_div(); // col-md-8

        // Right side - status
        $html .= html_writer::start_div('col-md-4 text-right');
        $html .= html_writer::span('', 'pending-grades-status', [
            'id' => 'pending-grades-count',
            'style' => 'display: none;'
        ]);
        $html .= html_writer::end_div(); // col-md-4

        $html .= html_writer::end_div(); // row

        // Instructions
        $html .= html_writer::start_div('grading-instructions mt-2');
        $html .= html_writer::tag('small',
            '<i class="fa fa-info-circle"></i> ' . get_string('grading_instructions', 'local_trustgrade'),
            ['class' => 'text-muted']
        );
        $html .= html_writer::end_div();

        $html .= html_writer::end_div(); // grading-controls-container

        return $html;
    }

    /**
     * Renders the grade input field with status indicator
     *
     * @param int $userid User ID
     * @param float|null $current_grade Current grade value
     * @param int $cmid Course module ID
     * @return string HTML for grade input
     */
    protected function render_grade_input($userid, $current_grade, $cmid) {
        $grade_value = $current_grade !== null ? number_format($current_grade, 2) : '';

        $html = html_writer::start_div('grade-input-container');

        // Grade label
        $html .= html_writer::tag('label', get_string('final_grade', 'local_trustgrade'), [
            'for' => 'grade_' . $userid,
            'class' => 'grade-label sr-only'
        ]);

        // Input group
        $html .= html_writer::start_div('input-group input-group-sm');

        // Grade input
        $html .= html_writer::empty_tag('input', [
            'type' => 'number',
            'id' => 'grade_' . $userid,
            'class' => 'form-control grade-input',
            'data-userid' => $userid,
            'value' => $grade_value,
            'placeholder' => '0.00',
            'step' => '0.01',
            'min' => '0',
            'style' => 'width: 100px;'
        ]);

        // Status icon
        $html .= html_writer::start_div('input-group-append');
        $html .= html_writer::span('', 'input-group-text grade-status-icon', [
            'title' => get_string('grade_status', 'local_trustgrade')
        ]);
        $html .= html_writer::end_div();

        $html .= html_writer::end_div(); // input-group
        $html .= html_writer::end_div(); // grade-input-container

        return $html;
    }

    /**
     * Renders the body of an accordion card with session details.
     *
     * @param \stdClass $session The session object.
     * @return string HTML for the card body.
     */
    protected function render_card_body($session) {
        $html = '';

        // Session info
        $html .= html_writer::tag('h5', get_string('session_info', 'local_trustgrade'));
        $html .= html_writer::start_div('row mb-3');

        $html .= html_writer::start_div('col-md-4');
        $html .= html_writer::tag('strong', get_string('completed_on', 'local_trustgrade') . ': ');
        $html .= userdate($session->timecompleted ?: $session->timemodified);
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('col-md-4');
        $html .= html_writer::tag('strong', get_string('time_taken', 'local_trustgrade') . ': ');
        $time_taken = ($session->timecompleted ?: $session->timemodified) - $session->timecreated;
        $html .= $this->format_duration($time_taken);
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('col-md-4');
        $questions = (array)$session->questions_data;
        $total_points = 0;
        foreach ($questions as $question) {
            $total_points += isset($question->points) ? $question->points : 10;
        }
        $score = $session->final_score;
        $percentage = $total_points > 0 ? round(($session->final_score / $total_points) * 100) : 0;

        $html .= html_writer::tag('strong', get_string('quiz_score', 'local_trustgrade') . ': ');
        $html .= $score . '/' . $total_points . ' (' . $percentage . '%)';
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();

        // Integrity summary
        $html .= html_writer::tag('h5', get_string('integrity_summary', 'local_trustgrade'));
        $blur_count = $session->window_blur_count ?? 0;
        $violations = (array)$session->integrity_violations;

        $html .= html_writer::start_div('alert alert-' . ($blur_count > 0 || !empty($violations) ? 'warning' : 'success'));
        $html .= html_writer::tag('p', get_string('window_blur_events', 'local_trustgrade') . ': ' . $blur_count);

        if (!empty($violations)) {
            $html .= html_writer::tag('p', get_string('integrity_violations_count', 'local_trustgrade', count($violations)));
            $html .= html_writer::start_tag('ul');
            foreach ($violations as $violation) {
                $html .= html_writer::tag('li',
                    ucfirst(str_replace('_', ' ', $violation->type)) .
                    ' (' . userdate($violation->timestamp) . ')'
                );
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= html_writer::end_div();

        // Quiz details
        $html .= html_writer::tag('h5', get_string('quiz_details', 'local_trustgrade'));
        $html .= $this->render_questions_table($session);

        return $html;
    }

    /**
     * Renders a table of questions, answers, and results.
     *
     * @param \stdClass $session The session object.
     * @return string HTML for the questions table.
     */
    protected function render_questions_table($session) {
        $table = new \html_table();
        $table->head = [
            '#',
            get_string('question', 'local_trustgrade'),
            get_string('student_answer', 'local_trustgrade'),
            get_string('correct_answer', 'local_trustgrade'),
            get_string('points', 'local_trustgrade'),
            get_string('result', 'local_trustgrade')
        ];
        $table->attributes['class'] = 'table table-striped table-bordered';

        $questions = (array)$session->questions_data;
        $answers = (array)$session->answers_data;

        foreach ($questions as $index => $question) {
            // Get the user's answer for this question index
            $user_answer = isset($answers[$index]) ? $answers[$index] : null;

            $student_answer_display = $this->format_student_answer($question, $user_answer);
            $correct_answer_display = $this->format_correct_answer($question, $user_answer);

            // Determine if answer is correct
            $is_correct = $this->is_answer_correct($question, $user_answer);

            // Calculate points
            $question_points = isset($question->points) ? $question->points : 10;
            $earned_points = $is_correct ? $question_points : 0;

            $result_icon = $is_correct
                ? $this->output->pix_icon('i/valid', get_string('correct', 'local_trustgrade'), 'moodle', ['class' => 'text-success'])
                : $this->output->pix_icon('i/invalid', get_string('incorrect', 'local_trustgrade'), 'moodle', ['class' => 'text-danger']);

            $row = new \html_table_row();
            $row->cells[] = $index + 1;

            // Question cell with source badge
            $question_cell = html_writer::div(
                html_writer::span(
                    ucfirst($question->source ?? 'instructor'),
                    'badge badge-' . (($question->source ?? 'instructor') === 'instructor' ? 'primary' : 'success') . ' mb-2'
                ) .
                html_writer::tag('div', format_text($this->get_question_text($question), FORMAT_HTML), ['class' => 'question-text']) .
                $this->render_question_options($question, $user_answer),
                'question-container'
            );
            $row->cells[] = $question_cell;

            $row->cells[] = $student_answer_display;
            $row->cells[] = $correct_answer_display;
            $row->cells[] = $earned_points . '/' . $question_points;
            $row->cells[] = $result_icon;

            // Add row class based on correctness
            $row->attributes['class'] = $is_correct ? 'table-success' : 'table-danger';

            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Format the student's answer for display
     *
     * @param \stdClass $question The question object
     * @param mixed $user_answer The user's answer
     * @return string Formatted answer
     */
    protected function format_student_answer($question, $user_answer) {
        if ($user_answer === null || $user_answer === '') {
            return html_writer::span(get_string('no_answer', 'local_trustgrade'), 'text-muted font-italic');
        }

        switch ($question->type) {
            case 'multiple_choice':
                $options = $this->normalize_options($question);
                $order = $this->get_display_order($question, $user_answer, count($options));
                $selectedDisplayIndex = $this->get_selected_display_index($user_answer);
                $selectedBaseIndex = $this->display_to_base_index($selectedDisplayIndex, $order);

                $raw_answer_display = html_writer::div(
                    html_writer::tag('small', get_string('raw_answer_value', 'local_trustgrade') . ': ') .
                    html_writer::tag('code', var_export($user_answer, true)),
                    'text-muted mb-1'
                );

                if ($selectedBaseIndex !== null && isset($options[$selectedBaseIndex])) {
                    $label = chr(65 + max(0, (int)$selectedDisplayIndex));
                    $text = $options[$selectedBaseIndex]->text;
                    return $raw_answer_display . html_writer::div(html_writer::tag('strong', $label . '. ') . $text, 'text-primary');
                } else {
                    $invalid_display = html_writer::span(
                        get_string('invalid_option_selected', 'local_trustgrade'),
                        'text-danger font-weight-bold'
                    );
                    return $raw_answer_display . $invalid_display;
                }

            case 'true_false':
                // Legacy support if still present.
                $raw_answer_display = html_writer::div(
                    html_writer::tag('small', get_string('raw_answer_value', 'local_trustgrade') . ': ') .
                    html_writer::tag('code', var_export($user_answer, true)),
                    'text-muted mb-1'
                );

                if ($user_answer === true || $user_answer === 'true' || $user_answer === 1 || $user_answer === '1') {
                    return $raw_answer_display . html_writer::div(get_string('true', 'local_trustgrade'), 'text-primary');
                } else if ($user_answer === false || $user_answer === 'false' || $user_answer === 0 || $user_answer === '0') {
                    return $raw_answer_display . html_writer::div(get_string('false', 'local_trustgrade'), 'text-primary');
                } else {
                    $invalid_display = html_writer::span(
                        get_string('invalid_boolean_value', 'local_trustgrade'),
                        'text-danger font-weight-bold'
                    );
                    return $raw_answer_display . $invalid_display;
                }

            case 'short_answer':
                return html_writer::div(format_text($user_answer, FORMAT_PLAIN), 'border p-2 bg-light');

            default:
                return html_writer::div(
                    html_writer::tag('small', get_string('raw_answer_value', 'local_trustgrade') . ': ') .
                    html_writer::tag('code', var_export($user_answer, true)) .
                    html_writer::div(
                        html_writer::span(get_string('unknown_question_type', 'local_trustgrade'), 'text-warning'),
                        'mt-1'
                    ),
                    'text-muted'
                );
        }
    }

    /**
     * Format the correct answer for display
     *
     * @param \stdClass $question The question object
     * @param mixed $user_answer The user's answer (used to align letters with the shown order)
     * @return string Formatted correct answer
     */
    protected function format_correct_answer($question, $user_answer) {
        switch ($question->type) {
            case 'multiple_choice':
                $options = $this->normalize_options($question);
                if (empty($options)) {
                    return get_string('not_available', 'local_trustgrade');
                }

                // Determine display order to label with the letters students saw.
                $order = $this->get_display_order($question, $user_answer, count($options));

                // Collect all correct options.
                $correctItems = [];
                foreach ($options as $baseIndex => $opt) {
                    if (!empty($opt->is_correct)) {
                        // Try to find its display index to compute a letter label.
                        $displayIndex = $this->base_to_display_index($baseIndex, $order);
                        $label = $displayIndex !== null ? chr(65 + $displayIndex) . '. ' : '';
                        $correctItems[] = $label . $opt->text;
                    }
                }

                if (empty($correctItems)) {
                    // Legacy fallback: single index on $question->correct_answer
                    if (isset($question->correct_answer) && isset($options[(int)$question->correct_answer])) {
                        $baseIndex = (int)$question->correct_answer;
                        $displayIndex = $this->base_to_display_index($baseIndex, $order);
                        $label = $displayIndex !== null ? chr(65 + $displayIndex) . '. ' : '';
                        return $label . $options[$baseIndex]->text;
                    }
                    return get_string('not_available', 'local_trustgrade');
                }

                // Join multiple correct answers if any (comma-separated).
                return implode(', ', $correctItems);

            case 'true_false':
                return isset($question->correct_answer)
                    ? ($question->correct_answer ? get_string('true', 'local_trustgrade') : get_string('false', 'local_trustgrade'))
                    : get_string('not_available', 'local_trustgrade');

            case 'short_answer':
                return html_writer::span(get_string('manual_grading_required', 'local_trustgrade'), 'text-info font-italic');

            default:
                return get_string('not_available', 'local_trustgrade');
        }
    }

    /**
     * Check if the user's answer is correct
     *
     * @param \stdClass $question The question object
     * @param mixed $user_answer The user's answer
     * @return bool True if correct
     */
    protected function is_answer_correct($question, $user_answer) {
        if ($user_answer === null || $user_answer === '') {
            return false;
        }

        switch ($question->type) {
            case 'multiple_choice': {
                $options = $this->normalize_options($question);
                if (empty($options)) {
                    return false;
                }
                $order = $this->get_display_order($question, $user_answer, count($options));
                $selectedDisplayIndex = $this->get_selected_display_index($user_answer);
                $selectedBaseIndex = $this->display_to_base_index($selectedDisplayIndex, $order);

                if ($selectedBaseIndex === null || !isset($options[$selectedBaseIndex])) {
                    // Legacy fallback: if user_answer is a base index directly.
                    if (is_numeric($user_answer) && isset($options[(int)$user_answer])) {
                        $selectedBaseIndex = (int)$user_answer;
                    } else {
                        return false;
                    }
                }

                // Primary: per-option is_correct.
                if (isset($options[$selectedBaseIndex]->is_correct)) {
                    return (bool)$options[$selectedBaseIndex]->is_correct;
                }

                // Legacy fallback: compare to question->correct_answer
                if (isset($question->correct_answer)) {
                    return (int)$selectedBaseIndex === (int)$question->correct_answer;
                }

                return false;
            }

            case 'true_false': {
                $user_bool = null;
                if ($user_answer === true || $user_answer === 'true' || $user_answer === 1 || $user_answer === '1') {
                    $user_bool = true;
                } else if ($user_answer === false || $user_answer === 'false' || $user_answer === 0 || $user_answer === '0') {
                    $user_bool = false;
                }

                return $user_bool !== null && isset($question->correct_answer) && $user_bool === (bool)$question->correct_answer;
            }

            case 'short_answer':
                return !empty(trim((string)$user_answer));

            default:
                return false;
        }
    }

    /**
     * Render question options for multiple choice questions
     * Respects the display order used in the attempt (if available) and highlights correct options.
     *
     * @param \stdClass $question The question object
     * @param mixed $user_answer The user's answer (used to discover display order)
     * @return string HTML for question options
     */
    protected function render_question_options($question, $user_answer) {
        if ($question->type !== 'multiple_choice' || !isset($question->options)) {
            return '';
        }

        $options = $this->normalize_options($question);
        if (empty($options)) {
            return '';
        }

        $order = $this->get_display_order($question, $user_answer, count($options));

        $html = html_writer::start_tag('ol', ['type' => 'A', 'class' => 'mt-2 mb-0']);

        // Iterate over display order to show what the student actually saw.
        foreach ($order as $displayIndex => $baseIndex) {
            if (!isset($options[$baseIndex])) {
                continue;
            }
            $opt = $options[$baseIndex];
            $class = !empty($opt->is_correct) ? 'text-success font-weight-bold' : '';
            $html .= html_writer::tag('li', $opt->text, ['class' => $class]);
        }
        $html .= html_writer::end_tag('ol');

        return $html;
    }

    /**
     * Format duration in human readable format
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    protected function format_duration($seconds) {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        if ($minutes > 0) {
            return $minutes . 'm ' . $seconds . 's';
        }
        return $seconds . 's';
    }

    /**
     * Get current assignment grade for a user
     *
     * @param int $userid User ID
     * @param int $cmid Course module ID
     * @return float|null Current grade or null if not graded
     */
    protected function get_current_assignment_grade($userid, $cmid) {
        global $DB;

        $cm = get_coursemodule_from_id('assign', $cmid);
        if (!$cm) {
            return null;
        }

        $grade = $DB->get_field('assign_grades', 'grade', [
            'assignment' => $cm->instance,
            'userid' => $userid
        ]);

        return $grade !== false ? floatval($grade) : null;
    }

    // ---------------------------
    // Helpers for new JSON schema
    // ---------------------------

    /**
     * Get question text from new or legacy field.
     *
     * @param \stdClass $question
     * @return string
     */
    protected function get_question_text($question) {
        if (isset($question->text) && $question->text !== '') {
            return (string)$question->text;
        }
        if (isset($question->question) && $question->question !== '') {
            return (string)$question->question;
        }
        return '';
    }

    /**
     * Normalize options into an array of objects with text, explanation, is_correct.
     * Supports:
     * - New schema: options as objects: { text, explanation?, is_correct? | correct? | isCorrect? }
     * - Legacy schema: options as strings with question->correct_answer index
     *
     * @param \stdClass $question
     * @return array<int,\stdClass>
     */
    protected function normalize_options($question) {
        $raw = isset($question->options) ? $question->options : [];
        // Convert to plain array (stdClass to array if necessary)
        if ($raw instanceof \stdClass) {
            $raw = (array)$raw;
        }

        $normalized = [];
        $hasCorrectIndex = isset($question->correct_answer);

        $i = 0;
        foreach ($raw as $key => $opt) {
            $item = (object)[
                'text' => '',
                'explanation' => null,
                'is_correct' => null,
            ];

            if (is_string($opt)) {
                // Legacy string option
                $item->text = $opt;
                if ($hasCorrectIndex && (int)$question->correct_answer === (int)$i) {
                    $item->is_correct = true;
                } else {
                    $item->is_correct = false;
                }
            } else if (is_object($opt) || is_array($opt)) {
                $optObj = is_array($opt) ? (object)$opt : $opt;
                $item->text = isset($optObj->text) ? (string)$optObj->text : (isset($optObj->label) ? (string)$optObj->label : '');
                $item->explanation = isset($optObj->explanation) ? (string)$optObj->explanation : (isset($optObj->rationale) ? (string)$optObj->rationale : null);

                // Determine correctness flag from various possible keys
                if (isset($optObj->is_correct)) {
                    $item->is_correct = (bool)$optObj->is_correct;
                } else if (isset($optObj->correct)) {
                    $item->is_correct = (bool)$optObj->correct;
                } else if (isset($optObj->isCorrect)) {
                    $item->is_correct = (bool)$optObj->isCorrect;
                } else if ($hasCorrectIndex) {
                    // Legacy fallback if per-option missing
                    $item->is_correct = ((int)$question->correct_answer === (int)$i);
                } else {
                    $item->is_correct = null;
                }
            } else {
                // Unknown option shape; stringify to be safe.
                $item->text = (string)json_encode($opt);
                $item->is_correct = null;
            }

            $normalized[] = $item;
            $i++;
        }

        return $normalized;
    }

    /**
     * Resolve the display order used for this attempt, if present.
     * Supports these keys on either user_answer or question:
     * - order
     * - options_order
     * - shuffled_order
     *
     * Returns an array where each entry is the baseIndex at a given displayIndex.
     *
     * @param \stdClass $question
     * @param mixed $user_answer
     * @param int $count number of options
     * @return array<int,int>
     */
    protected function get_display_order($question, $user_answer, $count) {
        $order = null;

        // Try on $user_answer (stdClass or array)
        if (is_object($user_answer) || is_array($user_answer)) {
            $ua = is_array($user_answer) ? (object)$user_answer : $user_answer;
            if (isset($ua->order) && is_array($ua->order)) {
                $order = $ua->order;
            } else if (isset($ua->options_order) && is_array($ua->options_order)) {
                $order = $ua->options_order;
            } else if (isset($ua->shuffled_order) && is_array($ua->shuffled_order)) {
                $order = $ua->shuffled_order;
            }
        }

        // Try on $question (stdClass)
        if ($order === null && is_object($question)) {
            if (isset($question->order) && is_array($question->order)) {
                $order = $question->order;
            } else if (isset($question->options_order) && is_array($question->options_order)) {
                $order = $question->options_order;
            } else if (isset($question->shuffled_order) && is_array($question->shuffled_order)) {
                $order = $question->shuffled_order;
            }
        }

        // Identity if nothing recorded or invalid
        if (!is_array($order) || empty($order)) {
            $order = [];
            for ($i = 0; $i < $count; $i++) {
                $order[$i] = $i;
            }
        }

        // Normalize: ensure integers and valid range
        $order = array_values(array_map('intval', $order));
        foreach ($order as $i => $v) {
            if ($v < 0 || $v >= $count) {
                $order[$i] = $i; // fallback
            }
        }

        return $order;
    }

    /**
     * Get selected display index from various possible user_answer shapes.
     *
     * @param mixed $user_answer
     * @return int|null
     */
    protected function get_selected_display_index($user_answer) {
        if (is_numeric($user_answer)) {
            return (int)$user_answer;
        }

        if (is_object($user_answer) || is_array($user_answer)) {
            $ua = is_array($user_answer) ? (object)$user_answer : $user_answer;
            foreach (['index', 'selectedIndex', 'selected', 'answer'] as $key) {
                if (isset($ua->{$key}) && is_numeric($ua->{$key})) {
                    return (int)$ua->{$key};
                }
            }
        }

        return null;
    }

    /**
     * Map display index to base index using the order array.
     *
     * @param int|null $displayIndex
     * @param array<int,int> $order
     * @return int|null
     */
    protected function display_to_base_index($displayIndex, $order) {
        if ($displayIndex === null) {
            return null;
        }
        return isset($order[$displayIndex]) ? (int)$order[$displayIndex] : null;
    }

    /**
     * Map base index to display index using the order array.
     *
     * @param int $baseIndex
     * @param array<int,int> $order
     * @return int|null
     */
    protected function base_to_display_index($baseIndex, $order) {
        $displayIndex = array_search((int)$baseIndex, $order, true);
        return $displayIndex === false ? null : (int)$displayIndex;
    }
}
