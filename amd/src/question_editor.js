// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/ajax", "core/notification", "core/str"], ($, Ajax, Notification, Str) => {
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
      const questionIndex = questionItem.data("question-index")
      const cmid = questionItem.data("cmid")

      // Build new JSON shape
      const questionType = questionItem.find(".question-type-input").val()
      const questionText = questionItem.find(".question-text-input").val()
      const points = Number.parseInt(questionItem.find(".question-points-input").val(), 10)
      const blooms = questionItem.find(".question-blooms-input").val() || undefined

      const questionData = {
        id: Number.parseInt(questionItem.data("question-id") || 0, 10) || undefined,
        type: questionType,
        text: questionText,
        options: [],
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

      // Validation
      if (!questionData.text || !questionData.text.trim()) {
        Str.get_string("question_text_required", "local_trustgrade").then((message) =>
          Notification.addNotification({ message, type: "error" }),
        )
        return
      }
      if (questionType === "multiple_choice") {
        const anyTextMissing = questionData.options.some((opt) => !(opt.text || "").trim())
        if (anyTextMissing) {
          Str.get_string("all_options_required", "local_trustgrade").then((message) =>
            Notification.addNotification({ message, type: "error" }),
          )
          return
        }
        const anyCorrect = questionData.options.some((opt) => opt.is_correct)
        if (!anyCorrect) {
          Str.get_string("correct_answer_required", "local_trustgrade").then((message) =>
            Notification.addNotification({ message, type: "error" }),
          )
          return
        }
      }

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

      promises[0]
        .done((response) => {
          if (response.success) {
            QuestionEditor.updateQuestionDisplay(questionItem, questionData)
            QuestionEditor.exitEditMode(questionItem)
            Str.get_string("question_saved_success", "local_trustgrade").then((message) =>
              Notification.addNotification({ message: message, type: "success" }),
            )
          } else {
            Notification.addNotification({ message: response.error || "Failed to save question.", type: "error" })
          }
        })
        .fail(Notification.exception)
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
        Str.get_string("type", "local_trustgrade"),
        Str.get_string("options", "local_trustgrade"),
      ]).then((strings) => {
        const [qStr, correctStr, explStr, pointsStr, typeStr, optionsStr] = strings

        let html = `<p class="mb-1"><strong>${typeStr}:</strong> ${$("<div>")
          .text(String(questionData.type || "").replace("_", " "))
          .html()}</p>`

        // Display metadata if available
        if (questionData.metadata && (questionData.metadata.points != null || questionData.metadata.blooms_level)) {
          const metaBits = []
          if (questionData.metadata.points != null) {
            metaBits.push(`${pointsStr}: ${questionData.metadata.points}`)
          }
          if (questionData.metadata.blooms_level) {
            metaBits.push(`Bloom's: ${$("<div>").text(questionData.metadata.blooms_level).html()}`)
          }
          html += `<p class="text-muted mb-2">${metaBits.join(" | ")}</p>`
        }

        // Display question text
        html += `<p><strong>${qStr}:</strong> ${$("<div>")
          .text(questionData.text || "")
          .html()}</p>`

        // Display options if available
        if (Array.isArray(questionData.options) && questionData.options.length > 0) {
          html += `<div class="mt-3"><p class="mb-2"><strong>${optionsStr}:</strong></p><ul class="mb-0">`
          questionData.options.forEach((opt) => {
            const correctBadge = opt.is_correct ? ` <strong>(${correctStr})</strong>` : ""
            const safeOptText = $("<div>")
              .text(opt.text || "")
              .html()
            const safeExpl = opt.explanation
              ? ` <div class="option-explanation text-muted small mt-1"><em>${explStr}:</em> ${$("<div>").text(opt.explanation).html()}</div>`
              : ""
            html += `<li class="mb-1">${safeOptText}${correctBadge}${safeExpl}</li>`
          })
          html += "</ul></div>"
        }

        displayMode.html(html)
      })
    },

    updateOptionsSection: (questionType, questionIndex) => {
      var optionsSection = $(
        `.editable-question-item[data-question-index="${questionIndex}"] .question-options-section`,
      )
      if (questionType === "multiple_choice") {
        optionsSection.html(QuestionEditor.generateMultipleChoiceOptions(questionIndex))
      } else if (questionType === "true_false") {
        QuestionEditor.generateTrueFalseOptions(questionIndex).then((html) => optionsSection.html(html))
      } else {
        optionsSection.html("")
      }
    },

    generateMultipleChoiceOptions: (questionIndex) => {
      var html = '<div class="multiple-choice-options"><label>Options:</label>'
      for (var i = 0; i < 4; i++) {
        html += `<div class="option-row">
    <div class="form-check">
      <input class="form-check-input correct-answer-radio" type="radio" name="correct_answer_${questionIndex}" value="${i}" ${i === 0 ? "checked" : ""}>
      <input type="text" class="form-control option-text-input" placeholder="Option ${String.fromCharCode(65 + i)}">
    </div>
    <div class="form-group mt-2">
      <label class="form-label">Explanation</label>
      <textarea class="form-control option-explanation-input" rows="2" placeholder="Enter explanation for this option"></textarea>
    </div>
  </div>`
      }
      html += "</div>"
      return html
    },

    generateTrueFalseOptions: (questionIndex) =>
      Promise.all([
        Str.get_string("correct_answer", "local_trustgrade"),
        Str.get_string("true", "local_trustgrade"),
        Str.get_string("false", "local_trustgrade"),
        Str.get_string("explanation", "local_trustgrade"),
      ]).then((strings) => {
        const [correctAns, trueStr, falseStr, explanationStr] = strings
        return `<div class="true-false-options">
    <label>${correctAns}:</label>
    <div class="tf-row" data-value="true">
      <div class="form-check">
        <input class="form-check-input" type="radio" name="tf_answer_${questionIndex}" value="true" checked>
        <label class="form-check-label tf-label">${trueStr}</label>
      </div>
      <div class="form-group mt-2">
        <label class="form-label">${explanationStr}</label>
        <textarea class="form-control option-explanation-input" rows="2" placeholder="${explanationStr} for ${trueStr}"></textarea>
      </div>
    </div>
    <div class="tf-row mt-3" data-value="false">
      <div class="form-check">
        <input class="form-check-input" type="radio" name="tf_answer_${questionIndex}" value="false">
        <label class="form-check-label tf-label">${falseStr}</label>
      </div>
      <div class="form-group mt-2">
        <label class="form-label">${explanationStr}</label>
        <textarea class="form-control option-explanation-input" rows="2" placeholder="${explanationStr} for ${falseStr}"></textarea>
      </div>
    </div>
  </div>`
      }),

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
  }

  return QuestionEditor
})
