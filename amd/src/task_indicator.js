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

var define = window.define // Declare the define variable
var M = window.M // Declare the M variable

define(["jquery", "core/ajax", "core/notification", "core/str"], ($, Ajax, Notification, Str) => {
  var TaskIndicator = {
    indicatorElement: null,
    checkInterval: null,
    CHECK_FREQUENCY: 10000, // Check every 10 seconds

    /**
     * Initialize the task indicator
     */
    init: function () {
      this.createIndicatorElement()
      this.checkPendingTasks()

      // Set up periodic checking
      this.checkInterval = setInterval(
        function () {
          this.checkPendingTasks()
        }.bind(this),
        this.CHECK_FREQUENCY,
      )
    },

    /**
     * Create the floating indicator element
     */
    createIndicatorElement: function () {
      var indicator = $("<div>", {
        id: "trustgrade-task-indicator",
        class: "trustgrade-task-indicator hidden",
      })

      indicator.html(
        '<div class="indicator-content">' +
          '   <div class="indicator-icon">' +
          '       <img src="' +
          M.cfg.wwwroot +
          '/local/trustgrade/public/icon-light-32x32.png" alt="TrustGrade">' +
          "   </div>" +
          '   <div class="indicator-text">' +
          '       <div class="indicator-title"></div>' +
          '       <div class="indicator-message"></div>' +
          "   </div>" +
          '   <div class="indicator-spinner">' +
          '       <i class="fa fa-spinner fa-spin"></i>' +
          "   </div>" +
          "</div>",
      )

      $("body").append(indicator)
      this.indicatorElement = indicator
    },

    /**
     * Check for pending tasks
     */
    checkPendingTasks: function () {
      Ajax.call([
        {
          methodname: "local_trustgrade_get_pending_tasks",
          args: {},
          done: function (response) {
            if (response.success && response.tasks) {
              try {
                var tasks = JSON.parse(response.tasks)
                if (tasks && tasks.length > 0) {
                  this.showIndicator(tasks[0]) // Show first pending task
                } else {
                  this.hideIndicator()
                }
              } catch (e) {
                console.error("[TrustGrade] Error parsing tasks:", e)
                this.hideIndicator()
              }
            } else {
              this.hideIndicator()
            }
          }.bind(this),
          fail: ((error) => {
            console.error("[TrustGrade] Error checking pending tasks:", error)
          }).bind(this),
        },
      ])
    },

    /**
     * Show the indicator with task info
     *
     * @param {Object} task Task object
     */
    showIndicator: function (task) {
      if (!this.indicatorElement) {
        return
      }

      // Get strings
      Str.get_strings([
        { key: "quiz_preparing", component: "local_trustgrade" },
        { key: "quiz_preparing_message", component: "local_trustgrade", param: task.assignment_name },
      ])
        .done(
          function (strings) {
            this.indicatorElement.find(".indicator-title").text(strings[0])
            this.indicatorElement.find(".indicator-message").text(strings[1])

            // Make indicator clickable if quiz is ready
            if (task.status === "completed" && task.quiz_url) {
              this.indicatorElement.addClass("clickable").css("cursor", "pointer")
              this.indicatorElement.off("click").on("click", () => {
                window.location.href = task.quiz_url
              })
              this.indicatorElement.find(".indicator-spinner").hide()
            } else {
              this.indicatorElement.removeClass("clickable").css("cursor", "default")
              this.indicatorElement.off("click")
              this.indicatorElement.find(".indicator-spinner").show()
            }

            this.indicatorElement.removeClass("hidden").addClass("visible")
          }.bind(this),
        )
        .fail((error) => {
          console.error("[TrustGrade] Error loading strings:", error)
        })
    },

    /**
     * Hide the indicator
     */
    hideIndicator: function () {
      if (this.indicatorElement) {
        this.indicatorElement.removeClass("visible").addClass("hidden")
      }
    },

    /**
     * Destroy the indicator and stop checking
     */
    destroy: function () {
      if (this.checkInterval) {
        clearInterval(this.checkInterval)
        this.checkInterval = null
      }
      if (this.indicatorElement) {
        this.indicatorElement.remove()
        this.indicatorElement = null
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
