// This file is part of Moodle - http://moodle.org
define(["jquery", "core/templates", "core/str"], ($, Templates, Str) => {
  var SubmissionProcessing = {
    cmid: 0,
    submissionId: 0,
    processingOverlay: null,
    statusCheckInterval: null,

    init: function (cmid) {
      this.cmid = cmid
      this.bindSubmissionEvents()
    },

    bindSubmissionEvents: function () {
      // Wait for DOM to be ready
      $(document).ready(() => {
        // Find assignment submission forms
        var $forms = $('form[action*="editsubmission"], form.mform, #region-main form')

        if ($forms.length > 0) {
          $forms.on("submit", (e) => {
            this.showProcessingMessage()
            // Allow form to submit normally
            // The processing message will be shown while the page processes
          })
        }
      })
    },

    showProcessingMessage: function () {
      

      var stringRequests = [
        { key: "processing_submission", component: "local_trustgrade" },
        { key: "processing_submission_message", component: "local_trustgrade" },
      ]

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
          console.error("Error rendering submission processing overlay:", error)
          // Fallback to simple overlay if template fails
          this.showFallbackProcessingMessage()
        })
    },

    showFallbackProcessingMessage: () => {
      var overlayHtml =
        '<div id="submission-processing-overlay" class="submission-processing-overlay">' +
        '<div class="processing-modal">' +
        '<div class="spinner-container"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>' +
        '<h3 class="processing-title">Processing...</h3>' +
        '<p class="processing-message">Please wait while we process your submission...</p>' +
        "</div></div>"

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
