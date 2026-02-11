// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Task indicator module for showing pending async tasks
 *
 * @module     local_trustgrade/task_indicator
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification", "core/str"], ($, Ajax, Notification, Str) => {
  var TaskIndicator = {
    /** @type {Object.<string, jQuery>} key (cmid_submissionid) -> indicator element */
    indicatorElements: {},
    containerElement: null,
    lastCheckTime: null,
    recheckTimeout: null,
    visibilityChangeHandler: null,
    isPolling: false,

    /**
     * Unique key for a task (one indicator per submission)
     */
    getTaskKey: function (task) {
      return (task.cmid || '') + '_' + (task.submission_id ?? '')
    },

    /**
     * Initialize the task indicator (one modal/indicator per submission)
     */
    init: function () {
      this.ensureContainer()

      // Do a lightweight check to see if user has any pending tasks
      this.checkHasPendingTasks()

      // Listen for storage events (when another tab/window sets the flag)
      $(window).on('storage.trustgrade', (e) => {
        if (e.originalEvent.key === 'trustgrade_has_active_task' && e.originalEvent.newValue === 'true') {
          console.log('[TrustGrade] Active task detected in another tab, starting polling')
          this.startPolling()
        }
      })
    },

    /**
     * Check if user has any pending tasks (lightweight check)
     */
    checkHasPendingTasks: function() {
      Ajax.call([
        {
          methodname: "local_trustgrade_has_pending_tasks",
          args: {},
          done: function (response) {
            if (response.success && response.has_tasks) {
              console.log('[TrustGrade] User has pending tasks, starting polling')
              this.startPolling()
            } else {
              console.log('[TrustGrade] No pending tasks found, will not poll')
              // Clear localStorage flag if set
              localStorage.removeItem('trustgrade_has_active_task')
            }
          }.bind(this),
          fail: ((error) => {
            console.error("[TrustGrade] Error checking for pending tasks:", error)
          }).bind(this),
        },
      ])
    },

    /**
     * Start polling for pending tasks
     */
    startPolling: function() {
      if (this.isPolling) {
        return // Already polling
      }

      this.isPolling = true
      this.lastCheckTime = Math.floor(Date.now() / 1000)

      // Start checking immediately
      this.checkPendingTasks()

      // Listen for visibility changes (when user returns to tab)
      this.visibilityChangeHandler = () => {
        if (!document.hidden) {
          console.log('[TrustGrade] Tab became visible, checking for updates')
          this.handleTaskStatusChange()
        }
      }
      document.addEventListener('visibilitychange', this.visibilityChangeHandler)

      // Listen for focus events (when user clicks on window)
      $(window).on('focus.trustgrade', () => {
        console.log('[TrustGrade] Window focused, checking for updates')
        this.handleTaskStatusChange()
      })

      // Schedule regular checks
      this.scheduleNextCheck(60000) // Check every 60 seconds
    },

    /**
     * Stop polling for pending tasks
     */
    stopPolling: function() {
      console.log('[TrustGrade] Stopping polling')
      this.isPolling = false

      if (this.recheckTimeout) {
        clearTimeout(this.recheckTimeout)
        this.recheckTimeout = null
      }

      // Remove event listeners
      if (this.visibilityChangeHandler) {
        document.removeEventListener('visibilitychange', this.visibilityChangeHandler)
        this.visibilityChangeHandler = null
      }
      $(window).off('focus.trustgrade')

      // Clear the active task flag
      localStorage.removeItem('trustgrade_has_active_task')
    },

    /**
     * Ensure the container for multiple indicators exists
     */
    ensureContainer: function () {
      if (this.containerElement && this.containerElement.length) {
        return
      }
      this.containerElement = $('<div>', {
        id: 'trustgrade-task-indicators',
        class: 'trustgrade-task-indicators-container',
      })
      $('body').append(this.containerElement)
    },

    /**
     * Create one indicator element for a task key (one per submission)
     */
    createIndicatorElement: function (taskKey) {
      var indicator = $('<div>', {
        class: 'trustgrade-task-indicator hidden',
        'data-task-key': taskKey,
      })
      indicator.html(
        '<div class="indicator-content">' +
          '   <div class="indicator-icon">' +
          '       <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">' +
          '         <circle cx="16" cy="16" r="14" fill="#4CAF50" stroke="#fff" stroke-width="2"/>' +
          '         <path d="M9 16L14 21L23 11" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>' +
          '       </svg>' +
          '   </div>' +
          '   <div class="indicator-text">' +
          '       <div class="indicator-title"></div>' +
          '       <div class="indicator-message"></div>' +
          '   </div>' +
          '   <div class="indicator-spinner">' +
          '       <i class="fa fa-spinner fa-spin"></i>' +
          '   </div>' +
          '</div>'
      )
      this.containerElement.append(indicator)
      this.indicatorElements[taskKey] = indicator
      return indicator
    },

    /**
     * Remove one indicator and forget it
     */
    removeIndicator: function (taskKey) {
      var el = this.indicatorElements[taskKey]
      if (el) {
        el.remove()
        delete this.indicatorElements[taskKey]
      }
    },

    /**
     * Hide all indicators (keep in DOM for next poll)
     */
    hideAllIndicators: function () {
      Object.keys(this.indicatorElements).forEach(function (key) {
        this.indicatorElements[key].removeClass('visible').addClass('hidden')
      }.bind(this))
    },

    /**
     * Handle task status change (triggered by visibility/focus events)
     */
    handleTaskStatusChange: function() {
      console.log('[TrustGrade] Checking for task updates')

      // Clear any pending recheck
      if (this.recheckTimeout) {
        clearTimeout(this.recheckTimeout)
        this.recheckTimeout = null
      }

      // Immediately check for pending tasks
      this.checkPendingTasks()

      // Reschedule next check
      this.scheduleNextCheck(60000)
    },

    /**
     * Schedule next check
     *
     * @param {Number} delay Delay in milliseconds
     */
    scheduleNextCheck: function(delay) {
      if (this.recheckTimeout) {
        clearTimeout(this.recheckTimeout)
      }

      this.recheckTimeout = setTimeout(() => {
        this.checkPendingTasks()
        this.scheduleNextCheck(60000) // Schedule next fallback check (60 seconds)
      }, delay)
    },

    /**
     * Check for pending tasks (one indicator per submission)
     *
     * @param {Boolean} singleCheck If true, only do one check and don't continue polling
     */
    checkPendingTasks: function (singleCheck = false) {
      Ajax.call([
        {
          methodname: "local_trustgrade_get_pending_tasks",
          args: {},
          done: function (response) {
            if (response.success && response.tasks) {
              try {
                var tasks = JSON.parse(response.tasks)
                if (tasks && tasks.length > 0) {
                  if (!this.isPolling && !singleCheck) {
                    console.log('[TrustGrade] Tasks found, starting polling')
                    this.startPolling()
                  }
                  var currentKeys = {}
                  tasks.forEach(function (task) {
                    var key = this.getTaskKey(task)
                    currentKeys[key] = true
                    var el = this.indicatorElements[key]
                    if (!el || !el.length) {
                      el = this.createIndicatorElement(key)
                    }
                    this.updateIndicatorForTask(task, el)
                  }.bind(this))
                  Object.keys(this.indicatorElements).forEach(function (key) {
                    if (!currentKeys[key]) {
                      this.removeIndicator(key)
                    }
                  }.bind(this))
                } else {
                  Object.keys(this.indicatorElements).slice().forEach(function (key) {
                    this.removeIndicator(key)
                  }.bind(this))
                  if (this.isPolling && !singleCheck) {
                    this.stopPolling()
                  }
                }
              } catch (e) {
                console.error("[TrustGrade] Error parsing tasks:", e)
                Object.keys(this.indicatorElements).slice().forEach(function (key) {
                  this.removeIndicator(key)
                }.bind(this))
              }
            } else {
              Object.keys(this.indicatorElements).slice().forEach(function (key) {
                this.removeIndicator(key)
              }.bind(this))
              if (this.isPolling && !singleCheck) {
                this.stopPolling()
              }
            }
          }.bind(this),
          fail: ((error) => {
            console.error("[TrustGrade] Error checking pending tasks:", error)
          }).bind(this),
        },
      ])
    },

    /**
     * Update one indicator element with task info (one modal per submission)
     *
     * @param {Object} task Task object
     * @param {jQuery} element Indicator DOM element
     */
    updateIndicatorForTask: function (task, element) {
      if (!element || !element.length) {
        return
      }

      var titleKey, messageKey, messageParam

      if (task.error_message && task.error_message.trim() !== '') {
        Str.get_strings([
          { key: "quiz_failed", component: "local_trustgrade" },
          { key: task.error_message, component: "local_trustgrade" }
        ]).done(function (strings) {
          element.find(".indicator-title").text(strings[0])
          element.find(".indicator-message").text(strings[1] || task.error_message)
          element.removeClass("clickable").css("cursor", "default")
          element.off("click")
          element.find(".indicator-spinner").hide()
          element.removeClass("hidden").addClass("visible")
        }.bind(this)).fail(function () {})
        return
      }

      if (task.status === "failed") {
        titleKey = "quiz_failed"
        messageKey = "quiz_failed_message"
        messageParam = task.assignment_name
      } else if (task.status === "ready") {
        titleKey = "quiz_ready"
        messageKey = "quiz_ready_message"
        messageParam = task.assignment_name
      } else {
        titleKey = "quiz_preparing"
        messageKey = "quiz_preparing_message"
        messageParam = task.assignment_name
      }

      Str.get_strings([
        { key: titleKey, component: "local_trustgrade" },
        { key: messageKey, component: "local_trustgrade", param: messageParam },
      ])
        .done(function (strings) {
          element.find(".indicator-title").text(strings[0])
          element.find(".indicator-message").text(strings[1])

          if (task.status === "ready" && task.quiz_url) {
            element.addClass("clickable").css("cursor", "pointer")
            element.off("click").on("click", () => {
              window.location.href = task.quiz_url
            })
            element.find(".indicator-spinner").hide()
          } else if (task.status === "failed") {
            element.removeClass("clickable").css("cursor", "default")
            element.off("click")
            element.find(".indicator-spinner").hide()
          } else {
            element.removeClass("clickable").css("cursor", "default")
            element.off("click")
            element.find(".indicator-spinner").show()
          }

          element.removeClass("hidden").addClass("visible")
        }.bind(this))
        .fail(function () {})
    },

    /**
     * Destroy all indicators and stop checking
     */
    destroy: function () {
      if (this.recheckTimeout) {
        clearTimeout(this.recheckTimeout)
        this.recheckTimeout = null
      }

      if (this.visibilityChangeHandler) {
        document.removeEventListener('visibilitychange', this.visibilityChangeHandler)
        this.visibilityChangeHandler = null
      }
      $(window).off('focus.trustgrade')

      Object.keys(this.indicatorElements).slice().forEach(function (key) {
        this.removeIndicator(key)
      }.bind(this))
      if (this.containerElement && this.containerElement.length) {
        this.containerElement.remove()
        this.containerElement = null
      }
    },
  }

  return {
    init: () => {
      $(document).ready(() => {
        TaskIndicator.init()
      })
    },
  }
})
