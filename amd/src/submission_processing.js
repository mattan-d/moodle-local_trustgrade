define(["jquery", "core/ajax", "core/notification", "M"], ($, Ajax, Notification, M) => {
  var SubmissionProcessing = {
    init: function (cmid) {
      this.cmid = cmid
      this.setupSubmissionProcessing()
    },

    setupSubmissionProcessing: function () {
      

      // Listen for form submissions
      $(document).on("submit", 'form[data-form="submission"]', (e) => {
        this.showProcessingMessage()
        this.startStatusPolling()
      })
    },

    showProcessingMessage: () => {
      var message =
        '<div id="submission-processing-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
        '<div style="background: white; padding: 30px; border-radius: 8px; text-align: center; max-width: 400px;">' +
        '<div class="spinner-border" role="status" style="width: 3rem; height: 3rem; margin-bottom: 20px;"></div>' +
        "<h4>Processing your submission...</h4>" +
        "<p>Please wait while we process your submission. This may take a few moments.</p>" +
        "</div>" +
        "</div>"

      $("body").append(message)
    },

    startStatusPolling: function () {
      
      var submissionId = this.getSubmissionId()

      if (!submissionId) {
        console.error("Could not determine submission ID")
        return
      }

      var pollInterval = setInterval(() => {
        this.checkTaskStatus(submissionId, (status) => {
          if (status === "completed") {
            clearInterval(pollInterval)
            this.hideProcessingMessage()
            this.redirectToQuiz()
          } else if (status === "failed") {
            clearInterval(pollInterval)
            this.hideProcessingMessage()
            this.showErrorMessage()
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
          console.error("Error checking task status:", error)
          callback("failed")
        })
    },

    getSubmissionId: () => {
      // Try to get submission ID from URL parameters or form data
      var urlParams = new URLSearchParams(window.location.search)
      var submissionId = urlParams.get("id") || urlParams.get("submission_id")

      if (!submissionId) {
        // Try to get from form data
        var form = $('form[data-form="submission"]')
        submissionId = form.find('input[name="id"]').val() || form.find('input[name="submission_id"]').val()
      }

      return submissionId
    },

    hideProcessingMessage: () => {
      $("#submission-processing-overlay").remove()
    },

    redirectToQuiz: function () {
      var quizUrl = M.cfg.wwwroot + "/local/trustgrade/quiz_interface.php?cmid=" + this.cmid
      window.location.href = quizUrl
    },

    showErrorMessage: () => {
      Notification.alert(
        "Error",
        "There was an error processing your submission. Please try again or contact your instructor.",
        "OK",
      )
    },
  }

  return SubmissionProcessing
})
