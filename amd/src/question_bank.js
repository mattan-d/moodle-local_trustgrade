// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/str", "core/ajax", "core/notification"], ($, Str, Ajax, Notification) => {
  var QuestionBank = {
    init: function (cmid) {
      this.cmid = cmid || 0
      this.bindEvents()
    },

    bindEvents: function () {
      // Generate new questions button
      $("#generate-new-questions").on("click", () => {
        this.generateQuestions()
      })

      // Edit question buttons
      $(".edit-question").on("click", (e) => {
        var questionId = $(e.currentTarget).data("question-id")
        this.editQuestion(questionId)
      })

      // Delete question buttons
      $(".delete-question").on("click", (e) => {
        var questionId = $(e.currentTarget).data("question-id")
        this.deleteQuestion(questionId)
      })
    },

    generateQuestions: function () {
      var questionsCount = $("#questions-count").val()

      $("#generate-new-questions").prop("disabled", true)
      $("#generation-loading").show()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_generate_questions",
          args: {
            cmid: this.cmid,
            count: Number.parseInt(questionsCount),
          },
        },
      ])

      promises[0]
        .then((response) => {
          $("#generation-loading").hide()
          $("#generate-new-questions").prop("disabled", false)

          if (response.success) {
            Notification.addNotification({
              message: questionsCount + " " + M.util.get_string("questions_generated_successfully", "local_trustgrade"),
              type: "success",
            })
            // Reload the page to show new questions
            window.location.reload()
          } else {
            Notification.addNotification({
              message: response.error || M.util.get_string("error_generating_questions", "local_trustgrade"),
              type: "error",
            })
          }
        })
        .catch((error) => {
          $("#generation-loading").hide()
          $("#generate-new-questions").prop("disabled", false)

          Notification.addNotification({
            message: M.util.get_string("error_generating_questions", "local_trustgrade"),
            type: "error",
          })
        })
    },

    editQuestion: function (questionId) {
      window.location.href =
        M.cfg.wwwroot + "/local/trustgrade/edit_question.php?id=" + questionId + "&cmid=" + this.cmid
    },

    deleteQuestion: function (questionId) {
      Str.get_string("confirm_delete_question", "local_trustgrade")
        .then((confirmText) => {
          if (confirm(confirmText)) {
            this.performDeleteQuestion(questionId)
          }
        })
        .catch(() => {
          if (confirm("Are you sure you want to delete this question?")) {
            this.performDeleteQuestion(questionId)
          }
        })
    },

    performDeleteQuestion: function (questionId) {
      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_delete_question",
          args: {
            question_id: questionId,
          },
        },
      ])

      promises[0]
        .then((response) => {
          if (response.success) {
            $('[data-question-id="' + questionId + '"]').fadeOut(() => {
              $(this).remove()
            })
            Notification.addNotification({
              message: M.util.get_string("question_deleted_successfully", "local_trustgrade"),
              type: "success",
            })
          } else {
            Notification.addNotification({
              message: response.error || M.util.get_string("error_deleting_question", "local_trustgrade"),
              type: "error",
            })
          }
        })
        .catch((error) => {
          Notification.addNotification({
            message: M.util.get_string("error_deleting_question", "local_trustgrade"),
            type: "error",
          })
        })
    },
  }

  return QuestionBank
})
