<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Normalize question objects to the legacy structure expected by the report renderer.
 * - Ensure $q->question is populated (from $q->text if necessary)
 * - For multiple_choice:
 *     - Convert $q->options from array of objects to array of strings (using ->text if present)
 *     - Ensure $q->correct_answer is an int; infer from first option with truthy ->correct or ->isCorrect if missing
 *
 * This function does not remove any fields; it only adds/derives legacy-compatible ones.
 *
 * @param array|\stdClass[] $questions
 * @return array normalized questions
 */
if (!function_exists('local_trustgrade_normalize_questions_for_report')) {
    function local_trustgrade_normalize_questions_for_report($questions) {
        if (!is_array($questions)) {
            $questions = (array)$questions;
        }

        $normalized = [];
        foreach ($questions as $q) {
            if (is_array($q)) {
                $q = (object)$q;
            }
            if (!$q instanceof stdClass) {
                // Skip invalid entries gracefully.
                continue;
            }

            // Map new JSON field 'text' to legacy 'question' if needed.
            if (!isset($q->question) && isset($q->text)) {
                $q->question = $q->text;
            }

            // Handle multiple choice options normalization.
            if (isset($q->type) && $q->type === 'multiple_choice' && isset($q->options) && is_array($q->options)) {
                $options = $q->options;
                $first = $options[0] ?? null;
                $areobjects = is_object($first);

                if ($areobjects) {
                    // Extract visible option text for the renderer.
                    $flatOptions = [];
                    foreach ($options as $idx => $opt) {
                        if (is_object($opt)) {
                            $flatOptions[$idx] = isset($opt->text) ? (string)$opt->text : (string)json_encode($opt);
                        } else {
                            $flatOptions[$idx] = (string)$opt;
                        }
                    }
                    $q->options = array_values($flatOptions);

                    // Infer correct_answer if missing and any option marks correct/isCorrect.
                    if (!isset($q->correct_answer)) {
                        $correctIndex = null;
                        foreach ($options as $idx => $opt) {
                            if (is_object($opt)) {
                                $isCorrect = false;
                                if ((isset($opt->correct) && $opt->correct) || (isset($opt->isCorrect) && $opt->isCorrect)) {
                                    $isCorrect = true;
                                }
                                if ($isCorrect) {
                                    $correctIndex = $idx;
                                    break;
                                }
                            }
                        }
                        if ($correctIndex !== null) {
                            $q->correct_answer = (int)$correctIndex;
                        }
                    }
                } else {
                    // Keep as-is, but ensure correct_answer is an int if present.
                    if (isset($q->correct_answer)) {
                        $q->correct_answer = (int)$q->correct_answer;
                    }
                }
            }

            // Ensure correct_answer is int when present (defensive).
            if (isset($q->correct_answer) && $q->correct_answer !== null) {
                $q->correct_answer = (int)$q->correct_answer;
            }

            $normalized[] = $q;
        }

        return $normalized;
    }
}

/**
 * Resolve a user's answer into the numeric option index expected by the renderer,
 * based on the normalized question data.
 *
 * Supported answer shapes for multiple_choice:
 * - integer index (e.g., 1)
 * - numeric string (e.g., "1")
 * - string matching the option text (e.g., "Option A")
 * - object with fields like { index | optionIndex | selectedIndex | value | id } (numeric)
 * - object with text-like fields { text | label | option | answer } matching option text
 *
 * For non-multiple choice, it returns the original answer unchanged.
 *
 * @param \stdClass|array $question Normalized question (options are strings)
 * @param mixed $answer Raw stored answer
 * @return mixed Integer index when resolvable for MCQ, otherwise original answer
 */
if (!function_exists('local_trustgrade_resolve_answer_index')) {
    function local_trustgrade_resolve_answer_index($question, $answer) {
        if (is_array($question)) {
            $question = (object)$question;
        }
        if (!$question instanceof stdClass) {
            return $answer;
        }

        if (!isset($question->type) || $question->type !== 'multiple_choice') {
            return $answer;
        }

        $options = [];
        if (isset($question->options) && is_array($question->options)) {
            $options = $question->options;
        }

        // 1) Already an integer index.
        if (is_int($answer)) {
            return $answer;
        }

        // 2) Numeric string like "2".
        if (is_string($answer) && is_numeric(trim($answer)) && ctype_digit((string)trim($answer))) {
            return (int)trim($answer);
        }

        // 3) A string that matches an option's text.
        if (is_string($answer)) {
            $needle = trim($answer);
            $ciMatch = null;
            foreach ($options as $idx => $opt) {
                $optText = is_string($opt) ? $opt : (is_object($opt) && isset($opt->text) ? (string)$opt->text : (string)$opt);
                $optTextTrim = trim($optText);
                if ($optTextTrim === $needle) {
                    return (int)$idx;
                }
                if (mb_strtolower($optTextTrim) === mb_strtolower($needle)) {
                    $ciMatch = $idx;
                }
            }
            if ($ciMatch !== null) {
                return (int)$ciMatch;
            }
        }

        // 4) Object with known index-like fields or text-like fields.
        if (is_object($answer)) {
            // Index-like fields
            foreach (['index', 'optionIndex', 'selectedIndex', 'i'] as $k) {
                if (isset($answer->$k) && is_numeric($answer->$k)) {
                    return (int)$answer->$k;
                }
            }
            foreach (['value', 'id'] as $k) {
                if (isset($answer->$k) && is_numeric($answer->$k)) {
                    return (int)$answer->$k;
                }
            }

            // Text-like fields
            foreach (['text', 'label', 'option', 'answer'] as $k) {
                if (isset($answer->$k) && is_string($answer->$k)) {
                    $needle = trim($answer->$k);
                    $ciMatch = null;
                    foreach ($options as $idx => $opt) {
                        $optText = is_string($opt) ? $opt : (is_object($opt) && isset($opt->text) ? (string)$opt->text : (string)$opt);
                        $optTextTrim = trim($optText);
                        if ($optTextTrim === $needle) {
                            return (int)$idx;
                        }
                        if (mb_strtolower($optTextTrim) === mb_strtolower($needle)) {
                            $ciMatch = $idx;
                        }
                    }
                    if ($ciMatch !== null) {
                        return (int)$ciMatch;
                    }
                }
            }
        }

        // Fallback: return raw answer (renderer will show raw and may mark incorrect).
        return $answer;
    }
}

