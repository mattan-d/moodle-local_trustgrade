// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/str"], ($, Str) => {
  var QuizReportButton = {
    init: function (cmid) {
      this.cmid = cmid || 0

      if (this.cmid > 0) {
        this.addReportButton()
      }
    },

    addReportButton: function () {
      

      $(document).ready(() => {
        var reportUrl = M.cfg.wwwroot + "/local/trustgrade/quiz_report.php?cmid=" + this.cmid

        Str.get_string("ai_quiz_report", "local_trustgrade")
          .then((buttonText) => {
            var actionMenu = document.querySelector(".tertiary-navigation")
            var submissionLinks = document.querySelector(".navitem")

            if (submissionLinks || actionMenu) {
              var reportButton = document.createElement("a")
              reportButton.href = reportUrl
              reportButton.className = "btn btn-secondary"
              reportButton.innerHTML = '<i class="fa fa-chart-bar"></i> ' + buttonText

              var container = document.createElement("div")
              container.className = "navitem ai-quiz-report-button"
              container.appendChild(reportButton)

              var targetElement = submissionLinks || (actionMenu ? actionMenu.closest(".action-menu") : null)
              if (targetElement) {
                targetElement.parentNode.insertBefore(container, targetElement.nextSibling)
              }
            }
          })
          .catch(() => {
            // Fallback to English
            var buttonText = "AI Quiz Report"

            var actionMenu = document.querySelector(".tertiary-navigation")
            var submissionLinks = document.querySelector(".navitem")

            if (submissionLinks || actionMenu) {
              var reportButton = document.createElement("a")
              reportButton.href = reportUrl
              reportButton.className = "btn btn-secondary"
              reportButton.innerHTML = '<i class="fa fa-chart-bar"></i> ' + buttonText

              var container = document.createElement("div")
              container.className = "navitem ai-quiz-report-button"
              container.appendChild(reportButton)

              var targetElement = submissionLinks || (actionMenu ? actionMenu.closest(".action-menu") : null)
              if (targetElement) {
                targetElement.parentNode.insertBefore(container, targetElement.nextSibling)
              }
            }
          })
      })
    },
  }

  return QuizReportButton
})
