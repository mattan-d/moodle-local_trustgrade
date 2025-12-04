// This file is part of Moodle - http://moodle.org/

var define = window.define
var M = window.M

define(["jquery", "core/notification"], ($, Notification) => {
  var Disclosure = {
    init: function (cmid, disclosureHtml) {
      this.cmid = cmid || 0
      this.disclosureHtml = disclosureHtml || ""
      this.injectDisclosure()
    },

    injectDisclosure: function () {
      if (!this.disclosureHtml) {
        return
      }

      // Wait for DOM to be ready
      $(document).ready(() => {
        this.insertDisclosureIntoForm()
        this.bindEvents()
      })
    },

    insertDisclosureIntoForm: function () {
      // Find the submission form
      var $form = $("form.mform, form[data-form-type='submission'], #region-main form").first()

      if ($form.length > 0) {
        // Check if disclosure already exists
        if ($form.find(".ai-disclosure-container").length > 0) {
          return // Already inserted
        }

        // Find the best insertion point
        var $insertionPoint = $form.find(".fitem").first()

        if ($insertionPoint.length === 0) {
          $insertionPoint = $form.find(".form-group").first()
        }

        if ($insertionPoint.length === 0) {
          $insertionPoint = $form.children().first()
        }

        if ($insertionPoint.length > 0) {
          // Insert disclosure before the first form element
          $insertionPoint.before(this.disclosureHtml)
        } else {
          // Fallback: prepend to form
          $form.prepend(this.disclosureHtml)
        }
      }
    },

    bindEvents: () => {
      // Add click handlers for collapsible content
      $(document).on("click", ".ai-disclosure-toggle", function (e) {
        e.preventDefault()
        var targetId = $(this).data("target")
        var $target = $(targetId)
        var $icon = $(this).find("i")

        if ($target.length > 0) {
          if ($target.is(":visible")) {
            $target.slideUp(200)
            $icon.removeClass("fa-chevron-up").addClass("fa-chevron-down")
          } else {
            $target.slideDown(200)
            $icon.removeClass("fa-chevron-down").addClass("fa-chevron-up")
          }
        }
      })

      // Add hover effects
      $(document).on("mouseenter", ".ai-disclosure-toggle", function () {
        $(this).css("text-decoration", "underline")
      })

      $(document).on("mouseleave", ".ai-disclosure-toggle", function () {
        $(this).css("text-decoration", "none")
      })
    },
  }

  return Disclosure
})
