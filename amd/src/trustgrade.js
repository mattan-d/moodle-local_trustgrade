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

      return trustgrade.renderRecommendationWithTemplates(recommendation)
    },

    /**
     * Render recommendation using Mustache templates and localized strings
     */
    renderRecommendationWithTemplates: (recommendation) => {
      return new Promise((resolve) => {
        // Get all required language strings
        Promise.all([
          Str.get_string("criteria_evaluation", "local_trustgrade"),
          Str.get_string("criterion", "local_trustgrade"),
          Str.get_string("met", "local_trustgrade"),
          Str.get_string("suggestions", "local_trustgrade"),
          Str.get_string("evaluation", "local_trustgrade"),
          Str.get_string("improved_assignment", "local_trustgrade"),
          Str.get_string("no_criteria_provided", "local_trustgrade"),
        ]).then((strings) => {
          const [criteriaEvaluation, criterion, met, suggestions, evaluation, improvedAssignment, noCriteriaProvided] =
            strings

          const table = recommendation.table || {}
          const rows = Array.isArray(table.rows) ? table.rows : []
          const tableTitle = table.title || criteriaEvaluation

          const evalText =
            recommendation.EvaluationText && recommendation.EvaluationText.content
              ? String(recommendation.EvaluationText.content)
              : ""
          const improved =
            recommendation.ImprovedAssignment && recommendation.ImprovedAssignment.content
              ? String(recommendation.ImprovedAssignment.content)
              : ""

          let html = ""

          if (typeof require !== "undefined") {
            require(["core/templates"], (Templates) => {
              // Prepare table context
              const tableContext = {
                title: trustgrade.escapeHtml(tableTitle),
                criterion_header: trustgrade.escapeHtml(criterion),
                met_header: trustgrade.escapeHtml(met),
                suggestions_header: trustgrade.escapeHtml(suggestions),
                has_rows: rows.length > 0,
                no_criteria_message: trustgrade.escapeHtml(noCriteriaProvided),
                rows: rows.map((r) => {
                  const metValue = r["Met"] ?? r["Met (y/n)"] ?? ""
                  return {
                    criterion: trustgrade.escapeHtml(r["Criterion"] ?? ""),
                    met: trustgrade.escapeHtml(metValue),
                    met_yes: metValue.toLowerCase().includes("yes") || metValue.toLowerCase().includes("y"),
                    met_no: metValue.toLowerCase().includes("no") || metValue.toLowerCase().includes("n"),
                    suggestions: trustgrade.escapeHtml(r["Suggestions"] ?? ""),
                  }
                }),
              }

              // Render table template
              Templates.render("local_trustgrade/recommendation_table", tableContext).then((tableHtml) => {
                html += tableHtml

                const evalContext = {
                  title: trustgrade.escapeHtml(evaluation),
                  content: trustgrade.escapeHtml(evalText).replace(/\n/g, "<br>"),
                  has_content: evalText.trim().length > 0,
                }

                Templates.render("local_trustgrade/recommendation_section", evalContext).then((evalHtml) => {
                  html += evalHtml

                  const improvedContext = {
                    title: trustgrade.escapeHtml(improvedAssignment),
                    content: trustgrade.escapeHtml(improved).replace(/\n/g, "<br>"),
                    has_content: improved.trim().length > 0,
                  }

                  Templates.render("local_trustgrade/recommendation_section", improvedContext).then((improvedHtml) => {
                    html += improvedHtml
                    resolve(html)
                  })
                })
              })
            })
          } else {
            // Fallback to legacy rendering if Templates module not available
            resolve(trustgrade.renderRecommendationLegacy(recommendation, strings))
          }
        })
      })
    },

    /**
     * Legacy fallback rendering method
     */
    renderRecommendationLegacy: (recommendation, strings) => {
      const [criteriaEvaluation, criterion, met, suggestions, evaluation, improvedAssignment, noCriteriaProvided] =
        strings

      const table = recommendation.table || {}
      const rows = Array.isArray(table.rows) ? table.rows : []
      const tableTitle = table.title || criteriaEvaluation

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
      html += `<div class="tg-section tg-table-section" style="margin-bottom:16px;">`
      if (tableTitle) {
        html += `<h4 style="margin:0 0 8px 0;">${trustgrade.escapeHtml(tableTitle)}</h4>`
      }
      html += `
        <div class="table-responsive">
          <table class="table table-striped table-hover" style="width:100%; border-collapse:collapse; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
            <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
              <tr>
                <th style="text-align:left; padding:16px 12px; font-weight: 600; border: none;">${trustgrade.escapeHtml(
                  criterion,
                )}</th>
                <th style="text-align:left; padding:16px 12px; font-weight: 600; border: none;">${trustgrade.escapeHtml(
                  met,
                )}</th>
                <th style="text-align:left; padding:16px 12px; font-weight: 600; border: none;">${trustgrade.escapeHtml(
                  suggestions,
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

          // Enhanced styling for Met column with badges
          let metDisplay = m
          if (m.toLowerCase().includes("yes") || m.toLowerCase().includes("y")) {
            metDisplay = `<span class="badge badge-success" style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size:12px;">${m}</span>`
          } else if (m.toLowerCase().includes("no") || m.toLowerCase().includes("n")) {
            metDisplay = `<span class="badge badge-warning" style="background: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px;">${m}</span>`
          }

          html += `
              <tr style="transition: background-color 0.2s ease;">
                <td style="vertical-align:top; padding:12px; border-bottom:1px solid #e9ecef; background: #fff;">${c}</td>
                <td style="vertical-align:top; padding:12px; border-bottom:1px solid #e9ecef; background: #fff;">${metDisplay}</td>
                <td style="vertical-align:top; padding:12px; border-bottom:1px solid #e9ecef; background: #fff;">${s}</td>
              </tr>
          `
        })
      } else {
        html += `
              <tr>
                <td colspan="3" style="padding:16px; color:#6c757d; text-align: center; font-style: italic; background: #f8f9fa;">${trustgrade.escapeHtml(noCriteriaProvided)}</td>
              </tr>
        `
      }
      html += `
            </tbody>
          </table>
        </div>
      </div>
      `

      // Evaluation Text section with card styling
      html += `<div class="tg-section tg-text-section" style="margin-bottom:16px;">`
      html += `<div class="card" style="border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">`
      html += `<div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6; padding: 12px 16px; border-radius: 8px 8px 0 0;">`
      html += `<h4 style="margin:0; color: #495057; font-weight: 600;">${trustgrade.escapeHtml(evaluation)}</h4>`
      html += `</div>`
      html += `<div class="card-body" style="padding: 16px; background: #fff; border-radius: 0 0 8px 8px;">`
      html += `<div style="line-height: 1.6; color: #495057;">${trustgrade.escapeHtml(evalText).replace(/\n/g, "<br>")}</div>`
      html += `</div></div></div>`

      // Improved Assignment section with card styling
      html += `<div class="tg-section tg-text-section" style="margin-bottom:8px;">`
      html += `<div class="card" style="border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">`
      html += `<div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6; padding: 12px 16px; border-radius: 8px 8px 0 0;">`
      html += `<h4 style="margin:0; color: #495057; font-weight: 600;">${trustgrade.escapeHtml(improvedAssignment)}</h4>`
      html += `</div>`
      html += `<div class="card-body" style="padding: 16px; background: #fff; border-radius: 0 0 8px 8px;">`
      html += `<div style="line-height: 1.6; color: #495057;">${trustgrade.escapeHtml(improved).replace(/\n/g, "<br>")}</div>`
      html += `</div></div></div>`

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
            try {
              var recObj =
                typeof response.recommendation === "string"
                  ? JSON.parse(response.recommendation)
                  : response.recommendation

              const renderPromise = trustgrade.renderRecommendation(recObj)

              // Handle both Promise and direct string returns for backward compatibility
              if (renderPromise && typeof renderPromise.then === "function") {
                renderPromise.then((recommendationHtml) => {
                  trustgrade.displayRecommendation(recommendationHtml, response.from_cache)
                })
              } else {
                trustgrade.displayRecommendation(renderPromise, response.from_cache)
              }
            } catch (e) {
              // Fallback to legacy plaintext if JSON parsing fails
              const recommendationHtml = String(response.recommendation || "").replace(/\n/g, "<br>")
              trustgrade.displayRecommendation(recommendationHtml, response.from_cache)
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

    /**
     * Display recommendation HTML with cache notification if applicable
     */
    displayRecommendation: (recommendationHtml, fromCache) => {
      if (fromCache) {
        Str.get_string("cache_hit", "local_trustgrade").then((cacheMessage) => {
          const finalHtml =
            '<div class="alert alert-info mb-2"><i class="fa fa-clock-o"></i> <small>' +
            cacheMessage +
            " (Debug mode)</small></div>" +
            recommendationHtml
          $("#ai-recommendation").html(finalHtml)
        })
      } else {
        $("#ai-recommendation").html(recommendationHtml)
      }
      $("#ai-recommendation-container").show()
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
          Str.get_string("no_questions_generated", "local_trustgrade"), // Using localized string
          Str.get_string("blooms_level", "local_trustgrade"), // Using localized string
        ]).then((strings) => {
          const [sGeneratedQuestions, sQuestion, sPoints, sCorrect, sExplanation, sNoQuestions, sBlooms] = strings

          let html = "<h4>" + sGeneratedQuestions + ":</h4>"

          if (!Array.isArray(questions) || questions.length === 0) {
            html += `<div style="color:#666;">${trustgrade.escapeHtml(sNoQuestions)}</div>` // Using localized string
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
              html += `<p><strong>${trustgrade.escapeHtml(sBlooms)}:</strong> ${trustgrade.escapeHtml(
                // Using localized string
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
