// This file is part of Moodle - http://moodle.org/

var define = window.define // Declare define variable
var M = window.M // Declare M variable
var tinyMCE = window.tinyMCE // Declare tinyMCE variable

define(["jquery", "core/ajax", "core/notification", "core/str", "core/modal_factory", "core/templates"], (
  $,
  Ajax,
  Notification,
  Str,
  ModalFactory,
  Templates,
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
     * Render the recommendation using Mustache templates and localized strings.
     * Supports both legacy string format and structured JSON objects.
     */
    renderRecommendation: (recommendation) => {
      return new Promise((resolve) => {
        Promise.all([
          Str.get_string("criteria_evaluation", "local_trustgrade"),
          Str.get_string("criterion", "local_trustgrade"),
          Str.get_string("met", "local_trustgrade"),
          Str.get_string("suggestions", "local_trustgrade"),
          Str.get_string("evaluation", "local_trustgrade"),
          Str.get_string("improved_assignment", "local_trustgrade"),
          Str.get_string("no_criteria_provided", "local_trustgrade"),
          Str.get_string("recommendation_error", "local_trustgrade"),
        ]).then((strings) => {
          const [
            criteriaEvaluation,
            criterion,
            met,
            suggestions,
            evaluation,
            improvedAssignment,
            noCriteriaProvided,
            recommendationError,
          ] = strings

          const localizedStrings = {
            criteria_evaluation: criteriaEvaluation,
            criterion: criterion,
            met: met,
            suggestions: suggestions,
            evaluation: evaluation,
            improved_assignment: improvedAssignment,
            no_criteria_provided: noCriteriaProvided,
            recommendation_error: recommendationError,
          }

          try {
            // Legacy string fallback
            if (typeof recommendation === "string") {
              const content = recommendation.replace(/\n/g, "<br>")
              resolve(content)
              return
            }

            // If it's not an object, stringify and fallback to legacy behavior
            if (!recommendation || typeof recommendation !== "object") {
              try {
                const txt = JSON.stringify(recommendation, null, 2)
                resolve(trustgrade.escapeHtml(txt).replace(/\n/g, "<br>"))
                return
              } catch (e) {
                resolve("")
                return
              }
            }

            const table = recommendation.table || {}
            const rows = Array.isArray(table.rows) ? table.rows : []
            const tableTitle = table.title || criteriaEvaluation

            const processedRows = rows.map((row) => {
              const metValue = (row["Met"] || row["Met (y/n)"] || "").toLowerCase()
              return {
                Criterion: trustgrade.escapeHtml(row["Criterion"] || ""),
                Met: trustgrade.escapeHtml(row["Met"] || row["Met (y/n)"] || ""),
                Suggestions: row["Suggestions"] || "",
                isMetYes: metValue === "yes" || metValue === "y" || metValue === "true",
                isMetNo: metValue === "no" || metValue === "n" || metValue === "false",
                isMetPartial: metValue === "partial" || metValue === "partially" || metValue === "maybe",
              }
            })

            let html = ""

            // Render table section
            const tableContext = {
              title: tableTitle,
              rows: processedRows,
              strings: localizedStrings,
            }

            Templates.render("local_trustgrade/recommendation_table", tableContext)
              .then((tableHtml) => {
                html += tableHtml

                // Render evaluation section
                const evalText =
                  recommendation.EvaluationText && recommendation.EvaluationText.content
                    ? String(recommendation.EvaluationText.content).replace(/\n/g, "<br>")
                    : ""

                if (evalText) {
                  const evalContext = {
                    title: evaluation,
                    content: evalText,
                    icon: "fa fa-clipboard-check",
                    sectionClass: "tg-eval-text",
                  }

                  return Templates.render("local_trustgrade/recommendation_section", evalContext)
                }
                return ""
              })
              .then((evalHtml) => {
                html += evalHtml

                // Render improved assignment section
                const improved =
                  recommendation.ImprovedAssignment && recommendation.ImprovedAssignment.content
                    ? String(recommendation.ImprovedAssignment.content).replace(/\n/g, "<br>")
                    : ""

                if (improved) {
                  const improvedContext = {
                    title: improvedAssignment,
                    content: improved,
                    icon: "fa fa-lightbulb",
                    sectionClass: "tg-improved",
                  }

                  return Templates.render("local_trustgrade/recommendation_section", improvedContext)
                }
                return ""
              })
              .then((improvedHtml) => {
                html += improvedHtml
                resolve(html)
              })
              .catch((error) => {
                console.error("Template rendering error:", error)
                resolve(trustgrade.renderRecommendationLegacy(recommendation, localizedStrings))
              })
          } catch (error) {
            console.error("Recommendation rendering error:", error)
            resolve(`<div class="alert alert-danger">${recommendationError}</div>`)
          }
        })
      })
    },

    /**
     * Legacy fallback rendering method with localized strings
     */
    renderRecommendationLegacy: (recommendation, strings) => {
      const table = recommendation.table || {}
      const rows = Array.isArray(table.rows) ? table.rows : []
      const tableTitle = table.title || strings.criteria_evaluation

      const evalText =
        recommendation.EvaluationText && recommendation.EvaluationText.content
          ? String(recommendation.EvaluationText.content)
          : ""
      const improved =
        recommendation.ImprovedAssignment && recommendation.ImprovedAssignment.content
          ? String(recommendation.ImprovedAssignment.content)
          : ""

      let html = ""

      // Table section with modern styling
      html += `<div class="tg-section tg-table-section mb-4">`
      if (tableTitle) {
        html += `<h4 class="mb-3 text-primary fw-semibold">${trustgrade.escapeHtml(tableTitle)}</h4>`
      }
      html += `
        <div class="table-responsive shadow-sm rounded">
          <table class="table table-hover table-striped mb-0 modern-criteria-table">
            <thead class="table-dark">
              <tr>
                <th scope="col" class="fw-semibold">${trustgrade.escapeHtml(strings.criterion)}</th>
                <th scope="col" class="fw-semibold text-center" style="width: 100px;">${trustgrade.escapeHtml(strings.met)}</th>
                <th scope="col" class="fw-semibold">${trustgrade.escapeHtml(strings.suggestions)}</th>
              </tr>
            </thead>
            <tbody>
      `
      if (rows.length > 0) {
        rows.forEach((r) => {
          const c = trustgrade.escapeHtml(r["Criterion"] ?? "")
          const m = trustgrade.escapeHtml(r["Met"] ?? r["Met (y/n)"] ?? "")
          const s = r["Suggestions"] ?? ""
          const metValue = m.toLowerCase()

          let metBadge = `<span class="badge bg-primary rounded-pill">${m}</span>`
          if (metValue === "yes" || metValue === "y" || metValue === "true") {
            metBadge = `<span class="badge bg-success rounded-pill"><i class="fa fa-check me-1"></i>${m}</span>`
          } else if (metValue === "no" || metValue === "n" || metValue === "false") {
            metBadge = `<span class="badge bg-danger rounded-pill"><i class="fa fa-times me-1"></i>${m}</span>`
          } else if (metValue === "partial" || metValue === "partially" || metValue === "maybe") {
            metBadge = `<span class="badge bg-warning rounded-pill"><i class="fa fa-minus me-1"></i>${m}</span>`
          }

          html += `
              <tr class="criteria-row">
                <td class="criterion-cell"><div class="fw-medium text-dark">${c}</div></td>
                <td class="met-cell text-center">${metBadge}</td>
                <td class="suggestions-cell"><div class="text-muted small">${s}</div></td>
              </tr>
          `
        })
      } else {
        html += `
              <tr>
                <td colspan="3" class="text-center text-muted py-4">
                  <i class="fa fa-info-circle me-2"></i>${trustgrade.escapeHtml(strings.no_criteria_provided)}
                </td>
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
      if (evalText) {
        html += `<div class="tg-section tg-eval-text mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-0 py-3">
              <h4 class="card-title mb-0 d-flex align-items-center">
                <i class="fa fa-clipboard-check me-2 text-primary"></i>
                ${trustgrade.escapeHtml(strings.evaluation)}
              </h4>
            </div>
            <div class="card-body">
              <div class="recommendation-content">${evalText.replace(/\n/g, "<br>")}</div>
            </div>
          </div>
        </div>`
      }

      // Improved Assignment section
      if (improved) {
        html += `<div class="tg-section tg-improved mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-0 py-3">
              <h4 class="card-title mb-0 d-flex align-items-center">
                <i class="fa fa-lightbulb me-2 text-primary"></i>
                ${trustgrade.escapeHtml(strings.improved_assignment)}
              </h4>
            </div>
            <div class="card-body">
              <div class="recommendation-content">${improved.replace(/\n/g, "<br>")}</div>
            </div>
          </div>
        </div>`
      }

      return html
    },

    checkInstructions: function () {
      var instructions = this.getInstructions()
      // Allow empty instructions to proceed

      $("#check-instructions-btn").prop("disabled", true)
      $("#ai-loading").show()
      $("#ai-recommendation-container").hide()

      var cmid = this.getCourseModuleId()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_check_instructions",
          args: {
            cmid: cmid,
            instructions: instructions || "", // Ensure we pass empty string instead of null/undefined
            intro_itemid: trustgrade.getIntroEditorItemId(),
            intro_attachments_itemid: trustgrade.getIntroAttachmentsItemId(),
          },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
            try {
              var recObj =
                typeof response.recommendation === "string"
                  ? JSON.parse(response.recommendation)
                  : response.recommendation

              trustgrade.renderRecommendation(recObj).then((recommendationHtml) => {
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
              })
            } catch (e) {
              // Fallback to legacy plaintext if JSON parsing fails
              const recommendationHtml = String(response.recommendation || "").replace(/\n/g, "<br>")
              $("#ai-recommendation").html(recommendationHtml)
              $("#ai-recommendation-container").show()
            }
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
      // Allow empty instructions to proceed

      $("#generate-questions-btn").prop("disabled", true)
      $("#ai-question-loading").show()

      var cmid = this.getCourseModuleId()

      var promises = Ajax.call([
        {
          methodname: "local_trustgrade_generate_questions",
          args: {
            cmid: cmid,
            instructions: instructions || "", // Ensure we pass empty string instead of null/undefined
            intro_itemid: trustgrade.getIntroEditorItemId(),
            intro_attachments_itemid: trustgrade.getIntroAttachmentsItemId(),
          },
        },
      ])

      promises[0]
        .done((response) => {
          if (response.success) {
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
