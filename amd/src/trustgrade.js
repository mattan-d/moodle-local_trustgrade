// This file is part of Moodle - http://moodle.org/

var define = window.define // Declare define variable
var M = window.M // Declare M variable
var tinyMCE = window.tinyMCE // Declare tinyMCE variable

define(["jquery", "core/ajax", "core/notification", "core/str", "core/modal_factory"], (
  $,
  Ajax,
  Notification,
  Str,
  ModalFactory,
) => {
  var trustgrade = {
    init: function () {
      this.bindEvents()
      this.loadQuestionBank() // Load existing questions on page load
    },

    bindEvents: () => {
      $(document).on("click", "#check-instructions-btn", (e) => {
        e.preventDefault()
        trustgrade.checkInstructions()
      })
      $(document).on("click", "#generate-questions-btn", (e) => {
        e.preventDefault()
        trustgrade.generateQuestions()
      })
      $(document).on("change", "#id_trustgrade_questions_to_generate", (e) => {
        e.preventDefault()
        trustgrade.updateSingleQuizSetting("questions_to_generate", $(e.target).val())
      })
    },

    showErrorModal: (title, message) => {
      ModalFactory.create({
        type: ModalFactory.types.ALERT,
        title: title,
        body: message,
      }).then((modal) => modal.show())
    },

    showSuccessNotification: (message) => {
      Notification.addNotification({ message: message, type: "success" })
    },

    updateSingleQuizSetting: function (settingName, settingValue) {
      var cmid = this.getCourseModuleId()
      if (cmid <= 0) return

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_update_quiz_setting",
          args: {
            cmid: cmid,
            setting_name: settingName,
            setting_value: settingValue,
          },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            Str.get_string("setting_updated_success", "local_trustgrade", {
              setting: settingName.replace(/_/g, " "),
            }).then((message) => {
              trustgrade.showSuccessNotification(message)
            })
          } else {
            Str.get_string("setting_update_error", "local_trustgrade").then((title) => {
              trustgrade.showErrorModal(title, response.error || "An error occurred while updating the setting.")
            })
          }
        })
        .fail(Notification.exception)
    },

    /**
     * Basic HTML escape to avoid XSS in rendered content.
     */
    escapeHtml: (value) => {
      if (value === null || value === undefined) return ""
      return String(value).replace(/[&<>"']/g, (s) => {
        const map = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }
        return map[s] || s
      })
    },

    /**
     * Render the recommendation which may be either:
     * - a JSON object in the format provided by the backend, or
     * - a string (legacy), which we render as paragraph lines with <br>.
     */
    renderRecommendation: function (recommendation) {
      // Legacy string fallback
      if (typeof recommendation === "string") {
        return recommendation.replace(/\n/g, "<br>")
      }

      // If it's not an object, stringify and fallback to legacy behavior
      if (!recommendation || typeof recommendation !== "object") {
        try {
          const txt = JSON.stringify(recommendation, null, 2)
          return this.escapeHtml(txt).replace(/\n/g, "<br>")
        } catch (e) {
          return ""
        }
      }

      // Expecting structure:
      // {
      //   "table": { "title": "", "rows": [{ "Criterion": "", "Met": "", "Suggestions": "" }] },
      //   "EvaluationText": { "content": "" },
      //   "ImprovedAssignment": { "content": "" }
      // }

      const table = recommendation.table || {}
      const rows = Array.isArray(table.rows) ? table.rows : []
      const tableTitle = table.title || "Criteria Evaluation"

      const evalText =
        recommendation.EvaluationText && recommendation.EvaluationText.content
          ? String(recommendation.EvaluationText.content)
          : ""
      const improved =
        recommendation.ImprovedAssignment && recommendation.ImprovedAssignment.content
          ? String(recommendation.ImprovedAssignment.content)
          : ""

      // Build HTML safely
      let html = ""

      // Table section
      html += `<div class="tg-section tg-table-section" style="margin-bottom:16px;">`
      if (tableTitle) {
        html += `<h4 style="margin:0 0 8px 0;">${this.escapeHtml(tableTitle)}</h4>`
      }
      html += `
        <div class="table-responsive">
          <table class="generaltable boxaligncenter" style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${this.escapeHtml("Criterion")}</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${this.escapeHtml("Met")}</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${this.escapeHtml("Suggestions")}</th>
              </tr>
            </thead>
            <tbody>
      `
      if (rows.length > 0) {
        rows.forEach((r) => {
          const c = this.escapeHtml(r["Criterion"] ?? "")
          const m = this.escapeHtml(r["Met"] ?? "")
          const s = this.escapeHtml(r["Suggestions"] ?? "")
          html += `
              <tr>
                <td style="vertical-align:top; border-bottom:1px solid #eee; padding:8px;">${c}</td>
                <td style="vertical-align:top; border-bottom:1px solid #eee; padding:8px;">${m}</td>
                <td style="vertical-align:top; border-bottom:1px solid #eee; padding:8px;">${s}</td>
              </tr>
          `
        })
      } else {
        html += `
              <tr>
                <td colspan="3" style="padding:8px; color:#666;">${this.escapeHtml("No criteria provided.")}</td>
              </tr>
        `
      }
      html += `
            </tbody>
          </table>
        </div>
      </div>
      `

      // Evaluation Text section
      html += `<div class="tg-section tg-eval-text" style="margin-bottom:16px;">`
      html += `<h4 style="margin:0 0 8px 0;">${this.escapeHtml("Evaluation")}</h4>`
      html += `<div>${this.escapeHtml(evalText).replace(/\n/g, "<br>")}</div>`
      html += `</div>`

      // Improved Assignment section
      html += `<div class="tg-section tg-improved" style="margin-bottom:8px;">`
      html += `<h4 style="margin:0 0 8px 0;">${this.escapeHtml("Improved Assignment")}</h4>`
      html += `<div>${this.escapeHtml(improved).replace(/\n/g, "<br>")}</div>`
      html += `</div>`

      return html
    },

    checkInstructions: function () {
      var instructions = this.getInstructions()
      if (!instructions || instructions.trim().length === 0) {
        Str.get_string("no_instructions_error", "local_trustgrade").then((message) => {
          Str.get_string("input_validation_error", "local_trustgrade").then((title) => {
            trustgrade.showErrorModal(title, message)
          })
        })
        return
      }

      $("#check-instructions-btn").prop("disabled", true)
      $("#ai-loading").show()
      $("#ai-recommendation-container").hide()

      var cmid = this.getCourseModuleId()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_check_instructions",
          args: { cmid: cmid, instructions: instructions },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            var recommendationHtml = ""
            try {
              var recObj =
                typeof response.recommendation === "string"
                  ? JSON.parse(response.recommendation)
                  : response.recommendation

              recommendationHtml = trustgrade.renderRecommendation(recObj)
            } catch (e) {
              // Fallback to legacy plaintext if JSON parsing fails
              recommendationHtml = String(response.recommendation || "").replace(/\n/g, "<br>")
            }

            if (response.from_cache) {
              Str.get_string("cache_hit", "local_trustgrade").then((cacheMessage) => {
                recommendationHtml =
                  '<div class="alert alert-info mb-2"><i class="fa fa-clock-o"></i> <small>' +
                  cacheMessage +
                  " (Debug mode)</small></div>" +
                  recommendationHtml
                $("#ai-recommendation").html(recommendationHtml)
              })
            } else {
              $("#ai-recommendation").html(recommendationHtml)
            }
            $("#ai-recommendation-container").show()
          } else {
            Str.get_string("gateway_error", "local_trustgrade").then((title) => {
              trustgrade.showErrorModal(title, response.error || "An error occurred.")
            })
          }
        })
        .fail(Notification.exception)
        .always(() => {
          $("#check-instructions-btn").prop("disabled", false)
          $("#ai-loading").hide()
        })
    },

    generateQuestions: function () {
      var instructions = this.getInstructions()
      if (!instructions || instructions.trim().length === 0) {
        Str.get_string("no_instructions_questions_error", "local_trustgrade").then((message) => {
          Str.get_string("input_validation_error", "local_trustgrade").then((title) => {
            trustgrade.showErrorModal(title, message)
          })
        })
        return
      }

      $("#generate-questions-btn").prop("disabled", true)
      $("#ai-question-loading").show()
      $("#ai-questions-container").hide()

      var cmid = this.getCourseModuleId()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_generate_questions",
          args: { cmid: cmid, instructions: instructions },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            var questions = JSON.parse(response.questions)
            trustgrade.formatQuestionsDisplay(questions).then((questionsHtml) => {
              if (response.from_cache) {
                Str.get_string("cache_hit", "local_trustgrade").then((cacheMessage) => {
                  questionsHtml =
                    '<div class="alert alert-info mb-2"><i class="fa fa-clock-o"></i> <small>' +
                    cacheMessage +
                    " (Debug mode)</small></div>" +
                    questionsHtml
                  $("#ai-questions").html(questionsHtml)
                })
              } else {
                $("#ai-questions").html(questionsHtml)
              }
              $("#ai-questions-container").show()
            })

            if (response.message) {
              trustgrade.showSuccessNotification(response.message)
            }
            trustgrade.loadQuestionBank()
          } else {
            Str.get_string("gateway_error", "local_trustgrade").then((title) => {
              trustgrade.showErrorModal(title, response.error || "An error occurred.")
            })
          }
        })
        .fail(Notification.exception)
        .always(() => {
          $("#generate-questions-btn").prop("disabled", false)
          $("#ai-question-loading").hide()
        })
    },

    formatQuestionsDisplay: (questions) =>
      new Promise((resolve) => {
        Promise.all([
          Str.get_string("generated_questions", "local_trustgrade"),
          Str.get_string("question", "local_trustgrade"),
          Str.get_string("points", "local_trustgrade"),
          Str.get_string("correct", "local_trustgrade"),
          Str.get_string("explanation", "local_trustgrade"),
        ]).then((strings) => {
          var html = "<h4>" + strings[0] + ":</h4>"
          questions.forEach((question, index) => {
            html += `<div class="question-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">`
            html += `<h5>${strings[1]} ${index + 1} (${question.difficulty || "medium"} - ${
              question.points || 10
            } ${strings[2]})</h5>`
            html += `<p><strong>Type:</strong> ${question.type}</p>`
            html += `<p><strong>${strings[1]}:</strong> ${question.question}</p>`
            if (question.options && question.options.length > 0) {
              html += `<p><strong>Options:</strong></p><ul>`
              question.options.forEach((option, optIndex) => {
                var isCorrect = question.correct_answer === optIndex ? ` <strong>(${strings[3]})</strong>` : ""
                html += `<li>${option}${isCorrect}</li>`
              })
              html += `</ul>`
            }
            if (question.explanation) {
              html += `<p><strong>${strings[4]}:</strong> ${question.explanation}</p>`
            }
            html += `</div>`
          })
          resolve(html)
        })
      }),

    getInstructions: () => {
      var instructions = ""
      var instructionSelectors = ["#id_introeditor_ifr", "#id_intro", 'textarea[name="intro"]']
      for (var i = 0; i < instructionSelectors.length; i++) {
        var $element = $(instructionSelectors[i])
        if ($element.length > 0) {
          if ($element.is("iframe")) {
            try {
              var iframeDoc = $element[0].contentDocument || $element[0].contentWindow.document
              instructions = $("<div>").html(iframeDoc.body.innerHTML).text()
            } catch (e) {
              if (typeof tinyMCE !== "undefined" && tinyMCE.get("id_introeditor")) {
                instructions = tinyMCE.get("id_introeditor").getContent({ format: "text" })
              }
            }
          } else {
            instructions = $element.val() || ""
          }
          if (instructions && instructions.trim().length > 0) break
        }
      }
      return typeof instructions === "string" ? instructions.trim() : ""
    },

    getCourseModuleId: () => {
      var urlParams = new URLSearchParams(window.location.search)
      var cmid = urlParams.get("update")
      if (!cmid) {
        cmid = $('input[name="coursemodule"]').val() || 0
      }
      return Number.parseInt(cmid) || 0
    },

    loadQuestionBank: function () {
      var cmid = this.getCourseModuleId()
      if (cmid <= 0) return

      $("#question-bank-loading").show()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_get_question_bank",
          args: { cmid: cmid },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            var questions = JSON.parse(response.questions)
            if (questions && questions.length > 0) {
              Str.get_string("question_bank_title", "local_trustgrade").then((title) => {
                var questionBankHtml = "<h4>" + title + "</h4>" + response.html
                $("#question-bank-container").html(questionBankHtml)
                if (typeof require !== "undefined") {
                  require(["local_trustgrade/question_editor"], (QuestionEditor) => {
                    QuestionEditor.reinitialize(cmid)
                  })
                }
              })
            } else {
              $("#question-bank-container").html("")
            }
          } else {
            Notification.addNotification({ message: response.error || "Failed to load question bank", type: "warning" })
          }
        })
        .fail(Notification.exception)
        .always(() => {
          $("#question-bank-loading").hide()
        })
    },
  }

  return trustgrade
})
