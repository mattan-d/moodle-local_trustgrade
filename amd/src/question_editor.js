// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question editor module for managing quiz questions in the question bank.
 *
 * @module     local_trustgrade/question_editor
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification", "core/str", "core/templates"], (
  $,
  Ajax,
  Notification,
  Str,
  Templates,
) => {
  var QuestionEditor = {
    cmid: 0,
    initialized: false,

    init: function (cmid) {
      this.cmid = cmid || 0
      if (this.initialized) return
      this.bindEvents()
      this.initialized = true
    },

    bindEvents: () => {
      $(document).off("click.questioneditor").off("change.questioneditor")

      $(document).on("click.questioneditor", "#add-new-question-btn", (e) => {
        e.preventDefault()
        QuestionEditor.addNewQuestion()
      })

      $(document).on("click.questioneditor", ".edit-question-btn", function (e) {
        e.preventDefault()
        var questionItem = $(this).closest(".editable-question-item")
        QuestionEditor.enterEditMode(questionItem)
      })
      $(document).on("click.questioneditor", ".cancel-edit-btn", function (e) {
        e.preventDefault()
        var questionItem = $(this).closest(".editable-question-item")
        QuestionEditor.exitEditMode(questionItem)
      })
      $(document).on("click.questioneditor", ".save-question-btn", function (e) {
        e.preventDefault()
        var questionItem = $(this).closest(".editable-question-item")
        QuestionEditor.saveQuestion(questionItem)
      })
      $(document).on("click.questioneditor", ".delete-question-btn", function (e) {
        e.preventDefault()
        var $button = $(this)
        if ($button.prop("disabled")) return
        $button.prop("disabled", true)
        var questionItem = $button.closest(".editable-question-item")
        setTimeout(() => QuestionEditor.deleteQuestion(questionItem, $button), 100)
      })
      $(document).on("change.questioneditor", ".question-type-input", function (e) {
        var questionItem = $(this).closest(".editable-question-item")
        var questionIndex = questionItem.data("question-index")
        QuestionEditor.updateOptionsSection($(this).val(), questionIndex)
      })
    },

    enterEditMode: (questionItem) => {
      questionItem.find(".question-display-mode").hide()
      questionItem.find(".question-edit-mode").show()
    },

    exitEditMode: (questionItem) => {
      questionItem.find(".question-edit-mode").hide()
      questionItem.find(".question-display-mode").show()
    },

    saveQuestion: (questionItem) => {
      console.log("[TrustGrade] saveQuestion called", questionItem)
      const $validationAlert = questionItem.find(".validation-alert")
      console.log("[TrustGrade] Found validation alert:", $validationAlert.length)
      $validationAlert.addClass("d-none")

      const questionIndex = questionItem.data("question-index")
      const cmid = questionItem.data("cmid")
      console.log("[TrustGrade] Question index:", questionIndex, "CM ID:", cmid)

      // Build new JSON shape
      const questionType = questionItem.find(".question-type-input").val()
      const questionText = questionItem.find(".question-text-input").val()
      const points = Number.parseInt(questionItem.find(".question-points-input").val(), 10)
      const blooms = questionItem.find(".question-blooms-input").val() || undefined
      const isMandatory = questionItem.find(".question-mandatory-input").is(":checked") ? 1 : 0

      console.log(
        "[TrustGrade] Form values - Type:",
        questionType,
        "Text:",
        questionText,
        "Points:",
        points,
        "Blooms:",
        blooms,
        "Mandatory:",
        isMandatory,
      )

      const questionData = {
        id: Number.parseInt(questionItem.data("question-id") || 0, 10) || undefined,
        type: questionType,
        text: questionText,
        options: [],
        is_mandatory: isMandatory,
        metadata: {
          points: isNaN(points) ? 0 : points,
          ...(blooms ? { blooms_level: blooms } : {}),
        },
      }

      // Build options per type
      if (questionType === "multiple_choice") {
        // Each .option-row contains:
        // - radio.correct-answer-radio (is_correct)
        // - input.option-text-input (text)
        // - textarea.option-explanation-input (explanation)
        questionItem.find(".option-row").each(function () {
          const $row = $(this)
          const isCorrect = $row.find(".correct-answer-radio").is(":checked")
          const optText = $row.find(".option-text-input").val() || ""
          const optExplanation = $row.find(".option-explanation-input").val() || ""
          questionData.options.push({
            text: optText,
            is_correct: !!isCorrect,
            explanation: optExplanation,
          })
        })
      } else if (questionType === "true_false") {
        // Expect two radios named tf_answer_{index} with values "true"/"false"
        const selectedVal = questionItem.find(`input[name^="tf_answer_${questionIndex}"]:checked`).val()
        // True row
        const $trueRow = questionItem.find('.true-false-options .tf-row[data-value="true"]')
        const trueText = $trueRow.find(".tf-label").text() || "True"
        const trueExpl = $trueRow.find(".option-explanation-input").val() || ""
        // False row
        const $falseRow = questionItem.find('.true-false-options .tf-row[data-value="false"]')
        const falseText = $falseRow.find(".tf-label").text() || "False"
        const falseExpl = $falseRow.find(".option-explanation-input").val() || ""
        questionData.options.push(
          { text: trueText, is_correct: selectedVal === "true", explanation: trueExpl },
          { text: falseText, is_correct: selectedVal === "false", explanation: falseExpl },
        )
      } else if (questionType === "short_answer") {
        // Minimum structure: one empty option with explanation field (optional for compatibility)
        questionData.options = []
      }

      console.log("[TrustGrade] Built question data:", questionData)

      if (!questionData.text || !questionData.text.trim()) {
        console.log("[TrustGrade] Validation failed: question text required")
        Str.get_string("question_text_required", "local_trustgrade").then((message) => {
          QuestionEditor.showInlineError(questionItem, message)
        })
        return
      }
      if (questionType === "multiple_choice") {
        const anyTextMissing = questionData.options.some((opt) => !(opt.text || "").trim())
        if (anyTextMissing) {
          console.log("[TrustGrade] Validation failed: all options required")
          Str.get_string("all_options_required", "local_trustgrade").then((message) => {
            QuestionEditor.showInlineError(questionItem, message)
          })
          return
        }
        const anyCorrect = questionData.options.some((opt) => opt.is_correct)
        if (!anyCorrect) {
          console.log("[TrustGrade] Validation failed: correct answer required")
          Str.get_string("correct_answer_required", "local_trustgrade").then((message) => {
            QuestionEditor.showInlineError(questionItem, message)
          })
          return
        }
      }

      console.log("[TrustGrade] Validation passed, calling AJAX")

      var $saveBtn = questionItem.find(".save-question-btn")
      $saveBtn.prop("disabled", true)

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_save_question",
          args: {
            cmid: cmid,
            question_index: questionIndex,
            question_data: JSON.stringify(questionData),
          },
        },
      ])

      console.log("[TrustGrade] AJAX call initiated")

      promises[0]
        .done((response) => {
          console.log("[TrustGrade] AJAX response received:", response)
          if (response.success) {
            QuestionEditor.updateQuestionDisplay(questionItem, questionData)
            QuestionEditor.exitEditMode(questionItem)
            Str.get_string("question_saved_success", "local_trustgrade").then((message) =>
              Notification.addNotification({ message: message, type: "success" }),
            )
          } else {
            console.log("[TrustGrade] Server returned error:", response.error)
            QuestionEditor.showInlineError(questionItem, response.error || "Failed to save question.")
          }
        })
        .fail((error) => {
          console.log("[TrustGrade] AJAX call failed:", error)
          const errorMessage = error.message || error.error || "An error occurred while saving the question."
          QuestionEditor.showInlineError(questionItem, errorMessage)
        })
        .always(() => $saveBtn.prop("disabled", false))
    },

    deleteQuestion: (questionItem, $button) => {
      Promise.all([
        Str.get_string("confirm_delete_question_title", "local_trustgrade"),
        Str.get_string("confirm_delete_question_message", "local_trustgrade"),
        Str.get_string("delete", "core"),
        Str.get_string("cancel", "core"),
      ]).then((strings) => {
        Notification.confirm(
          strings[0],
          strings[1],
          strings[2],
          strings[3],
          () => {
            var questionIndex = questionItem.data("question-index")
            var cmid = questionItem.data("cmid")
            QuestionEditor.performDelete(questionItem, questionIndex, cmid)
          },
          () => $button.prop("disabled", false),
        )
      })
    },

    performDelete: (questionItem, questionIndex, cmid) => {
      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_delete_question",
          args: { cmid: cmid, question_index: questionIndex },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            questionItem.fadeOut(300, function () {
              $(this).remove()
              QuestionEditor.reindexQuestions()
            })
            Str.get_string("question_deleted_success", "local_trustgrade").then((message) =>
              Notification.addNotification({ message: message, type: "success" }),
            )
          } else {
            Notification.addNotification({ message: response.error || "Failed to delete question.", type: "error" })
          }
        })
        .fail(Notification.exception)
    },

    updateQuestionDisplay: (questionItem, questionData) => {
      var displayMode = questionItem.find(".question-display-mode .question-content")
      Promise.all([
        Str.get_string("question", "local_trustgrade"),
        Str.get_string("correct", "local_trustgrade"),
        Str.get_string("explanation", "local_trustgrade"),
        Str.get_string("points", "local_trustgrade"),
        Str.get_string("mandatory", "local_trustgrade"),
        Str.get_string("blooms_level_label", "local_trustgrade"),
        Str.get_string("blooms_remembering", "local_trustgrade"),
        Str.get_string("blooms_understanding", "local_trustgrade"),
        Str.get_string("blooms_applying", "local_trustgrade"),
        Str.get_string("blooms_analyzing", "local_trustgrade"),
        Str.get_string("blooms_evaluating", "local_trustgrade"),
      ]).then((strings) => {
        const [qStr, correctStr, explStr, pointsStr, mandatoryStr, bloomsLabel, sRemember, sUnderstand, sApply, sAnalyze, sEvaluate] = strings
        const bloomsMap = {
          Remembering: sRemember,
          Remember: sRemember,
          Understanding: sUnderstand,
          Understand: sUnderstand,
          Applying: sApply,
          Apply: sApply,
          Analyzing: sAnalyze,
          Analyze: sAnalyze,
          Evaluating: sEvaluate,
          Evaluate: sEvaluate,
        }
        let html = ""
        if (questionData.metadata && (questionData.metadata.points != null || questionData.metadata.blooms_level)) {
          const pts = questionData.metadata.points != null ? `${pointsStr}: ${questionData.metadata.points}` : ""
          const rawBloom = questionData.metadata.blooms_level || ""
          const bloomStr = bloomsMap[rawBloom] || rawBloom
          const bloom = rawBloom ? ` | ${bloomsLabel}: ${bloomStr}` : ""
          html += `<p class="text-muted mb-2">${pts}${bloom}</p>`
        }
        html += `<p><strong>${qStr}:</strong> ${questionData.text || ""}</p>`
        if (questionData.is_mandatory) {
          html += `<p><strong>${mandatoryStr}:</strong> Yes</p>`
        }

        if (Array.isArray(questionData.options) && questionData.options.length > 0) {
          html += "<p><strong>Options:</strong></p><ul>"
          questionData.options.forEach((opt) => {
            const correctBadge = opt.is_correct ? ` <strong>(${correctStr})</strong>` : ""
            const safeOptText = opt.text || ""
            const safeExpl = opt.explanation
              ? ` <div class="option-explanation"><em>${explStr}:</em> ${opt.explanation}</div>`
              : ""
            html += `<li>${safeOptText}${correctBadge}${safeExpl}</li>`
          })
          html += "</ul>"
        }

        displayMode.html(html)
      })
    },

    updateOptionsSection: (questionType, questionIndex) => {
      var optionsSection = $(
        `.editable-question-item[data-question-index="${questionIndex}"] .question-options-section`,
      )
      if (questionType === "multiple_choice") {
        const context = {
          questionIndex: questionIndex,
          options: [
            { index: 0, text: "", isCorrect: true, explanation: "", optionLabel: "Option A" },
            { index: 1, text: "", isCorrect: false, explanation: "", optionLabel: "Option B" },
            { index: 2, text: "", isCorrect: false, explanation: "", optionLabel: "Option C" },
            { index: 3, text: "", isCorrect: false, explanation: "", optionLabel: "Option D" },
          ],
        }
        Templates.render("local_trustgrade/question_multiple_choice_options", context)
          .then((html) => optionsSection.html(html))
          .catch(Notification.exception)
      } else if (questionType === "true_false") {
        const context = {
          questionIndex: questionIndex,
          trueSelected: true,
          falseSelected: false,
          trueExplanation: "",
          falseExplanation: "",
        }
        Templates.render("local_trustgrade/question_true_false_options", context)
          .then((html) => optionsSection.html(html))
          .catch(Notification.exception)
      } else {
        optionsSection.html("")
      }
    },

    generateMultipleChoiceOptions: (questionIndex) => {
      console.warn("generateMultipleChoiceOptions is deprecated, use updateOptionsSection instead")
      return ""
    },

    generateTrueFalseOptions: (questionIndex) => {
      console.warn("generateTrueFalseOptions is deprecated, use updateOptionsSection instead")
      return Promise.resolve("")
    },

    reindexQuestions: () => {
      Str.get_string("question", "local_trustgrade").then((str) => {
        $("#question-bank-container .editable-question-item").each(function (index) {
          $(this).data("question-index", index)
          $(this)
            .find(".question-header h5")
            .text(`${str} ${index + 1}`)
        })
      })
    },

    reinitialize: function (cmid) {
      this.initialized = false
      this.init(cmid)
    },

    addNewQuestion: () => {
      const questionCount = $("#question-bank-container .editable-question-item").length
      const newIndex = questionCount

      // Create a blank question object
      const blankQuestion = {
        id: 0,
        type: "multiple_choice",
        text: "",
        options: [
          { text: "", is_correct: true, explanation: "" },
          { text: "", is_correct: false, explanation: "" },
          { text: "", is_correct: false, explanation: "" },
          { text: "", is_correct: false, explanation: "" },
        ],
        metadata: {
          points: 10,
          blooms_level: "",
        },
        is_mandatory: 0,
      }

      QuestionEditor.prepareEditFormContext(blankQuestion, newIndex)
        .then((editFormContext) => {
          return Templates.render("local_trustgrade/question_edit_form", editFormContext)
        })
        .then((editFormHtml) => {
          const context = {
            index: newIndex,
            cmid: QuestionEditor.cmid,
            questionNumber: newIndex + 1,
            editFormHtml: editFormHtml,
          }
          return Templates.render("local_trustgrade/question_new_item", context)
        })
        .then((html) => {
          // Insert before the add button section
          $(".add-question-section").before(html)

          const newQuestionItem = $(`.editable-question-item[data-question-index="${newIndex}"]`)
          newQuestionItem.find(".question-type-input").closest(".col-12.col-md-4").hide()

          QuestionEditor.updateOptionsSection("multiple_choice", newIndex)

          // Automatically enter edit mode for the new question
          QuestionEditor.enterEditMode(newQuestionItem)

          // Focus on the question text input
          newQuestionItem.find(".question-text-input").focus()
        })
        .catch(Notification.exception)
    },

    generateEditForm: (question, index) => {
      const context = QuestionEditor.prepareEditFormContext(question, index)
      return Templates.render("local_trustgrade/question_edit_form", context).catch((err) => {
        Notification.exception(err)
        return ""
      })
    },

    prepareEditFormContext: (question, index) => {
      const type = question.type || "multiple_choice"
      const text = question.text || ""
      const metadata = question.metadata || {}
      const points = metadata.points || 10
      const blooms = metadata.blooms_level || ""
      const isMandatory = question.is_mandatory || 0

      const bloomsLevels = [
        { value: "", key: "" },
        { value: "Remembering", key: "blooms_remembering" },
        { value: "Understanding", key: "blooms_understanding" },
        { value: "Applying", key: "blooms_applying" },
        { value: "Analyzing", key: "blooms_analyzing" },
        { value: "Evaluating", key: "blooms_evaluating" },
      ]

      // Get language strings for Bloom's levels
      const bloomsPromises = bloomsLevels.map((level) => {
        if (level.key === "") return Promise.resolve("-")
        return Str.get_string(level.key, "local_trustgrade").catch(() => level.value)
      })

      return Promise.all(bloomsPromises).then((bloomsLabels) => {
        const bloomsOptions = bloomsLevels.map((level, i) => ({
          value: level.value,
          label: bloomsLabels[i],
          selected: blooms === level.value,
        }))

        const options = []
        for (let i = 0; i < 4; i++) {
          const opt =
            question.options && question.options[i]
              ? question.options[i]
              : { text: "", is_correct: i === 0, explanation: "" }
          options.push({
            index: i,
            text: opt.text,
            isCorrect: opt.is_correct,
            explanation: opt.explanation,
            optionLabel: `Option ${String.fromCharCode(65 + i)}`,
          })
        }

        return {
          index: index,
          text: text,
          type: type,
          points: points,
          bloomsLevel: blooms,
          bloomsOptions: bloomsOptions,
          isMultipleChoice: type === "multiple_choice",
          isTrueFalse: type === "true_false",
          isShortAnswer: type === "short_answer",
          options: options,
          isMandatory: isMandatory,
        }
      })
    },

    showInlineError: (questionItem, message) => {
      let $validationAlert = questionItem.find(".question-edit-mode .validation-alert")

      // If not found in edit mode, try finding it anywhere in the question item
      if ($validationAlert.length === 0) {
        $validationAlert = questionItem.find(".validation-alert")
      }

      console.log("[TrustGrade] showInlineError - Alert found:", $validationAlert.length, "Message:", message)

      if ($validationAlert.length === 0) {
        console.error("[TrustGrade] Validation alert element not found in question item")
        // Fallback to notification
        Notification.addNotification({ message: message, type: "error" })
        return
      }

      const $validationMessage = $validationAlert.find(".validation-message")

      if ($validationMessage.length === 0) {
        console.error("[TrustGrade] Validation message element not found")
        // Fallback to notification
        Notification.addNotification({ message: message, type: "error" })
        return
      }

      $validationMessage.text(message)
      $validationAlert.removeClass("d-none")

      console.log("[TrustGrade] Alert displayed, scrolling into view")

      // Scroll to the alert
      $validationAlert[0].scrollIntoView({ behavior: "smooth", block: "nearest" })

      // Auto-hide after 5 seconds
      setTimeout(() => {
        $validationAlert.addClass("d-none")
      }, 5000)
    },
  }

  return QuestionEditor
})
