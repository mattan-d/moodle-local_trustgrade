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
 * Question bank interface module for managing quiz questions.
 *
 * @module     local_trustgrade/question_bank
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const define = window.define // Declare the define variable
const M = window.M // Declare the M variable

define(["jquery", "core/str", "core/ajax", "core/notification"], ($, Str, Ajax, Notification) => {
  var QuestionBank = {
    strings: {},

    init: function (cmid) {
      this.cmid = cmid || 0
      this.loadStrings().then(() => {
        this.bindEvents()
      })
    },

    loadStrings: function () {
      const stringKeys = [
        { key: "mandatory_question", component: "local_trustgrade" },
        { key: "make_mandatory", component: "local_trustgrade" },
        { key: "remove_mandatory", component: "local_trustgrade" },
        { key: "question_marked_mandatory", component: "local_trustgrade" },
        { key: "question_unmarked_mandatory", component: "local_trustgrade" },
        { key: "error_updating_question", component: "local_trustgrade" },
      ]

      return Str.get_strings(stringKeys).then((strings) => {
        this.strings.mandatory_question = strings[0]
        this.strings.make_mandatory = strings[1]
        this.strings.remove_mandatory = strings[2]
        this.strings.question_marked_mandatory = strings[3]
        this.strings.question_unmarked_mandatory = strings[4]
        this.strings.error_updating_question = strings[5]
        return strings
      })
    },

    bindEvents: function () {
      // Generate new questions button
      $("#generate-new-questions").on("click", () => {
        this.generateQuestions()
      })

      $(document).on("click", ".toggle-mandatory-btn", (e) => {
        e.preventDefault()
        const $btn = $(e.currentTarget)
        const $questionItem = $btn.closest(".editable-question-item")
        const questionId = Number.parseInt($btn.data("question-id"))
        const currentMandatory = Number.parseInt($btn.data("mandatory")) === 1
        const newMandatory = !currentMandatory

        QuestionBank.toggleMandatoryQuestion(questionId, newMandatory, $questionItem)
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

    toggleMandatoryQuestion: function (questionId, isMandatory, $questionItem) {
      const $btn = $questionItem.find(".toggle-mandatory-btn")

      // Disable button during request
      $btn.prop("disabled", true)

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_toggle_mandatory_question",
          args: {
            questionid: questionId,
            is_mandatory: isMandatory,
          },
        },
      ])

      promises[0]
        .then((response) => {
          if (response.success) {
            const $mandatoryControls = $questionItem.find(".mandatory-controls")

            if (response.is_mandatory) {
              // Show badge and "Remove" button
              $mandatoryControls.html(
                '<span class="badge bg-danger mandatory-badge">' +
                  this.strings.mandatory_question +
                  "</span>" +
                  '<button type="button" class="btn btn-sm btn-outline-secondary toggle-mandatory-btn" data-mandatory="1" data-question-id="' +
                  questionId +
                  '" title="' +
                  this.strings.remove_mandatory +
                  '">' +
                  '<i class="fa fa-times-circle" aria-hidden="true"></i> ' +
                  this.strings.remove_mandatory +
                  "</button>",
              )
            } else {
              // Show "Make mandatory" button only
              $mandatoryControls.html(
                '<button type="button" class="btn btn-sm btn-outline-primary toggle-mandatory-btn" data-mandatory="0" data-question-id="' +
                  questionId +
                  '" title="' +
                  this.strings.make_mandatory +
                  '">' +
                  '<i class="fa fa-star" aria-hidden="true"></i> ' +
                  this.strings.make_mandatory +
                  "</button>",
              )
            }

            // Also update the checkbox in edit mode
            $questionItem.find(".question-mandatory-input").prop("checked", response.is_mandatory)

            Notification.addNotification({
              message: response.is_mandatory
                ? this.strings.question_marked_mandatory
                : this.strings.question_unmarked_mandatory,
              type: "success",
            })
          } else {
            Notification.addNotification({
              message: response.error || this.strings.error_updating_question,
              type: "error",
            })
          }

          $btn.prop("disabled", false)
        })
        .catch((error) => {
          Notification.addNotification({
            message: this.strings.error_updating_question,
            type: "error",
          })
          $btn.prop("disabled", false)
        })
    },
  }

  return QuestionBank
})
