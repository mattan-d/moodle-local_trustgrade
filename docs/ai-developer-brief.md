Title: Update questions to new JSON schema (quiz, edit, display)

Summary
- Update the system to use ONLY the new questions JSON schema returned by:
  $result = question_generator::generate_questions_with_count($instructions, $questions_to_generate);
- The previous format (difficulty at question level, single explanation) is deprecated for this feature.
- Do NOT remove or change any functions that are not required for this change.

New JSON Schema (authoritative)
[
  {
    "id": 1,
    "type": "multiple_choice",
    "text": "Question text here",
    "options": [
      { "id": 1, "text": "Option A", "is_correct": true,  "explanation": "Why A is correct" },
      { "id": 2, "text": "Option B", "is_correct": false, "explanation": "Why B is incorrect" },
      { "id": 3, "text": "Option C", "is_correct": false, "explanation": "Why C is incorrect" },
      { "id": 4, "text": "Option D", "is_correct": false, "explanation": "Why D is incorrect" }
    ],
    "metadata": {
      "blooms_level": "Understand",
      "points": 10
    }
  }
]

Important schema notes
- difficulty: removed (do not display or persist a question-level difficulty).
- explanation: now per option (options[].explanation), not per question.
- points: located at metadata.points (use this for scoring).
- type: currently multiple_choice (assume single-correct for now). True/False, if present, should be modeled as two options with is_correct set accordingly and their own explanations.

Scope of updates
1) Edit mode (author/instructor UI)
   - Remove the Difficulty control entirely.
   - For multiple_choice:
     - Render each option with fields:
       - text (string)
       - is_correct (single-choice selection across options)
       - explanation (textarea per option)
     - Enforce at most one is_correct = true.
   - For True/False:
     - Two options ("True", "False"), each with its own explanation field and mutually exclusive is_correct.
   - Points input:
     - Bind to metadata.points (number).
   - Save payload should match the new schema exactly (no difficulty, explanations per option).

2) Display mode (read-only question view)
   - Show:
     - Type (from type)
     - Points (from metadata.points)
     - Question text (from text)
     - Options:
       - List each option’s text.
       - If correct, mark clearly (e.g., "(Correct)").
       - Option explanations are per-option; show them when available (e.g., under each option or via a collapsible).

3) Quiz (student flow, attempt and feedback)
   - Scoring:
     - Use options[].is_correct to determine correctness.
     - Award metadata.points if the selected option is the correct one.
   - Feedback:
     - After answering, show the explanation of the selected option (always).
     - If incorrect, also indicate the correct option and show its explanation.
   - Randomization (if enabled by existing settings):
     - Shuffle options while preserving which option is_correct.
   - Storage:
     - Persist and retrieve the question JSON as-is (no difficulty field).

Data handling and validation
- Required:
  - question.text must be non-empty.
  - options must be non-empty; for multiple_choice, recommend exactly 4 options.
  - Exactly one options[].is_correct must be true for single-correct MCQ.
- Optional:
  - options[].explanation (display when present).
  - metadata.blooms_level (display when present).
- Points:
  - metadata.points is required for scoring; default to 1 if missing (fallback) but prefer enforcing a value.

Files likely to touch (do not remove unrelated functions)
- JS: amd/src/question_editor.js (edit UI), amd/src/quiz.js (quiz flow), amd/src/trustgrade.js (if any preview/render logic), amd/src/grading.js (if scoring touches question shape).
- PHP renderers: classes/question_bank_renderer.php (display + edit markup), output renderers as needed.
- Storage: keep saving to existing tables (local_trustgrade_questions, local_trustgd_sub_questions) as JSON; update only the JSON shape.

Backward compatibility
- This task supports the NEW JSON pattern ONLY for (quiz, edit, display).
- Do not include or render the old difficulty or a single question-level explanation anywhere in these flows.

Acceptance criteria
- Edit mode:
  - No Difficulty field is visible.
  - Each option shows text, a selector for correct, and an Explanation textarea.
  - Saving produces JSON exactly matching the new schema (points in metadata, explanations per option).
- Display mode:
  - Renders Type, Points, Question text, and a list of options with "(Correct)" marker on the right option.
  - If an option has an explanation, it is viewable per option.
- Quiz:
  - Selecting the correct option awards metadata.points.
  - Feedback shows the selected option’s explanation; if incorrect, also show the correct option and its explanation.
  - Option order can be randomized without breaking correctness tracking.
- Regression:
  - No unrelated functions are removed or altered.

Notes
- Keep all existing functions unless they must be updated to support the new JSON shape.
- If you must add helpers (e.g., schema parsing/sanitization), add them without removing existing functionality.
- Ensure server/client sanitization (escape HTML) for text fields when rendering.