/**
 * Normalize answers array to align with normalized questions.
 * Produces a simple array where multiple_choice answers are numeric indexes when possible.
 *
 * @param array|\stdClass[] $questions Normalized questions
 * @param array|\stdClass[]|string $answers Raw answers (array or JSON)
 * @return array Normalized answers aligned by index
 */
if (!function_exists('local_trustgrade_normalize_answers_for_report')) {
    function local_trustgrade_normalize_answers_for_report($questions, $answers) {
        if (!is_array($questions)) {
            $questions = (array)$questions;
        }

        // Decode JSON if needed.
        if (is_string($answers)) {
            $decoded = json_decode($answers);
            if (json_last_error() === JSON_ERROR_NONE) {
                $answers = $decoded;
            }
        }

        if (!is_array($answers)) {
            $answers = (array)$answers;
        }

        // Re-index for positional access.
        $questions = array_values($questions);
        $answers = array_values($answers);

        $normalized = [];
        foreach ($questions as $i => $q) {
            $ans = $answers[$i] ?? null;
            $normalized[$i] = local_trustgrade_resolve_answer_index($q, $ans);
        }

        return $normalized;
    }
}

// Get parameters
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Require login
require_login();

// Set up page context
if ($cmid) {
   $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
   $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
   $context = context_module::instance($cm->id);
   require_capability('mod/assign:grade', $context);
   $PAGE->set_cm($cm, $course);
   $PAGE->set_context($context);
} else if ($courseid) {
   $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
   $context = context_course::instance($course->id);
   require_capability('moodle/course:manageactivities', $context);
   $PAGE->set_course($course);
   $PAGE->set_context($context);
} else {
   $context = context_system::instance();
   require_capability('moodle/site:config', $context);
   $PAGE->set_context($context);
}

// Set up page
$PAGE->set_url('/local/trustgrade/quiz_report.php', array('cmid' => $cmid, 'courseid' => $courseid));
$PAGE->set_title(get_string('quiz_report', 'local_trustgrade'));
$PAGE->set_heading(get_string('quiz_report', 'local_trustgrade'));
$PAGE->set_pagelayout('admin');

// Add CSS for grading interface
$PAGE->requires->css('/local/trustgrade/grading_styles.css');

// Get completed quiz sessions
$sessions = [];
try {
   if ($cmid) {
       // Get sessions for specific assignment
       $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_assignment($cmid);
   } else if ($courseid) {
       // Get sessions for specific course
       $sessions = \local_trustgrade\quiz_session::get_completed_sessions_for_course($courseid);
   } else {
       // Get all completed sessions
       $sessions = \local_trustgrade\quiz_session::get_all_completed_sessions();
   }

   // Normalize questions and answers in each session to support new JSON shape and ensure correct grading.
   foreach ($sessions as $s) {
       // Normalize questions
       if (isset($s->questions_data)) {
           $qdata = $s->questions_data;

           // Decode if JSON string
           if (is_string($qdata)) {
               $decoded = json_decode($qdata);
               if (json_last_error() === JSON_ERROR_NONE) {
                   $qdata = $decoded;
               }
           }

           $normalizedQuestions = local_trustgrade_normalize_questions_for_report($qdata ?? []);
           $s->questions_data = $normalizedQuestions;

           // Normalize answers aligned with normalized questions
           if (isset($s->answers_data)) {
               $s->answers_data = local_trustgrade_normalize_answers_for_report($normalizedQuestions, $s->answers_data);
           }
       }
   }
} catch (Exception $e) {
   debugging('Error loading quiz sessions: ' . $e->getMessage(), DEBUG_DEVELOPER);
   $sessions = [];
}

// Output page
echo $OUTPUT->header();

// Page heading and description
echo $OUTPUT->heading(get_string('quiz_report', 'local_trustgrade'));

if ($cmid) {
   echo html_writer::tag('p', 
       get_string('quiz_report_assignment_desc', 'local_trustgrade', $cm->name),
       ['class' => 'lead']
   );
} else if ($courseid) {
   echo html_writer::tag('p', 
       get_string('quiz_report_course_desc', 'local_trustgrade', $course->fullname),
       ['class' => 'lead']
   );
} else {
   echo html_writer::tag('p', 
       get_string('quiz_report_all_desc', 'local_trustgrade'),
       ['class' => 'lead']
   );
}

// Render the report
$renderer = $PAGE->get_renderer('local_trustgrade', 'report');
echo $renderer->render_quiz_report($sessions, $cmid);

// Back navigation
if ($cmid) {
   $back_url = new moodle_url('/mod/assign/view.php', array('id' => $cmid));
   echo html_writer::div(
       html_writer::link($back_url, get_string('back_to_assignment', 'local_trustgrade'), 
           ['class' => 'btn btn-secondary']),
       'mt-3'
   );
} else if ($courseid) {
   $back_url = new moodle_url('/course/view.php', array('id' => $courseid));
   echo html_writer::div(
       html_writer::link($back_url, get_string('back_to_course', 'local_trustgrade'), 
           ['class' => 'btn btn-secondary']),
       'mt-3'
   );
}

echo $OUTPUT->footer();
