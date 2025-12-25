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
 * Navigation buttons module for adding quiz report and question bank buttons.
 *
 * @module     local_trustgrade/navigation_buttons
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
