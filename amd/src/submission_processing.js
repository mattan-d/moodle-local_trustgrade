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
 * Submission processing module for handling submission UI overlays.
 *
 * @module     local_trustgrade/submission_processing
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const define = window.define // Declare the define variable
define(["jquery", "core/templates", "core/str"], ($, Templates, Str) => {
  var SubmissionProcessing = {
    cmid: 0,
    submissionId: 0,
    processingOverlay: null,
    statusCheckInterval: null,
    questionsToGenerate: 0, // Added to track if questions should be generated

    init: function (cmid, questionsToGenerate = 0) {
      this.cmid = cmid
      this.questionsToGenerate = questionsToGenerate
      this.bindSubmissionEvents()
      this.bindAssignmentFormEvents()
    },

    bindSubmissionEvents: function () {
      // Wait for DOM to be ready
      $(document).ready(() => {
        // Find assignment submission forms
        var $forms = $('form[action*="editsubmission"], form[action*="editmode"], form.mform, #region-main form')

        if ($forms.length > 0) {
          $forms.on("submit", (e) => {
            if (this.questionsToGenerate > 0) {
              this.showProcessingMessage("submission")
            }
            // Allow form to submit normally
            // The processing message will be shown while the page processes
          })
        }
      })
    },

    bindAssignmentFormEvents: function () {
      $(document).ready(() => {
        // Find assignment creation/editing forms (mod_assign forms)
        var $assignmentForms = $('form[action*="modedit.php"], form.mform[action*="course/modedit.php"]')

        if ($assignmentForms.length > 0) {
          $assignmentForms.on("submit", (e) => {
            // Check if auto-generate questions is enabled
            var $autoGenerateCheckbox = $("#id_trustgrade_auto_generate")
            var $trustgradeEnabled = $("#id_trustgrade_enabled")

            if (
              $autoGenerateCheckbox.length > 0 &&
              $autoGenerateCheckbox.is(":checked") &&
              $trustgradeEnabled.length > 0 &&
              $trustgradeEnabled.is(":checked")
            ) {
              this.showProcessingMessage("questions")
            }
            // Allow form to submit normally
          })
        }
      })
    },

    showProcessingMessage: function (type = "submission") {
      var stringRequests = []

      if (type === "questions") {
        stringRequests = [
          { key: "processing_question_generation", component: "local_trustgrade" },
          { key: "processing_question_generation_message", component: "local_trustgrade" },
        ]
      } else {
        stringRequests = [
          { key: "processing_submission", component: "local_trustgrade" },
          { key: "processing_submission_message", component: "local_trustgrade" },
        ]
      }

      Str.get_strings(stringRequests)
        .then((strings) => {
          var context = {
            title: strings[0],
            message: strings[1],
            spinner_class: "fa fa-spinner fa-spin",
          }

          return Templates.render("local_trustgrade/submission_processing_overlay", context)
        })
        .then((html) => {
          // Remove any existing overlay
          $("#submission-processing-overlay").remove()

          // Add overlay to body
          $("body").append(html)

          // Prevent scrolling
          $("body").css("overflow", "hidden")
        })
        .catch((error) => {
          console.error("Error rendering processing overlay:", error)
          // Fallback to simple overlay if template fails
          this.showFallbackProcessingMessage(type)
        })
    },

    showFallbackProcessingMessage: (type = "submission") => {
      var overlayHtml = ""

      if (type === "questions") {
        overlayHtml =
          '<div id="submission-processing-overlay" class="local-trustgrade submission-processing-overlay">' +
          '<div class="processing-modal">' +
          '<div class="spinner-container"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>' +
          '<h3 class="processing-title">Processing Assignment...</h3>' +
          '<p class="processing-message">Please wait while we save your assignment and prepare to generate questions...</p>' +
          "</div></div>"
      } else {
        overlayHtml =
          '<div id="submission-processing-overlay" class="local-trustgrade submission-processing-overlay">' +
          '<div class="processing-modal">' +
          '<div class="spinner-container"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>' +
          '<h3 class="processing-title">Processing...</h3>' +
          '<p class="processing-message">Please wait while we process your submission...</p>' +
          "</div></div>"
      }

      $("#submission-processing-overlay").remove()
      $("body").append(overlayHtml)
      $("body").css("overflow", "hidden")
    },

    hideProcessingMessage: () => {
      $("#submission-processing-overlay").remove()
      $("body").css("overflow", "")
    },
  }

  return SubmissionProcessing
})
