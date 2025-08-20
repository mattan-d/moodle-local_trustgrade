// This file is part of Moodle - http://moodle.org/

const define = window.define // Declare the define variable

define(["jquery", "core/ajax", "core/notification", "core/str"], ($, Ajax, Notification, Str) => {
  var Quiz = {
    session: null,
    questions: [],
    settings: {},
    cmid: 0,
    submissionid: 0,
    currentQuestion: 0,
    answers: {},
    timer: null,
    timeRemaining: 0,
    attemptStarted: false,
    attemptCompleted: false,
    windowBlurCount: 0,
    maxWindowBlurs: 3,
    autoSaveInterval: null,

    init: function (session) {
      if (!session || !session.questions || session.questions.length === 0) {
        return
      }

      this.session = session
      this.questions = session.questions
      this.settings = session.settings
      this.cmid = session.cmid
      this.submissionid = session.submissionid
      this.currentQuestion = session.current_question
      this.answers = session.answers
      this.attemptStarted = session.attempt_started
      this.attemptCompleted = session.attempt_completed
      this.windowBlurCount = session.window_blur_count
      this.timeRemaining = session.time_remaining

      if (this.attemptCompleted) {
        this.showResults()
        return
      }

      if (!this.attemptStarted) {
        this.showIntegrityWarning()
      } else {
        this.resumeQuiz()
      }
    },

    resumeQuiz: function () {
      this.bindEvents()
      this.bindIntegrityEvents()
      this.startAutoSave()
      this.showQuestion(this.currentQuestion)
      this.updateCounter()
    },

    showIntegrityWarning: function () {
      Promise.all([
        Str.get_string("important_formal_assessment", "local_trustgrade"),
        Str.get_string("read_carefully", "local_trustgrade"),
        Str.get_string("one_attempt_only", "local_trustgrade"),
        Str.get_string("no_going_back", "local_trustgrade"),
        Str.get_string("no_restarts", "local_trustgrade"),
        Str.get_string("time_limits", "local_trustgrade"),
        Str.get_string("no_cheating", "local_trustgrade"),
        Str.get_string("final_grade", "local_trustgrade"),
        Str.get_string("stay_focused", "local_trustgrade"),
        Str.get_string("cannot_restart_notice", "local_trustgrade"),
        Str.get_string("understand_start_quiz", "local_trustgrade"),
      ]).then((strings) => {
        var warningHtml = `
          <div class="quiz-integrity-warning alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle"></i> ${strings[0]}</h4>
            <p><strong>${strings[1]}</strong></p>
            <ul>
              <li><strong>${strings[2]}</strong></li>
              <li><strong>${strings[3]}</strong></li>
              <li><strong>${strings[4]}</strong></li>
              <li><strong>${strings[5]}</strong></li>
              <li><strong>${strings[6]}</strong></li>
              <li><strong>${strings[7]}</strong></li>
              <li><strong>${strings[8]}</strong></li>
            </ul>
            <p class="text-danger"><strong>${strings[9]}</strong></p>
            <div class="text-center mt-3">
              <button id="start-quiz-btn" class="btn btn-danger btn-lg">
                <i class="fa fa-play"></i> ${strings[10]}
              </button>
            </div>
          </div>
        `
        $(".quiz-content").html(warningHtml)
        $(".question-counter").hide()
        $(".quiz-navigation").hide()
        this.bindStartEvent()
      })
    },

    bindStartEvent: function () {
      $(document)
        .off("click.quizstart")
        .on("click.quizstart", "#start-quiz-btn", () => {
          this.startQuizAttempt()
        })
    },

    startQuizAttempt: function () {
      var promise = Ajax.call([
        {
          methodname: "local_trustgrade_start_quiz_attempt",
          args: {
            cmid: this.cmid,
            submissionid: this.submissionid,
          },
        },
      ])[0]

      promise
        .done((response) => {
          if (response.success) {
            this.attemptStarted = true
            Str.get_string("quiz_started_notice", "local_trustgrade").then((message) => {
              Notification.addNotification({ message: message, type: "info" })
            })
            this.resumeQuiz()
          } else {
            Str.get_string("failed_start_session", "local_trustgrade").then((message) => {
              Notification.addNotification({ message: response.error || message, type: "error" })
            })
          }
        })
        .fail(Notification.exception)
    },

    startAutoSave: function () {
      this.autoSaveInterval = setInterval(() => {
        this.saveSessionState()
      }, 10000)
    },

    saveSessionState: function () {
      if (!this.attemptStarted || this.attemptCompleted) {
        return
      }

      var updates = {
        current_question: this.currentQuestion,
        answers: this.answers,
        time_remaining: this.timeRemaining,
        window_blur_count: this.windowBlurCount,
      }

      Ajax.call([
        {
          methodname: "local_trustgrade_update_quiz_session",
          args: {
            cmid: this.cmid,
            submissionid: this.submissionid,
            updates: JSON.stringify(updates),
          },
        },
      ])
    },

    bindEvents: function () {
      $(document).off("click.quiz")
      $(document).on("click.quiz", "#next-btn", () => {
        if (this.validateCurrentAnswer()) {
          this.saveCurrentAnswer()
          this.advanceToNextQuestion()
        }
      })
      $(document).on("click.quiz", "#finish-btn", () => {
        if (this.validateCurrentAnswer()) {
          this.saveCurrentAnswer()
          this.finishQuiz()
        }
      })
      $(document).on("change.quiz", 'input[name="answer"]', () => {
        this.updateNavigationButtons()
        this.saveSessionState()
      })
      $(document).on("input.quiz", 'textarea[name="answer"]', () => {
        this.updateNavigationButtons()
        this.saveSessionState()
      })
    },

    bindIntegrityEvents: function () {
      $(window).on("blur.quiz", () => {
        if (this.attemptStarted && !this.attemptCompleted) {
          this.windowBlurCount++
          this.logIntegrityViolation("window_blur", { count: this.windowBlurCount })
          if (this.windowBlurCount >= this.maxWindowBlurs) {
            this.showIntegrityViolation()
          } else {
            Str.get_string("window_switching_warning", "local_trustgrade", {
              count: this.windowBlurCount,
              max: this.maxWindowBlurs,
            }).then((message) => {
              Notification.addNotification({ message: message, type: "warning" })
            })
          }
        }
      })
      $(document).on("contextmenu.quiz", () => {
        if (this.attemptStarted && !this.attemptCompleted) {
          this.logIntegrityViolation("right_click_attempt")
          return false
        }
      })
      $(document).on("keydown.quiz", (e) => {
        if (this.attemptStarted && !this.attemptCompleted) {
          if (
            e.keyCode === 123 ||
            (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
            (e.ctrlKey && e.keyCode === 85)
          ) {
            e.preventDefault()
            this.logIntegrityViolation("dev_tools_attempt")
            Str.get_string("dev_tools_blocked", "local_trustgrade").then((message) => {
              Notification.addNotification({ message: message, type: "error" })
            })
            return false
          }
        }
      })
      $(window).on("beforeunload.quiz", () => {
        if (this.attemptStarted && !this.attemptCompleted) {
          return Str.get_string("quiz_progress_saved", "local_trustgrade").then((message) => message)
        }
      })
    },

    logIntegrityViolation: function (violationType, violationData = {}) {
      Ajax.call([
        {
          methodname: "local_trustgrade_log_integrity_violation",
          args: {
            cmid: this.cmid,
            submissionid: this.submissionid,
            violation_type: violationType,
            violation_data: JSON.stringify(violationData),
          },
        },
      ])
    },

    getQuestionText: (q) => {
      if (!q) return ""
      return q.text || q.question || ""
    },

    getOptionText: (option) => {
      if (option == null) return ""
      if (typeof option === "string") return option
      if (typeof option === "object") {
        return option.text ?? option.label ?? String(option)
      }
      return String(option)
    },

    getOptionExplanation: (question, index) => {
      if (!question) return ""
      const options = question.options || []
      const i = typeof index === "number" ? index : Number.parseInt(index, 10)
      if (Number.isNaN(i) || i == null) return ""

      // New pattern: explanation on the option object
      if (Array.isArray(options) && options[i] && typeof options[i] === "object" && "explanation" in options[i]) {
        return options[i].explanation || ""
      }

      // Alternate pattern: explanations array aligning to options index
      if (Array.isArray(question.explanations)) {
        return question.explanations[i] || ""
      }

      // Alternate pattern: map object, e.g., { "true": "...", "false": "..." }
      if (question.explanations && typeof question.explanations === "object") {
        // Try boolean-like keys, then index keys
        const key = i === 1 ? "true" : i === 0 ? "false" : String(i)
        return question.explanations[key] || question.explanations[String(i)] || ""
      }

      // Fallback
      if (question.option_explanations && Array.isArray(question.option_explanations)) {
        return question.option_explanations[i] || ""
      }

      return ""
    },

    getCorrectAnswerIndex: (question) => {
      if (!question) return null

      // Backward-compatible: numeric index
      if (Number.isInteger(question.correct_answer)) {
        return question.correct_answer
      }

      // New pattern: detect correct option by flag
      const options = question.options || []
      for (let i = 0; i < options.length; i++) {
        const opt = options[i]
        if (
          opt &&
          typeof opt === "object" &&
          (opt.correct === true || opt.is_correct === true || opt.isCorrect === true)
        ) {
          return i
        }
      }
      return null
    },

    isAnswerCorrect: function (question, userAnswer) {
      if (!question) return false

      if (question.type === "true_false") {
        // Backward-compat: boolean compare
        if (typeof question.correct_answer !== "boolean") return false
        let userBool = null
        if (userAnswer === true || userAnswer === "true" || userAnswer === 1 || userAnswer === "1") userBool = true
        else if (userAnswer === false || userAnswer === "false" || userAnswer === 0 || userAnswer === "0")
          userBool = false
        return userBool !== null && userBool === question.correct_answer
      }

      const correctIndex = this.getCorrectAnswerIndex(question)
      if (correctIndex == null) return false
      const userIndex = typeof userAnswer === "number" ? userAnswer : Number.parseInt(userAnswer, 10)
      return userIndex === correctIndex
    },

    advanceToNextQuestion: function () {
      if (this.currentQuestion < this.questions.length - 1) {
        this.currentQuestion++
        this.showQuestion(this.currentQuestion)
        this.updateCounter()
        this.saveSessionState()
      }
    },

    showQuestion: function (index) {
      var question = this.questions[index]
      Promise.all([
        Str.get_string(
          "quiz_progress_complete",
          "local_trustgrade",
          Math.round(((index + 1) / this.questions.length) * 100),
        ),
        Str.get_string("question_x_of_y", "local_trustgrade", { current: index + 1, total: this.questions.length }),
        Str.get_string("instructor_question", "local_trustgrade"),
        Str.get_string("based_on_submission", "local_trustgrade"),
        Str.get_string("progress_auto_saved", "local_trustgrade"),
        Str.get_string("true", "local_trustgrade"),
        Str.get_string("false", "local_trustgrade"),
        Str.get_string("enter_answer_placeholder", "local_trustgrade"),
      ]).then((strings) => {
        var progress = Math.round(((index + 1) / this.questions.length) * 100)
        var html = `<div class="quiz-progress mb-3">
          <div class="progress">
            <div class="progress-bar bg-primary" style="width: ${progress}%"></div>
          </div>
          <small class="text-muted">${strings[1]} (${strings[0]})</small>
        </div>
        <div class="question-container">
          <div class="question-header">
            <span class="question-source badge ${question.source === "instructor" ? "badge-primary" : "badge-success"}">
              ${question.source === "instructor" ? strings[2] : strings[3]}
            </span>
          </div>
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            <small>${strings[4]}</small>
          </div>
          <h3 class="question-text">${this.getQuestionText(question)}</h3>`

        if (question.type === "multiple_choice" && question.options) {
          html += `<div class="question-options">`
          question.options.forEach((option, optIndex) => {
            var checked = this.answers[index] === optIndex ? "checked" : ""
            var label = this.getOptionText(option)
            html += `<div class="form-check">
              <input class="form-check-input" type="radio" name="answer" value="${optIndex}" id="option_${optIndex}" ${checked}>
              <label class="form-check-label" for="option_${optIndex}">${label}</label>
            </div>`
          })
          html += `</div>`
        } else if (question.type === "true_false") {
          var trueChecked = this.answers[index] === true ? "checked" : ""
          var falseChecked = this.answers[index] === false ? "checked" : ""
          html += `<div class="question-options">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="answer" value="true" id="true_option" ${trueChecked}>
              <label class="form-check-label" for="true_option">${strings[5]}</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="answer" value="false" id="false_option" ${falseChecked}>
              <label class="form-check-label" for="false_option">${strings[6]}</label>
            </div>
          </div>`
        } else if (question.type === "short_answer") {
          var savedAnswer = this.answers[index] || ""
          html += `<div class="question-options">
            <textarea class="form-control" name="answer" rows="4" placeholder="${strings[7]}">${savedAnswer}</textarea>
          </div>`
        }
        html += `</div>`
        $(".quiz-content").html(html)
        $(".question-counter").show()
        $(".quiz-navigation").show()
        this.updateNavigationButtons()
        if (this.settings.show_countdown) {
          this.startTimer()
        }
      })
    },

    startTimer: function () {
      if (!this.settings.show_countdown) return
      if (this.timer) clearInterval(this.timer)
      if (this.timeRemaining <= 0 || this.timeRemaining > this.settings.time_per_question) {
        this.timeRemaining = this.settings.time_per_question
      }
      this.updateTimerDisplay()
      $(".question-timer").show()
      this.timer = setInterval(() => {
        this.timeRemaining--
        this.updateTimerDisplay()
        if (this.timeRemaining <= 0) {
          this.stopTimer()
          this.autoAdvance()
        }
      }, 1000)
    },

    stopTimer: function () {
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
      $(".question-timer").hide()
    },

    updateTimerDisplay: function () {
      var minutes = Math.floor(this.timeRemaining / 60)
      var seconds = this.timeRemaining % 60
      var timeString = minutes + ":" + (seconds < 10 ? "0" : "") + seconds
      Str.get_string("time_remaining", "local_trustgrade", timeString).then((message) => {
        var timerClass = this.timeRemaining <= 5 ? "timer-warning" : ""
        var timerHtml = `<div class="timer-display ${timerClass}">
                          <i class="fa fa-clock-o"></i> ${message}
                        </div>`
        $(".question-timer").html(timerHtml)
      })
    },

    autoAdvance: function () {
      this.saveCurrentAnswer()
      if (this.currentQuestion < this.questions.length - 1) {
        this.advanceToNextQuestion()
      } else {
        this.finishQuiz()
      }
    },

    updateCounter: function () {
      var total = this.questions.length
      var current = this.currentQuestion + 1
      Str.get_string("question_x_of_y", "local_trustgrade", { current: current, total: total }).then((message) => {
        $(".question-counter").html(message)
      })
    },

    updateNavigationButtons: function () {
      $("#prev-btn").hide()
      Promise.all([
        Str.get_string("next_question", "local_trustgrade"),
        Str.get_string("submit_final_answers", "local_trustgrade"),
      ]).then((strings) => {
        if (this.currentQuestion < this.questions.length - 1) {
          $("#next-btn").show().text(strings[0])
          $("#finish-btn").hide()
        } else {
          $("#next-btn").hide()
          $("#finish-btn").show().text(strings[1])
        }
      })
    },

    validateCurrentAnswer: function () {
      var question = this.questions[this.currentQuestion]
      var hasAnswer = false
      if (question.type === "multiple_choice" || question.type === "true_false") {
        hasAnswer = $('input[name="answer"]:checked').length > 0
      } else if (question.type === "short_answer") {
        hasAnswer = $('textarea[name="answer"]').val().trim().length > 0
      }
      if (!hasAnswer) {
        Str.get_string("provide_answer_warning", "local_trustgrade").then((message) => {
          Notification.addNotification({ message: message, type: "warning" })
        })
        return false
      }
      return true
    },

    saveCurrentAnswer: function () {
      var question = this.questions[this.currentQuestion]
      var answer = null
      if (question.type === "multiple_choice") {
        answer = $('input[name="answer"]:checked').val()
        if (answer !== undefined) this.answers[this.currentQuestion] = Number.parseInt(answer)
      } else if (question.type === "true_false") {
        answer = $('input[name="answer"]:checked').val()
        if (answer !== undefined) this.answers[this.currentQuestion] = answer === "true"
      } else if (question.type === "short_answer") {
        answer = $('textarea[name="answer"]').val().trim()
        this.answers[this.currentQuestion] = answer
      }
      this.timeRemaining = this.settings.time_per_question
    },

    finishQuiz: function () {
      if (this.attemptCompleted) return
      this.attemptCompleted = true
      this.stopTimer()
      if (this.autoSaveInterval) clearInterval(this.autoSaveInterval)
      $(window).off(".quiz")
      $(document).off(".quiz")
      var score = this.calculateScore()
      var promise = Ajax.call([
        {
          methodname: "local_trustgrade_complete_quiz_session",
          args: {
            cmid: this.cmid,
            submissionid: this.submissionid,
            final_answers: JSON.stringify(this.answers),
            final_score: score,
          },
        },
      ])[0]

      promise
        .done((response) => {
          if (!response.success) {
            Str.get_string("failed_save_results", "local_trustgrade", response.error || "Unknown error").then(
              (message) => {
                Notification.addNotification({ message: message, type: "error" })
              },
            )
          }
          this.showResults()
        })
        .fail(() => {
          Str.get_string("failed_save_contact_instructor", "local_trustgrade").then((message) => {
            Notification.addNotification({ message: message, type: "error" })
          })
          this.showResults()
        })
    },

    calculateScore: function () {
      var score = 0
      this.questions.forEach((question, index) => {
        var userAnswer = this.answers[index]
        var isCorrect = this.isAnswerCorrect(question, userAnswer)
        var points = question.points || 10
        if (isCorrect) score += points
      })
      return score
    },

    showResults: function () {
      var score = 0
      var totalPoints = 0
      Promise.all([
        Str.get_string("quiz_completed_header", "local_trustgrade"),
        Str.get_string("quiz_completed_message", "local_trustgrade"),
        Str.get_string("correct", "local_trustgrade"),
        Str.get_string("incorrect", "local_trustgrade"),
        Str.get_string("your_answer", "local_trustgrade"),
        Str.get_string("correct_answer_was", "local_trustgrade"),
        Str.get_string("no_answer", "local_trustgrade"),
        Str.get_string("explanation", "local_trustgrade"),
        Str.get_string("final_grade_notice", "local_trustgrade"),
        Str.get_string("true", "local_trustgrade"),
        Str.get_string("false", "local_trustgrade"),
      ]).then((strings) => {
        var resultsHtml = `<div class="quiz-completion-header alert alert-success">
          <h2><i class="fa fa-check-circle"></i> ${strings[0]}</h2>
          <p>${strings[1]}</p>
        </div>
        <div class="results-summary">`

        this.questions.forEach((question, index) => {
          var userAnswer = this.answers[index]
          var isCorrect = this.isAnswerCorrect(question, userAnswer)
          var points = question.points || 10
          totalPoints += points
          if (isCorrect) score += points

          resultsHtml += `<div class="result-item ${isCorrect ? "correct" : "incorrect"}">
            <div class="result-header">
              <span class="question-number">Question ${index + 1}</span>
              <span class="result-status ${isCorrect ? "text-success" : "text-danger"}">
                ${isCorrect ? `✓ ${strings[2]}` : `✗ ${strings[3]}`}
              </span>
            </div>
            <p class="question-text">${this.getQuestionText(question)}</p>`

          // Show the user's answer text
          if (question.type === "multiple_choice") {
            var mcAnswerText = strings[6]
            if (
              userAnswer !== undefined &&
              userAnswer !== null &&
              question.options &&
              question.options[Number(userAnswer)] !== undefined
            ) {
              mcAnswerText = this.getOptionText(question.options[Number(userAnswer)])
            }
            resultsHtml += `<p><strong>${strings[4].replace("{$a}", mcAnswerText)}</strong></p>`

            // Show the explanation corresponding to the selected answer (per-answer explanation)
            var explanationText = this.getOptionExplanation(question, Number(userAnswer))
            if (explanationText) {
              resultsHtml += `<div class="explanation"><strong>${strings[7]}:</strong> ${explanationText}</div>`
            }
          } else if (question.type === "true_false") {
            var tfAnswerText = userAnswer !== undefined ? (userAnswer ? strings[9] : strings[10]) : strings[6]
            resultsHtml += `<p><strong>${strings[4].replace("{$a}", tfAnswerText)}</strong></p>`

            // Attempt to show a per-answer explanation if provided in a map or options
            var tfExplanation = ""
            if (question.explanations && typeof question.explanations === "object") {
              if (userAnswer === true || userAnswer === "true" || userAnswer === 1 || userAnswer === "1") {
                tfExplanation =
                  question.explanations.true || question.explanations["1"] || question.explanations[1] || ""
              } else if (userAnswer === false || userAnswer === "false" || userAnswer === 0 || userAnswer === "0") {
                tfExplanation =
                  question.explanations.false || question.explanations["0"] || question.explanations[0] || ""
              }
            } else if (Array.isArray(question.options) && question.options.length === 2) {
              // If options are objects, try to read explanation
              var idx = userAnswer === true || userAnswer === "true" || userAnswer === 1 || userAnswer === "1" ? 1 : 0
              if (
                question.options[idx] &&
                typeof question.options[idx] === "object" &&
                "explanation" in question.options[idx]
              ) {
                tfExplanation = question.options[idx].explanation || ""
              }
            }
            if (tfExplanation) {
              resultsHtml += `<div class="explanation"><strong>${strings[7]}:</strong> ${tfExplanation}</div>`
            }
          } else if (question.type === "short_answer") {
            resultsHtml += `<p><strong>${strings[4].replace("{$a}", userAnswer || strings[6])}</strong></p>`
            // No per-answer explanation for short answer in the new pattern
          }

          // IMPORTANT CHANGE: Do NOT show the "Correct answer was" line.

          resultsHtml += `</div>`
        })

        var percentage = totalPoints > 0 ? Math.round((score / totalPoints) * 100) : 0
        Str.get_string("final_score", "local_trustgrade", {
          score: score,
          total: totalPoints,
          percentage: percentage,
        }).then((scoreString) => {
          resultsHtml =
            `<div class="score-summary alert alert-info">
              <h3>${scoreString}</h3>
              <p><strong>${strings[8]}</strong></p>
            </div>` + resultsHtml
          resultsHtml += `</div>`
          if (this.windowBlurCount > 0) {
            Promise.all([
              Str.get_string("integrity_report_header", "local_trustgrade"),
              Str.get_string("window_focus_lost", "local_trustgrade", this.windowBlurCount),
              Str.get_string("integrity_recorded", "local_trustgrade"),
            ]).then((integrityStrings) => {
              resultsHtml += `<div class="integrity-report alert alert-warning">
                <h4><i class="fa fa-exclamation-triangle"></i> ${integrityStrings[0]}</h4>
                <p>${integrityStrings[1]}</p>
                <p><small>${integrityStrings[2]}</small></p>
              </div>`
              this.displayResults(resultsHtml)
            })
          } else {
            this.displayResults(resultsHtml)
          }
        })
      })
    },

    displayResults: (resultsHtml) => {
      $(".quiz-content").hide()
      $(".quiz-navigation").hide()
      $(".question-counter").hide()
      $(".question-timer").hide()
      $(".quiz-results").html(resultsHtml).show()
    },

    showIntegrityViolation: function () {
      this.attemptCompleted = true
      this.stopTimer()
      if (this.autoSaveInterval) clearInterval(this.autoSaveInterval)
      Promise.all([
        Str.get_string("integrity_violation_header", "local_trustgrade"),
        Str.get_string("quiz_flagged", "local_trustgrade"),
        Str.get_string("exceeded_window_switches", "local_trustgrade", this.maxWindowBlurs),
        Str.get_string("incident_logged", "local_trustgrade"),
        Str.get_string("progress_saved_cannot_continue", "local_trustgrade"),
      ]).then((strings) => {
        var violationHtml = `
          <div class="integrity-violation alert alert-danger">
            <h2><i class="fa fa-ban"></i> ${strings[0]}</h2>
            <p><strong>${strings[1]}</strong></p>
            <p>${strings[2]}</p>
            <p>${strings[3]}</p>
            <p><strong>${strings[4]}</strong></p>
          </div>
        `
        $(".quiz-content").html(violationHtml)
        $(".quiz-navigation").hide()
        $(".question-counter").hide()
        $(".question-timer").hide()
        this.logIntegrityViolation("integrity_violation", {
          violation_type: "excessive_window_blur",
          window_blur_count: this.windowBlurCount,
          current_question: this.currentQuestion,
        })
      })
    },
  }

  return Quiz
})
