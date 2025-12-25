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
  }

  return QuestionBank
})
