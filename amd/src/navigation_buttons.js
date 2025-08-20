// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/str"], ($, Str) => {
  var NavigationButtons = {
    init: function (cmid) {
      this.cmid = cmid || 0

      if (this.cmid > 0) {
        this.addNavigationButtons()
      }
    },

    addNavigationButtons: function () {
      $(document).ready(() => {
        this.addQuizReportButton()
        this.addQuestionBankButton()
      })
    },

    addQuizReportButton: function () {
      var reportUrl = M.cfg.wwwroot + "/local/trustgrade/quiz_report.php?cmid=" + this.cmid

      Str.get_string("ai_quiz_report", "local_trustgrade")
        .then((buttonText) => {
          this.createReportButton(reportUrl, buttonText)
        })
        .catch(() => {
          // Fallback to English
          this.createReportButton(reportUrl, "AI Quiz Report")
        })
    },

    addQuestionBankButton: function () {
      var questionBankUrl = M.cfg.wwwroot + "/local/trustgrade/question_bank.php?cmid=" + this.cmid

      Str.get_string("question_bank", "local_trustgrade")
        .then((buttonText) => {
          this.createQuestionBankButton(questionBankUrl, buttonText)
        })
        .catch(() => {
          // Fallback to English
          this.createQuestionBankButton(questionBankUrl, "Question Bank")
        })
    },

    createReportButton: (url, buttonText) => {
      var actionMenu = document.querySelector(".tertiary-navigation")
      var submissionLinks = document.querySelector(".navitem")

      if (submissionLinks || actionMenu) {
        var reportButton = document.createElement("a")
        reportButton.href = url
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
    },

    createQuestionBankButton: (url, buttonText) => {
      var actionMenu = document.querySelector(".tertiary-navigation")
      var submissionLinks = document.querySelector(".navitem")
      var existingReportButton = document.querySelector(".ai-quiz-report-button")

      if ((submissionLinks || actionMenu) && existingReportButton) {
        var questionBankButton = document.createElement("a")
        questionBankButton.href = url
        questionBankButton.className = "btn btn-secondary"
        questionBankButton.innerHTML = '<i class="fa fa-question-circle"></i> ' + buttonText

        var container = document.createElement("div")
        container.className = "navitem ai-question-bank-button"
        container.appendChild(questionBankButton)

        // Insert after the quiz report button
        existingReportButton.parentNode.insertBefore(container, existingReportButton.nextSibling)
      }
    },
  }

  return NavigationButtons
})
