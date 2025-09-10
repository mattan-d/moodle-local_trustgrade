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
    },

    generateQuestions: function () {
      var questionsCount = $("#questions-count").val()

      $("#generate-new-questions").prop("disabled", true)
      $("#generation-loading").show()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_get_assignment_instructions",
          args: {
            cmid: this.cmid,
          },
        },
      ])

      promises[0]
        .then((instructionsResponse) => {
          if (!instructionsResponse.success) {
            throw new Error(instructionsResponse.error || "Failed to get assignment instructions")
          }

          return Ajax.call([
            {
              methodname: "local_trustgrade_update_quiz_setting",
              args: {
                cmid: this.cmid,
                setting_name: "questions_to_generate",
                setting_value: Number.parseInt(questionsCount),
              },
            },
          ])
        })
        .then((updateResponse) => {
          if (!updateResponse[0].success) {
            throw new Error(updateResponse[0].error || "Failed to update question count")
          }

          return Ajax.call([
            {
              methodname: "local_trustgrade_generate_questions",
              args: {
                cmid: this.cmid,
                instructions: "", // Will be fetched from assignment by the web service
                intro_itemid: 0,
                intro_attachments_itemid: 0,
              },
            },
          ])
        })
        .then((response) => {
          $("#generation-loading").hide()
          $("#generate-new-questions").prop("disabled", false)

          if (response[0].success) {
            Notification.addNotification({
              message: questionsCount + " " + M.util.get_string("questions_generated_successfully", "local_trustgrade"),
              type: "success",
            })
            // Reload the page to show new questions
            window.location.reload()
          } else {
            Notification.addNotification({
              message: response[0].error || M.util.get_string("error_generating_questions", "local_trustgrade"),
              type: "error",
            })
          }
        })
        .catch((error) => {
          $("#generation-loading").hide()
          $("#generate-new-questions").prop("disabled", false)

          Notification.addNotification({
            message: error.message || M.util.get_string("error_generating_questions", "local_trustgrade"),
            type: "error",
          })
        })
    },
  }

  return QuestionBank
})
