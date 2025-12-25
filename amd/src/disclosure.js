// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Disclosure handler module for AI disclosure messages to students.
 *
 * @module     local_trustgrade/disclosure
 * @package    local_trustgrade
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
