define([
  "jquery",
  "core/ajax",
  "core/notification",
  "core/config",
  "core/str",
  "core/modal_factory",
  "core/modal_events",
], ($, Ajax, Notification, M, Str, ModalFactory, ModalEvents) => {
  var pendingGrades = new Map()
  var saveTimeout
  var cmid

  /**
   * Initialize the grading functionality
   * @param {int} courseModuleId Course module ID
   */
  function init(courseModuleId) {
    cmid = courseModuleId || 0

    // Bind event handlers
    bindGradeInputEvents()
    bindBulkActionEvents()

    // Load current grades
    loadCurrentGrades()
  }

  /**
   * Bind events to grade input fields
   */
  function bindGradeInputEvents() {
    $(document).on("input", ".grade-input", function () {
      var $input = $(this)
      var userid = $input.data("userid")
      var grade = $input.val()

      // Mark as pending
      pendingGrades.set(userid, grade)
      updateGradeStatus($input, "pending")
      updatePendingGradesDisplay()

      // Clear existing timeout
      if (saveTimeout) {
        clearTimeout(saveTimeout)
      }

      // Set new timeout for auto-save
      saveTimeout = setTimeout(() => {
        saveGrade(userid, grade, $input)
      }, 2000)
    })

    $(document).on("blur", ".grade-input", function () {
      var $input = $(this)
      var userid = $input.data("userid")
      var grade = $input.val()

      // Save immediately on blur
      if (pendingGrades.has(userid)) {
        if (saveTimeout) {
          clearTimeout(saveTimeout)
        }
        saveGrade(userid, grade, $input)
      }
    })

    $(document).on("keypress", ".grade-input", function (e) {
      if (e.which === 13) {
        // Enter key
        var $input = $(this)
        var userid = $input.data("userid")
        var grade = $input.val()

        if (saveTimeout) {
          clearTimeout(saveTimeout)
        }
        saveGrade(userid, grade, $input)
      }
    })
  }

  /**
   * Bind bulk action events
   */
  function bindBulkActionEvents() {
    $("#bulk-save-grades").on("click", () => {
      savePendingGrades()
    })

    $("#clear-all-grades").on("click", () => {
      showClearAllGradesModal()
    })

    $("#auto-grade-by-quiz").on("click", () => {
      showAutoGradeModal()
    })
  }

  /**
   * Show confirmation modal for clearing all grades
   */
  function showClearAllGradesModal() {
    ModalFactory.create({
      type: ModalFactory.types.SAVE_CANCEL,
      title: Str.get_string("confirm_clear_all_grades", "local_trustgrade"),
      body: Str.get_string("confirm_clear_all_grades_body", "local_trustgrade"),
    })
      .then((modal) => {
        modal.setSaveButtonText(Str.get_string("clear_all_grades", "local_trustgrade"))
        modal.getRoot().on(ModalEvents.save, () => {
          clearAllGrades()
        })
        modal.show()
        return modal
      })
      .catch(() => {
        // Fallback to native confirm if modal fails
        if (confirm("Are you sure you want to clear all grades? This action cannot be undone.")) {
          clearAllGrades()
        }
      })
  }

  /**
   * Show confirmation modal for auto-grading
   */
  function showAutoGradeModal() {
    ModalFactory.create({
      type: ModalFactory.types.SAVE_CANCEL,
      title: Str.get_string("auto_grade_by_quiz", "local_trustgrade"),
      body: Str.get_string("auto_grade_confirmation", "local_trustgrade"),
    })
      .then((modal) => {
        modal.setSaveButtonText(Str.get_string("auto_grade_by_quiz", "local_trustgrade"))
        modal.getRoot().on(ModalEvents.save, () => {
          autoGradeByQuizScore()
        })
        modal.show()
        return modal
      })
      .catch(() => {
        // Fallback to native confirm if modal fails
        if (
          confirm(
            "This will automatically set grades based on quiz scores for all students. Existing grades will be overwritten. Continue?",
          )
        ) {
          autoGradeByQuizScore()
        }
      })
  }

  /**
   * Save a single grade
   * @param {int} userid User ID
   * @param {string} grade Grade value
   * @param {jQuery} $input Input element
   */
  function saveGrade(userid, grade, $input) {
    updateGradeStatus($input, "saving")

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_save_grade",
        args: {
          cmid: cmid,
          userid: userid,
          grade: grade,
        },
      },
    ])[0]

    promise
      .done((response) => {
        if (response.success) {
          updateGradeStatus($input, "saved")
          pendingGrades.delete(userid)
          updatePendingGradesDisplay()

          // Update display with formatted grade
          if (response.formatted_grade) {
            $input.val(response.formatted_grade)
          }
        } else {
          updateGradeStatus($input, "error")
          Notification.addNotification({
            message: "Error saving grade for user " + userid + ": " + (response.message || "Unknown error"),
            type: "error",
          })
        }
      })
      .fail((ex) => {
        updateGradeStatus($input, "error")
        Notification.exception(ex)
      })
  }

  /**
   * Save all pending grades
   */
  function savePendingGrades() {
    if (pendingGrades.size === 0) {
      Notification.addNotification({
        message: "No pending grades to save.",
        type: "info",
      })
      return
    }

    var $button = $("#bulk-save-grades")
    $button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Saving...')

    // Convert Map to object for JSON encoding
    var gradesObject = {}
    pendingGrades.forEach((grade, userid) => {
      gradesObject[userid] = grade
    })

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_save_bulk_grades",
        args: {
          cmid: cmid,
          grades: JSON.stringify(gradesObject),
        },
      },
    ])[0]

    promise
      .done((response) => {
        if (response.success) {
          // Clear pending grades and update status
          pendingGrades.forEach((grade, userid) => {
            var $input = $("#grade_" + userid)
            updateGradeStatus($input, "saved")

            setTimeout(() => {
              updateGradeStatus($input, "")
            }, 3000)
          })

          pendingGrades.clear()
          updatePendingGradesDisplay()

          Notification.addNotification({
            message: response.saved_count + " grades saved successfully",
            type: "success",
          })
        } else {
          Notification.addNotification({
            message: response.message || "Error saving grades",
            type: "error",
          })
        }
      })
      .fail(Notification.exception)
      .always(() => {
        $button.prop("disabled", false).html('<i class="fa fa-save"></i> Save All Pending')
      })
  }

  /**
   * Clear all grades
   */
  function clearAllGrades() {
    var $button = $("#clear-all-grades")
    $button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Clearing...')

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_clear_all_grades",
        args: {
          cmid: cmid,
        },
      },
    ])[0]

    promise
      .done((response) => {
        if (response.success) {
          // Clear all input fields
          $(".grade-input")
            .val("")
            .each(function () {
              updateGradeStatus($(this), "")
            })

          // Clear pending grades
          pendingGrades.clear()
          updatePendingGradesDisplay()

          Notification.addNotification({
            message: "All grades cleared successfully",
            type: "success",
          })
        } else {
          Notification.addNotification({
            message: response.message || "Error clearing grades",
            type: "error",
          })
        }
      })
      .fail(Notification.exception)
      .always(() => {
        $button.prop("disabled", false).html('<i class="fa fa-eraser"></i> Clear All Grades')
      })
  }

  /**
   * Auto-grade all students based on their quiz scores
   */
  function autoGradeByQuizScore() {
    var $button = $("#auto-grade-by-quiz")
    $button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Auto-grading...')

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_auto_grade_by_quiz",
        args: {
          cmid: cmid,
        },
      },
    ])[0]

    promise
      .done((response) => {
        if (response.success) {
          // Parse the JSON grades string
          var gradesObj = {}
          try {
            if (response.grades) {
              gradesObj = JSON.parse(response.grades)
            }
          } catch (e) {
            console.error("Error parsing grades JSON:", e)
            gradesObj = {}
          }

          // Update all grade inputs with the new grades
          for (var userid in gradesObj) {
            if (gradesObj.hasOwnProperty(userid)) {
              var $input = $("#grade_" + userid)
              var grade = gradesObj[userid]
              if (grade !== null && grade !== undefined) {
                $input.val(Number.parseFloat(grade).toFixed(2))
                updateGradeStatus($input, "saved")

                // Clear the saved status after a few seconds
                setTimeout(() => {
                  updateGradeStatus($input, "")
                }, 3000)
              }
            }
          }

          // Clear any pending grades since we just applied auto-grades
          pendingGrades.clear()
          updatePendingGradesDisplay()

          Notification.addNotification({
            message: response.graded_count + " students auto-graded based on quiz scores",
            type: "success",
          })
        } else {
          Notification.addNotification({
            message: response.message || "Error auto-grading students",
            type: "error",
          })
        }
      })
      .fail(Notification.exception)
      .always(() => {
        $button.prop("disabled", false).html('<i class="fa fa-magic"></i> Auto-grade by Quiz Score')
      })
  }

  /**
   * Load current grades from server
   */
  function loadCurrentGrades() {
    var userids = []
    $(".grade-input").each(function () {
      userids.push($(this).data("userid"))
    })

    if (userids.length === 0) {
      return
    }

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_get_current_grades",
        args: {
          cmid: cmid,
          userids: userids,
        },
      },
    ])[0]

    promise
      .done((response) => {
        if (response.success && response.grades) {
          // Parse the JSON grades string
          var gradesObj = {}
          try {
            gradesObj = JSON.parse(response.grades)
          } catch (e) {
            console.error("Error parsing grades JSON:", e)
            return
          }

          for (var userid in gradesObj) {
            if (gradesObj.hasOwnProperty(userid)) {
              var $input = $("#grade_" + userid)
              var grade = gradesObj[userid]
              if (grade !== null && grade !== undefined) {
                $input.val(Number.parseFloat(grade).toFixed(2))
                updateGradeStatus($input, "saved")
              }
            }
          }
        }
      })
      .fail(() => {
        // Silently fail - grades will show as empty
      })
  }

  /**
   * Update grade status icon
   * @param {jQuery} $input Input element
   * @param {string} status Status: pending, saving, saved, error
   */
  function updateGradeStatus($input, status) {
    var $icon = $input.siblings(".input-group-append").find(".grade-status-icon")

    $icon.removeClass("text-warning text-primary text-success text-danger")
    $icon.html("")

    switch (status) {
      case "pending":
        $icon.addClass("text-warning").html('<i class="fa fa-clock-o"></i>')
        $icon.attr("title", "Grade pending save")
        break
      case "saving":
        $icon.addClass("text-primary").html('<i class="fa fa-spinner fa-spin"></i>')
        $icon.attr("title", "Saving grade...")
        break
      case "saved":
        $icon.addClass("text-success").html('<i class="fa fa-check"></i>')
        $icon.attr("title", "Grade saved")
        break
      case "error":
        $icon.addClass("text-danger").html('<i class="fa fa-exclamation-triangle"></i>')
        $icon.attr("title", "Error saving grade")
        break
    }
  }

  /**
   * Update pending grades count display
   */
  function updatePendingGradesDisplay() {
    var count = pendingGrades.size
    var $counter = $("#pending-grades-count")
    var $bulkButton = $("#bulk-save-grades")

    if (count > 0) {
      $counter.text(count + " unsaved change" + (count === 1 ? "" : "s")).show()
      $bulkButton.show()
    } else {
      $counter.hide()
      $bulkButton.hide()
    }
  }

  return {
    init: init,
  }
})
