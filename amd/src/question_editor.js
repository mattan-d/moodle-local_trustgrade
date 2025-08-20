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
      ]).then((strings) => {
        const [qStr, correctStr, explStr, pointsStr] = strings
        let html = `<p><strong>Type:</strong> ${String(questionData.type || "").replace("_", " ")}</p>`
        if (questionData.metadata && (questionData.metadata.points != null || questionData.metadata.blooms_level)) {
          const pts = questionData.metadata.points != null ? `${pointsStr}: ${questionData.metadata.points}` : ""
          const bloom = questionData.metadata.blooms_level ? ` | Bloom's: ${questionData.metadata.blooms_level}` : ""
          html += `<p>${pts}${bloom}</p>`
        }
        html += `<p><strong>${qStr}:</strong> ${questionData.text || ""}</p>`

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
      }

      // Generate HTML for the new question using the same structure as existing questions
      Str.get_string("question", "local_trustgrade").then((questionStr) => {
        let html = `<div class="editable-question-item card mb-4" data-question-index="${newIndex}" data-cmid="${QuestionEditor.cmid}" data-question-id="0">`

        // Header
        html += '<div class="card-header d-flex align-items-center justify-content-between">'
        html += `<h5 class="mb-0">${questionStr} ${newIndex + 1}</h5>`
        html += '<div class="question-controls d-flex gap-2">'
        html += '<button type="button" class="btn btn-sm btn-outline-secondary edit-question-btn">'
        html += '<i class="fa fa-edit" aria-hidden="true"></i> Edit</button>'
        html += '<button type="button" class="btn btn-sm btn-outline-danger delete-question-btn">'
        html += '<i class="fa fa-trash" aria-hidden="true"></i> Delete</button>'
        html += "</div></div>"

        // Body with display and edit modes
        html += '<div class="card-body">'
        html += '<div class="question-display-mode">'
        html += '<div class="question-content">'
        html += '<p class="mb-1"><strong>Type:</strong> Multiple Choice</p>'
        html += '<p class="text-muted mb-2">Points: 10</p>'
        html += "<p><strong>Question:</strong> <em>Click Edit to add question text</em></p>"
        html += '<div class="mt-3"><p class="mb-2"><strong>Options:</strong></p>'
        html += '<ul class="mb-0"><li class="mb-1"><em>Click Edit to add options</em></li></ul></div>'
        html += "</div></div>"

        html += '<div class="question-edit-mode" style="display: none;">'
        html += QuestionEditor.generateEditForm(blankQuestion, newIndex)
        html += "</div>"

        html += "</div></div>"

        // Insert before the add button section
        $(".add-question-section").before(html)

        // Automatically enter edit mode for the new question
        const newQuestionItem = $(`.editable-question-item[data-question-index="${newIndex}"]`)
        QuestionEditor.enterEditMode(newQuestionItem)

        // Focus on the question text input
        newQuestionItem.find(".question-text-input").focus()
      })
    },

    generateEditForm: (question, index) => {
      const type = question.type || "multiple_choice"
      const text = question.text || ""
      const metadata = question.metadata || {}
      const points = metadata.points || 10
      const blooms = metadata.blooms_level || ""

      let html = '<div class="question-edit-form container-fluid px-0">'

      // Question text
      html += '<div class="form-group mb-3">'
      html += `<label for="question_text_${index}" class="form-label">Question Text:</label>`
      html += `<textarea class="form-control question-text-input" id="question_text_${index}" rows="3" placeholder="Enter question text">${text}</textarea>`
      html += "</div>"

      // Type, Points, Bloom's row
      html += '<div class="row g-3">'
      html += '<div class="col-12 col-md-4">'
      html += '<div class="form-group">'
      html += `<label for="question_type_${index}" class="form-label">Type:</label>`
      html += `<select class="form-control question-type-input" id="question_type_${index}">`
      html += `<option value="multiple_choice" selected>Multiple Choice</option>`
      html += "</select></div></div>"

      html += '<div class="col-12 col-md-4">'
      html += '<div class="form-group">'
      html += `<label for="question_points_${index}" class="form-label">Points:</label>`
      html += `<input type="number" class="form-control question-points-input" id="question_points_${index}" value="${points}" min="0" max="100" />`
      html += "</div></div>"

      html += '<div class="col-12 col-md-4">'
      html += '<div class="form-group">'
      html += `<label for="question_blooms_${index}" class="form-label">Bloom's Level:</label>`
      html += `<select class="form-control question-blooms-input" id="question_blooms_${index}">`
      const levels = ["", "Remember", "Understand", "Apply", "Analyze", "Evaluate", "Create"]
      levels.forEach((level) => {
        const sel = blooms === level ? "selected" : ""
        const label = level === "" ? "-" : level
        html += `<option value="${level}" ${sel}>${label}</option>`
      })
      html += "</select></div></div></div>"

      // Options section
      html += '<div class="question-options-section mt-4">'
      html += '<div class="d-flex align-items-center justify-content-between mb-2">'
      html += '<h6 class="mb-0">Options</h6></div>'
      html += '<div class="row text-muted small fw-semibold mb-1">'
      html += '<div class="col-12 col-md-1">Correct</div>'
      html += '<div class="col-12 col-md-5">Option Text</div>'
      html += '<div class="col-12 col-md-6">Explanation</div></div>'

      // Generate 4 option rows
      for (let i = 0; i < 4; i++) {
        const opt = question.options[i] || { text: "", is_correct: i === 0, explanation: "" }
        const checked = opt.is_correct ? "checked" : ""

        html += '<div class="row align-items-start gy-2 gx-3 mb-2 option-row">'
        html += '<div class="col-12 col-md-1 d-flex align-items-start pt-2">'
        html += `<input class="form-check-input mt-0 correct-answer-radio" type="radio" name="correct_answer_${index}" value="${i}" ${checked}>`
        html += "</div>"
        html += '<div class="col-12 col-md-5">'
        html += `<input type="text" class="form-control option-text-input" placeholder="Option ${String.fromCharCode(65 + i)}" value="${opt.text}">`
        html += "</div>"
        html += '<div class="col-12 col-md-6">'
        html += `<textarea class="form-control option-explanation-input" rows="2" placeholder="Explanation">${opt.explanation}</textarea>`
        html += "</div></div>"
      }

      html += "</div>"

      // Save/Cancel buttons
      html += '<div class="question-edit-buttons mt-4 d-flex gap-2">'
      html += '<button type="button" class="btn btn-primary save-question-btn">Save Changes</button>'
      html += '<button type="button" class="btn btn-secondary cancel-edit-btn">Cancel</button>'
      html += "</div></div>"

      return html
    },
  }

  return QuestionEditor
})
