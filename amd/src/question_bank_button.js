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
      

      // Try immediately first
      this.tryAddButton()

      // Also try after DOM is ready
      $(document).ready(() => {
        this.tryAddButton()
      })

      // Fallback: retry after a short delay to handle race conditions
      setTimeout(() => {
        this.tryAddButton()
      }, 500)

      // Final fallback: retry after longer delay for slow-loading pages
      setTimeout(() => {
        this.tryAddButton()
      }, 2000)
    },

    tryAddButton: function () {
      // Don't add if button already exists
      if (document.querySelector(".ai-question-bank-button")) {
        return
      }

      var questionBankUrl = M.cfg.wwwroot + "/local/trustgrade/question_bank.php?cmid=" + this.cmid

      Str.get_string("question_bank", "local_trustgrade")
        .then((buttonText) => {
          this.createButton(questionBankUrl, buttonText)
        })
        .catch(() => {
          // Fallback to English
          this.createButton(questionBankUrl, "Question Bank")
        })
    },

    createButton: (url, buttonText) => {
      if (document.querySelector(".ai-question-bank-button")) {
        return
      }

      var questionBankButton = document.createElement("a")
      questionBankButton.href = url
      questionBankButton.className = "btn btn-secondary"
      questionBankButton.innerHTML = '<i class="fa fa-question-circle"></i> ' + buttonText

      var buttonContainer = document.createElement("div")
      buttonContainer.className = "navitem ai-question-bank-button"
      buttonContainer.appendChild(questionBankButton)

      var actionMenu = document.querySelector(".tertiary-navigation")
      var submissionLinks = document.querySelector(".navitem")
      var existingReportButton = document.querySelector(".ai-quiz-report-button")

      var targetContainer = null
      var insertMethod = "append" // 'append', 'prepend', 'after', 'before'
      var referenceElement = null

      if (existingReportButton && (submissionLinks || actionMenu)) {
        // Preferred: Insert after quiz report button
        targetContainer = existingReportButton.parentNode
        insertMethod = "after"
        referenceElement = existingReportButton
      } else if (actionMenu) {
        // Fallback 1: Add to tertiary navigation
        targetContainer = actionMenu
        insertMethod = "append"
      } else if (submissionLinks) {
        // Fallback 2: Add to navitem container
        targetContainer = submissionLinks.parentNode || submissionLinks
        insertMethod = "append"
      } else {
        // Fallback 3: Try to find any navigation container
        var navContainers = [".navbar-nav", ".nav", ".navigation", ".page-header-headings", ".page-context-header"]

        for (var i = 0; i < navContainers.length; i++) {
          var container = document.querySelector(navContainers[i])
          if (container) {
            targetContainer = container
            insertMethod = "append"
            break
          }
        }
      }

      if (targetContainer) {
        switch (insertMethod) {
          case "after":
            if (referenceElement && referenceElement.nextSibling) {
              targetContainer.insertBefore(buttonContainer, referenceElement.nextSibling)
            } else {
              targetContainer.appendChild(buttonContainer)
            }
            break
          case "before":
            targetContainer.insertBefore(buttonContainer, referenceElement)
            break
          case "prepend":
            targetContainer.insertBefore(buttonContainer, targetContainer.firstChild)
            break
          case "append":
          default:
            targetContainer.appendChild(buttonContainer)
            break
        }
      }
    },
  }

  return QuestionBankButton
})
