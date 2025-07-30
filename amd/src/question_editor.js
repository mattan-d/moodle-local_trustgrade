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
      var questionIndex = questionItem.data("question-index")
      var cmid = questionItem.data("cmid")
      var questionData = {
        question: questionItem.find(".question-text-input").val(),
        type: questionItem.find(".question-type-input").val(),
        difficulty: questionItem.find(".question-difficulty-input").val(),
        points: Number.parseInt(questionItem.find(".question-points-input").val()),
        explanation: questionItem.find(".question-explanation-input").val(),
      }

      if (questionData.type === "multiple_choice") {
        questionData.options = []
        questionItem.find(".option-text-input").each(function () {
          questionData.options.push($(this).val())
        })
        questionData.correct_answer = Number.parseInt(questionItem.find(".correct-answer-radio:checked").val() || 0)
      } else if (questionData.type === "true_false") {
        questionData.correct_answer = questionItem.find('input[name^="tf_answer_"]:checked').val() === "true"
      }

      if (!questionData.question.trim()) {
        Str.get_string("question_text_required", "local_trustgrade").then((message) =>
          Notification.addNotification({ message: message, type: "error" }),
        )
        return
      }
      if (questionData.type === "multiple_choice" && questionData.options.some((opt) => !opt.trim())) {
        Str.get_string("all_options_required", "local_trustgrade").then((message) =>
          Notification.addNotification({ message: message, type: "error" }),
        )
        return
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
      ]).then((strings) => {
        var html = `<p><strong>Type:</strong> ${questionData.type.replace("_", " ")}</p>
                    <p><strong>Difficulty:</strong> ${questionData.difficulty}</p>
                    <p><strong>Points:</strong> ${questionData.points}</p>
                    <p><strong>${strings[0]}:</strong> ${questionData.question}</p>`
        if (questionData.options && questionData.options.length > 0) {
          html += "<p><strong>Options:</strong></p><ul>"
          questionData.options.forEach((option, index) => {
            var isCorrect = questionData.correct_answer === index ? ` <strong>(${strings[1]})</strong>` : ""
            html += `<li>${option}${isCorrect}</li>`
          })
          html += "</ul>"
        }
        if (questionData.explanation) {
          html += `<p><strong>${strings[2]}:</strong> ${questionData.explanation}</p>`
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
        html += `<div class="option-row"><div class="form-check">
                   <input class="form-check-input correct-answer-radio" type="radio" name="correct_answer_${questionIndex}" value="${i}" ${i === 0 ? "checked" : ""}>
                   <input type="text" class="form-control option-text-input" placeholder="Option ${String.fromCharCode(65 + i)}">
                 </div></div>`
      }
      html += "</div>"
      return html
    },

    generateTrueFalseOptions: (questionIndex) =>
      Promise.all([
        Str.get_string("correct_answer", "local_trustgrade"),
        Str.get_string("true", "local_trustgrade"),
        Str.get_string("false", "local_trustgrade"),
      ]).then((strings) => {
        return `<div class="true-false-options"><label>${strings[0]}:</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tf_answer_${questionIndex}" value="true" checked>
                    <label class="form-check-label">${strings[1]}</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tf_answer_${questionIndex}" value="false">
                    <label class="form-check-label">${strings[2]}</label>
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
