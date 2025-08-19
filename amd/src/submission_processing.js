define(["jquery", "core/ajax", "core/notification"], ($, Ajax, Notification) => {
  var M = window.M

  var SubmissionProcessing = {
    init: function (cmid) {
      this.cmid = cmid
      this.setupSubmissionProcessing()
    },

    setupSubmissionProcessing: function () {
      var self = this

      // Listen for assignment submission forms
      $('form[action*="mod/assign/view.php"], form[data-form="submission"], form.mform').on("submit", function (event) {
        console.log("[v0] Form submission detected")

        // Check if this is actually a submission form
        if (
          $(this).find('input[name="action"][value="savesubmission"]').length > 0 ||
          $(this).find('button[name="submitbutton"]').length > 0
        ) {
          console.log("[v0] Assignment submission form detected, showing processing message")
          self.showProcessingMessage()

          // Start polling after a short delay to allow form submission to process
          setTimeout(() => {
            self.startStatusPolling()
          }, 1000)
        }
      })
    },

    showProcessingMessage: () => {
      console.log("[v0] Showing processing message")

      var message =
        '<div id="submission-processing-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; display: flex; align-items: center; justify-content: center;">' +
        '<div style="background: white; padding: 40px; border-radius: 12px; text-align: center; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">' +
        '<div style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>' +
        '<h3 style="color: #333; margin-bottom: 15px;">Processing your submission...</h3>' +
        '<p style="color: #666; margin: 0;">Please wait while we analyze your submission. This may take a few moments.</p>' +
        "</div>" +
        "</div>" +
        "<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>"

      $("body").append(message)
    },

    startStatusPolling: function () {
      var submissionId = this.getSubmissionId()
      var userId = this.getCurrentUserId()

      console.log("[v0] Starting status polling for submission:", submissionId, "user:", userId)

      if (!submissionId && !userId) {
        console.error("[v0] Could not determine submission ID or user ID")
        this.hideProcessingMessage()
        return
      }
      
      var pollCount = 0
      var maxPolls = 60 // Poll for max 3 minutes

      var pollInterval = setInterval(() => {
        pollCount++
        console.log("[v0] Polling attempt:", pollCount)

        this.checkTaskStatus(submissionId || userId, (status) => {
          console.log("[v0] Task status:", status)

          if (status === "completed") {
            clearInterval(pollInterval)
            this.hideProcessingMessage()
            this.redirectToQuiz()
          } else if (status === "failed") {
            clearInterval(pollInterval)
            this.hideProcessingMessage()
            this.showErrorMessage()
          } else if (pollCount >= maxPolls) {
            // Timeout after max polls
            clearInterval(pollInterval)
            this.hideProcessingMessage()
            this.showTimeoutMessage()
          }
        })
      }, 3000)
    },

    checkTaskStatus: function (submissionId, callback) {
      Ajax.call([
        {
          methodname: "local_trustgrade_check_task_status",
          args: {
            submission_id: submissionId,
            cmid: this.cmid,
          },
        },
      ])[0]
        .done((response) => {
          callback(response.status)
        })
        .fail((error) => {
          console.error("[v0] Error checking task status:", error)
          callback("failed")
        })
    },

    getSubmissionId: () => {
      var urlParams = new URLSearchParams(window.location.search)
      var submissionId = urlParams.get("id") || urlParams.get("submission_id")

      if (!submissionId) {
        // Try to get from form data
        var form = $('form[action*="mod/assign/view.php"], form.mform')
        submissionId =
          form.find('input[name="id"]').val() ||
          form.find('input[name="submission_id"]').val() ||
          form.find('input[name="userid"]').val()
      }

      console.log("[v0] Found submission ID:", submissionId)
      return submissionId
    },

    getCurrentUserId: () => M.cfg.userid || null,

    hideProcessingMessage: () => {
      console.log("[v0] Hiding processing message")
      $("#submission-processing-overlay").remove()
    },

    redirectToQuiz: function () {
      console.log("[v0] Redirecting to quiz")
      var quizUrl = M.cfg.wwwroot + "/local/trustgrade/quiz_interface.php?cmid=" + this.cmid
      console.log("[v0] Quiz URL:", quizUrl)
      window.location.href = quizUrl
    },

    showErrorMessage: () => {
      Notification.alert(
        "Error",
        "There was an error processing your submission. Please try again or contact your instructor.",
        "OK",
      )
    },

    showTimeoutMessage: () => {
      Notification.alert(
        "Processing Timeout",
        "Your submission is still being processed. You can check back later or contact your instructor if this continues.",
        "OK",
      )
    },
  }

  return SubmissionProcessing
})
