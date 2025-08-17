// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/str"], ($, Str) => {
  var QuestionBankButton = {
    init: function (cmid) {
      this.cmid = cmid || 0

      if (this.cmid > 0) {
        this.addQuestionBankButton()
      }
    },

    addQuestionBankButton: function () {
      $(document).ready(() => {
        var questionBankUrl = M.cfg.wwwroot + "/local/trustgrade/question_bank.php?cmid=" + this.cmid

        Str.get_string("question_bank", "local_trustgrade")
          .then((buttonText) => {
            this.createButton(questionBankUrl, buttonText)
          })
          .catch(() => {
            // Fallback to English
            this.createButton(questionBankUrl, "Question Bank")
          })
      })
    },

    createButton: (url, buttonText) => {
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

  return QuestionBankButton
})
