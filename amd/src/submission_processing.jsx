define(["jquery", "core/ajax", "core/notification"], ($, Ajax, Notification) => {
  var M = window.M

  return {
    init: function (cmid) {
      console.log("[v0] TrustGrade submission processing initialized for cmid:", cmid)

      // Check for existing queued tasks on page load
      this.checkInitialTaskStatus(cmid)

      // Listen for form submissions
      this.setupSubmissionListener(cmid)
    },

    checkInitialTaskStatus: function (cmid) {
      

      // Get current user's latest submission for this assignment
      this.getCurrentSubmissionId(cmid)
        .then((submissionId) => {
          if (submissionId) {
            console.log("[v0] Checking task status for submission:", submissionId)
            this.checkTaskStatus(submissionId, cmid)
          } else {
            console.log("[v0] No submission found for current user")
          }
        })
        .catch((error) => {
          console.log("[v0] Error getting submission ID:", error)
        })
    },

    getCurrentSubmissionId: (cmid) =>
      Ajax.call([
        {
          methodname: "mod_assign_get_submission_status",
          args: {
            assignid: cmid,
          },
        },
      ])[0].then((response) => {
        console.log("[v0] Submission status response:", response)
        if (response.lastattempt && response.lastattempt.submission) {
          return response.lastattempt.submission.id
        }
        return null
      }),

    setupSubmissionListener: function (cmid) {
      

      // Listen for assignment submission forms
      $(document).on("submit", 'form[action*="mod/assign/view.php"]', (e) => {
        console.log("[v0] Assignment form submitted")

        // Small delay to allow submission to be processed
        setTimeout(() => {
          this.checkInitialTaskStatus(cmid)
        }, 1000)
      })
    },

    checkTaskStatus: function (submissionId, cmid) {
      

      console.log("[v0] Calling web service with submission_id:", submissionId, "cmid:", cmid)

      Ajax.call([
        {
          methodname: "local_trustgrade_check_task_status",
          args: {
            submission_id: Number.parseInt(submissionId),
            cmid: Number.parseInt(cmid),
          },
        },
      ])[0]
        .then((response) => {
          console.log("[v0] Task status response:", response)

          if (response.status === "queued" || response.status === "processing") {
            this.showProcessingMessage()
            this.startStatusPolling(submissionId, cmid)
          } else if (response.status === "completed") {
            this.redirectToQuiz(cmid)
          } else if (response.status === "failed") {
            this.showErrorMessage(response.error_message)
          } else {
            console.log("[v0] No active task found")
          }
        })
        .catch((error) => {
          console.log("[v0] Error checking task status:", error)
          Notification.exception(error)
        })
    },

    showProcessingMessage: () => {
      console.log("[v0] Showing processing message")

      // Remove existing overlay
      $(".trustgrade-processing-overlay").remove()

      // Create processing overlay
      var overlay = $(
        '<div class="trustgrade-processing-overlay">' +
          '<div class="trustgrade-processing-content">' +
          '<div class="trustgrade-spinner"></div>' +
          "<h3>Processing your submission...</h3>" +
          "<p>Please wait while we analyze your submission and generate quiz questions.</p>" +
          "</div>" +
          "</div>",
      )

      // Add styles
      overlay.css({
        position: "fixed",
        top: "0",
        left: "0",
        width: "100%",
        height: "100%",
        background: "rgba(0, 0, 0, 0.8)",
        "z-index": "9999",
        display: "flex",
        "align-items": "center",
        "justify-content": "center",
      })

      overlay.find(".trustgrade-processing-content").css({
        background: "white",
        padding: "30px",
        "border-radius": "10px",
        "text-align": "center",
        "max-width": "400px",
      })

      overlay.find(".trustgrade-spinner").css({
        border: "4px solid #f3f3f3",
        "border-top": "4px solid #3498db",
        "border-radius": "50%",
        width: "40px",
        height: "40px",
        animation: "spin 2s linear infinite",
        margin: "0 auto 20px",
      })

      // Add spinner animation
      $(
        "<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>",
      ).appendTo("head")

      $("body").append(overlay)
    },

    startStatusPolling: function (submissionId, cmid) {
      
      var pollCount = 0
      var maxPolls = 60 // 3 minutes max

      var pollInterval = setInterval(() => {
        pollCount++
        console.log("[v0] Polling task status, attempt:", pollCount)

        Ajax.call([
          {
            methodname: "local_trustgrade_check_task_status",
            args: {
              submission_id: Number.parseInt(submissionId),
              cmid: Number.parseInt(cmid),
            },
          },
        ])[0]
          .then((response) => {
            console.log("[v0] Poll response:", response)

            if (response.status === "completed") {
              clearInterval(pollInterval)
              this.hideProcessingMessage()
              this.redirectToQuiz(cmid)
            } else if (response.status === "failed") {
              clearInterval(pollInterval)
              this.hideProcessingMessage()
              this.showErrorMessage(response.error_message)
            } else if (pollCount >= maxPolls) {
              clearInterval(pollInterval)
              this.hideProcessingMessage()
              this.showErrorMessage("Processing is taking longer than expected. Please refresh the page.")
            }
          })
          .catch((error) => {
            console.log("[v0] Poll error:", error)
            if (pollCount >= maxPolls) {
              clearInterval(pollInterval)
              this.hideProcessingMessage()
            }
          })
      }, 3000) // Poll every 3 seconds
    },

    hideProcessingMessage: () => {
      console.log("[v0] Hiding processing message")
      $(".trustgrade-processing-overlay").remove()
    },

    showErrorMessage: function (message) {
      console.log("[v0] Showing error message:", message)
      this.hideProcessingMessage()
      Notification.alert("Processing Error", message || "An error occurred while processing your submission.")
    },

    redirectToQuiz: (cmid) => {
      console.log("[v0] Redirecting to quiz for cmid:", cmid)
      var quizUrl = window.M.cfg.wwwroot + "/local/trustgrade/quiz_interface.php?id=" + cmid
      window.location.href = quizUrl
    },
  }
})
