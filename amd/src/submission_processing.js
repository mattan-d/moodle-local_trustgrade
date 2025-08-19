// This file is part of Moodle - http://moodle.org

define(["jquery", "core/ajax", "core/notification"], ($, Ajax, Notification) => {
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

    showProcessingMessage: () => {
      var overlayHtml = `
                <div id="submission-processing-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 18px;
                ">
                    <div style="
                        background: white;
                        color: #333;
                        padding: 30px;
                        border-radius: 8px;
                        text-align: center;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        max-width: 400px;
                    ">
                        <div style="margin-bottom: 20px;">
                            <i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #007cba;"></i>
                        </div>
                        <h3 style="margin: 0 0 10px 0; color: #333;">Processing Your Submission</h3>
                        <p style="margin: 0; color: #666;">Please wait while we process your assignment submission...</p>
                    </div>
                </div>
            `

      // Remove any existing overlay
      $("#submission-processing-overlay").remove()

      // Add overlay to body
      $("body").append(overlayHtml)

      // Prevent scrolling
      $("body").css("overflow", "hidden")
    },

    hideProcessingMessage: () => {
      $("#submission-processing-overlay").remove()
      $("body").css("overflow", "")
    },
  }

  return SubmissionProcessing
})
