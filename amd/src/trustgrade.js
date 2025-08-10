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
    renderRecommendation: (recommendation) => {
      // Legacy string fallback
      if (typeof recommendation === "string") {
        return recommendation.replace(/\n/g, "<br>")
      }

      // If it's not an object, stringify and fallback to legacy behavior
      if (!recommendation || typeof recommendation !== "object") {
        try {
          const txt = JSON.stringify(recommendation, null, 2)
          return trustgrade.escapeHtml(txt).replace(/\n/g, "<br>")
        } catch (e) {
          return ""
        }
      }

      // Expected structure:
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

      let html = ""

      // Table section
      html += `<div class="tg-section tg-table-section" style="margin-bottom:16px;">`
      if (tableTitle) {
        html += `<h4 style="margin:0 0 8px 0;">${trustgrade.escapeHtml(tableTitle)}</h4>`
      }
      html += `
        <div class="table-responsive">
          <table class="generaltable boxaligncenter" style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${trustgrade.escapeHtml(
                  "Criterion",
                )}</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${trustgrade.escapeHtml(
                  "Met",
                )}</th>
                <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">${trustgrade.escapeHtml(
                  "Suggestions",
                )}</th>
              </tr>
            </thead>
            <tbody>
      `
      if (rows.length > 0) {
        rows.forEach((r) => {
          const c = trustgrade.escapeHtml(r["Criterion"] ?? "")
          const m = trustgrade.escapeHtml(r["Met"] ?? r["Met (y/n)"] ?? "")
          const s = trustgrade.escapeHtml(r["Suggestions"] ?? "")
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
                <td colspan="3" style="padding:8px; color:#666;">${trustgrade.escapeHtml("No criteria provided.")}</td>
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
      html += `<h4 style="margin:0 0 8px 0;">${trustgrade.escapeHtml("Evaluation")}</h4>`
      html += `<div>${trustgrade.escapeHtml(evalText).replace(/\n/g, "<br>")}</div>`
      html += `</div>`

      // Improved Assignment section
      html += `<div class="tg-section tg-improved" style="margin-bottom:8px;">`
      html += `<h4 style="margin:0 0 8px 0;">${trustgrade.escapeHtml("Improved Assignment")}</h4>`
      html += `<div>${trustgrade.escapeHtml(improved).replace(/\n/g, "<br>")}</div>`
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
          args: {
            cmid: cmid,
            instructions: instructions,
            intro_itemid: trustgrade.getIntroEditorItemId(),
            intro_attachments_itemid: trustgrade.getIntroAttachmentsItemId(),
          },
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
            // response.questions is expected to be a JSON string representing the NEW pattern:
            // [
            //   {
            //     id, type, text,
            //     options: [{ id, text, is_correct, explanation }],
            //     metadata: { blooms_level, points }
            //   }
            // ]
            var questions = typeof response.questions === "string" ? JSON.parse(response.questions) : response.questions

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

    /**
     * Format the AI-generated questions for display (NEW JSON PATTERN ONLY).
     * - No difficulty field.
     * - Per-option explanations.
     * - Points are in question.metadata.points.
     */
    formatQuestionsDisplay: (questions) =>
      new Promise((resolve) => {
        Promise.all([
          Str.get_string("generated_questions", "local_trustgrade"),
          Str.get_string("question", "local_trustgrade"),
          Str.get_string("points", "local_trustgrade"),
          Str.get_string("correct", "local_trustgrade"),
          Str.get_string("explanation", "local_trustgrade"),
        ]).then((strings) => {
          const [sGeneratedQuestions, sQuestion, sPoints, sCorrect, sExplanation] = strings

          let html = "<h4>" + sGeneratedQuestions + ":</h4>"

          if (!Array.isArray(questions) || questions.length === 0) {
            html += `<div style="color:#666;">${trustgrade.escapeHtml("No questions generated.")}</div>`
            resolve(html)
            return
          }

          questions.forEach((q, index) => {
            const qType = q && q.type ? String(q.type) : ""
            const qText = q && q.text ? String(q.text) : ""
            const points =
              q && q.metadata && (typeof q.metadata.points === "number" || typeof q.metadata.points === "string")
                ? String(q.metadata.points)
                : ""
            const blooms = q && q.metadata && q.metadata.blooms_level ? String(q.metadata.blooms_level) : ""

            html += `<div class="question-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">`

            // Header line: "Question X (Y points)" if points exist
            let header = `${sQuestion} ${index + 1}`
            if (points !== "") {
              header += ` (${trustgrade.escapeHtml(points)} ${sPoints})`
            }
            html += `<h5>${trustgrade.escapeHtml(header)}</h5>`

            if (qType) {
              html += `<p><strong>Type:</strong> ${trustgrade.escapeHtml(qType)}</p>`
            }

            html += `<p><strong>${trustgrade.escapeHtml(sQuestion)}:</strong> ${trustgrade.escapeHtml(qText)}</p>`

            if (blooms) {
              html += `<p><strong>${trustgrade.escapeHtml("Bloom's level")}:</strong> ${trustgrade.escapeHtml(
                blooms,
              )}</p>`
            }

            // Options with per-option explanation
            if (Array.isArray(q.options) && q.options.length > 0) {
              html += `<div><strong>Options:</strong></div><ul style="margin:6px 0 0 20px;">`
              q.options.forEach((opt, optIndex) => {
                const label = String.fromCharCode(65 + optIndex) + "."
                const optText = opt && opt.text ? String(opt.text) : ""
                const isCorrect = !!(opt && opt.is_correct)
                const explanation = opt && opt.explanation ? String(opt.explanation) : ""

                const correctBadge = isCorrect
                  ? ` <span class="badge badge-success" style="display:inline-block; padding:2px 6px; background:#16a34a; color:#fff; border-radius:4px; font-size:12px;">${trustgrade.escapeHtml(
                      sCorrect,
                    )}</span>`
                  : ""

                html += `<li style="margin:6px 0;">`
                html += `<div>${trustgrade.escapeHtml(label)} ${trustgrade.escapeHtml(optText)}${correctBadge}</div>`
                if (explanation) {
                  html += `<div style="margin-left:20px; color:#555;"><em>${trustgrade.escapeHtml(
                    sExplanation,
                  )}:</em> ${trustgrade.escapeHtml(explanation)}</div>`
                }
                html += `</li>`
              })
              html += `</ul>`
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

    getIntroEditorItemId: () => {
      var $input = $('input[name="introeditor[itemid]"]')
      var val = $input.length ? $input.val() : ""
      var n = Number.parseInt(val || "0", 10)
      return isNaN(n) ? 0 : n
    },

    getIntroAttachmentsItemId: () => {
      // Try a few common names used by Moodle forms for a filemanager attached to "intro"
      var candidates = [
        'input[name="introattachments"]',
        'input[name="introattachments_filemanager"]',
        'input[name="introattachments[itemid]"]',
      ]
      for (var i = 0; i < candidates.length; i++) {
        var $el = $(candidates[i])
        if ($el.length) {
          var v = Number.parseInt($el.val() || "0", 10)
          if (!isNaN(v) && v > 0) return v
        }
      }
      return 0
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
