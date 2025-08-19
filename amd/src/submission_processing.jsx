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
 * Submission processing monitoring for TrustGrade
 *
 * @module     local_trustgrade/submission_processing
 * @copyright  2024 TrustGrade
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification", "core/config"], ($, Ajax, Notification, Config) => {
  var SubmissionProcessing = {
    /**
     * Initialize submission processing monitoring
     * @param {int} submissionId
     * @param {int} cmid
     */
    init: function (submissionId, cmid) {
      this.submissionId = submissionId
      this.cmid = cmid
      this.checkInterval = null

      // Show initial processing message
      this.showProcessingMessage()

      // Start checking task status
      this.startStatusCheck()
    },

    /**
     * Show processing message to user
     */
    showProcessingMessage: () => {
      var messageHtml =
        '<div id="trustgrade-processing-message" class="alert alert-info">' +
        '<div class="d-flex align-items-center">' +
        '<div class="spinner-border spinner-border-sm me-2" role="status">' +
        '<span class="sr-only">Loading...</span>' +
        "</div>" +
        "<div>" +
        "<strong>Processing your submission...</strong><br>" +
        "Please wait while we generate questions based on your submission. This may take a few moments." +
        "</div>" +
        "</div>" +
        "</div>"

      // Insert message after assignment submission area
      var $submissionArea = $(".submissionstatustable, .submission-status, #assign_submission_form")
      if ($submissionArea.length) {
        $submissionArea.after(messageHtml)
      } else {
        // Fallback: prepend to main content
        $("#region-main .card-body, #region-main").first().prepend(messageHtml)
      }
    },

    /**
     * Start checking task status periodically
     */
    startStatusCheck: function () {
      

      this.checkInterval = setInterval(() => {
        this.checkTaskStatus()
      }, 3000) // Check every 3 seconds

      // Also check immediately
      this.checkTaskStatus()
    },

    /**
     * Check current task status via AJAX
     */
    checkTaskStatus: function () {
      

      $.ajax({
        url: Config.wwwroot + "/local/trustgrade/ajax/check_task_status.php",
        method: "GET",
        data: {
          submission_id: this.submissionId,
          cmid: this.cmid,
          sesskey: Config.sesskey,
        },
        dataType: "json",
      })
        .done((response) => {
          this.handleStatusResponse(response)
        })
        .fail(() => {
          // Continue checking - might be temporary network issue
        })
    },

    /**
     * Handle status response from server
     * @param {Object} response
     */
    handleStatusResponse: function (response) {
      if (response.status === "completed") {
        this.showCompletedMessage()
        this.stopStatusCheck()
        // Redirect to quiz after short delay
        setTimeout(() => {
          window.location.reload()
        }, 2000)
      } else if (response.status === "failed") {
        this.showErrorMessage(response.error_message)
        this.stopStatusCheck()
      }
      // If status is 'queued' or 'processing', continue checking
    },

    /**
     * Show completion message
     */
    showCompletedMessage: () => {
      var $message = $("#trustgrade-processing-message")
      $message.removeClass("alert-info").addClass("alert-success")
      $message.html(
        '<div class="d-flex align-items-center">' +
          '<i class="fa fa-check-circle me-2 text-success"></i>' +
          "<div>" +
          "<strong>Processing completed!</strong><br>" +
          "Questions have been generated. Redirecting to quiz..." +
          "</div>" +
          "</div>",
      )
    },

    /**
     * Show error message
     * @param {string} errorMessage
     */
    showErrorMessage: (errorMessage) => {
      var $message = $("#trustgrade-processing-message")
      $message.removeClass("alert-info").addClass("alert-danger")
      $message.html(
        '<div class="d-flex align-items-center">' +
          '<i class="fa fa-exclamation-triangle me-2 text-danger"></i>' +
          "<div>" +
          "<strong>Processing failed</strong><br>" +
          "There was an error processing your submission. Please try again or contact support." +
          (errorMessage ? "<br><small>" + errorMessage + "</small>" : "") +
          "</div>" +
          "</div>",
      )
    },

    /**
     * Stop status checking
     */
    stopStatusCheck: function () {
      if (this.checkInterval) {
        clearInterval(this.checkInterval)
        this.checkInterval = null
      }
    },
  }

  return SubmissionProcessing
})
