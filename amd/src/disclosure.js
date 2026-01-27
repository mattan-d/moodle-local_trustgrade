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
      console.log("[AI Disclosure] Running insertDisclosureIntoForm...")
      
      // Find all forms
      var $forms = $("form.mform, form[data-form-type='submission'], #region-main form")
      console.log("[AI Disclosure] Total forms found:", $forms.length)
      
      // Filter out navigation and search forms, and find the right form
      var $form = $forms.not(".searchform-navbar, .form-inline, .navbar-form").filter(function() {
        // Prioritize forms with fieldset (typical question/answer forms)
        return $(this).find("fieldset").length > 0
      }).first()
      
      // Fallback: if no form with fieldset, get the first non-search form
      if ($form.length === 0) {
        $form = $forms.not(".searchform-navbar, .form-inline, .navbar-form").first()
      }
      
      console.log("[AI Disclosure] Selected form:", $form.attr("id") || $form.attr("class") || "unknown")
      console.log("[AI Disclosure] Form found:", $form.length > 0)

      if ($form.length > 0) {
        // Check if disclosure already exists
        if ($form.find(".ai-disclosure-container").length > 0) {
          console.log("[AI Disclosure] Disclosure already exists — skipping")
          return // Already inserted
        }

        console.log("[AI Disclosure] Disclosure not found — inserting...")
        
        // Strategy 1: Try to find .fitem
        var $insertionPoint = $form.find(".fitem").first()
        console.log("[AI Disclosure] Looking for .fitem - found:", $insertionPoint.length)

        // Strategy 2: Try to find .form-group
        if ($insertionPoint.length === 0) {
          console.log("[AI Disclosure] No .fitem found, checking .form-group...")
          $insertionPoint = $form.find(".form-group").first()
          console.log("[AI Disclosure] Looking for .form-group - found:", $insertionPoint.length)
        }

        // Strategy 3: Try fieldset > div (common Moodle form structure)
        if ($insertionPoint.length === 0) {
          console.log("[AI Disclosure] No .form-group found, trying fieldset > div...")
          $insertionPoint = $form.find("fieldset > div").first()
          console.log("[AI Disclosure] Looking for fieldset > div - found:", $insertionPoint.length)
        }

        // Strategy 4: Try any direct form children
        if ($insertionPoint.length === 0) {
          console.log("[AI Disclosure] No fieldset > div found, trying first child...")
          $insertionPoint = $form.children().first()
          console.log("[AI Disclosure] Form children found:", $insertionPoint.length)
        }

        // Insert the disclosure
        if ($insertionPoint.length > 0) {
          console.log("[AI Disclosure] Inserting before insertion point")
          console.log("[AI Disclosure] Target form ID:", $form.attr("id"))
          console.log("[AI Disclosure] Insertion point:", $insertionPoint.get(0))
          $insertionPoint.before(this.disclosureHtml)
        } else {
          console.log("[AI Disclosure] Prepending to form")
          console.log("[AI Disclosure] Target form ID:", $form.attr("id"))
          $form.prepend(this.disclosureHtml)
        }
        
        // Verify insertion
        var $inserted = $form.find(".ai-disclosure-container")
        console.log("[AI Disclosure] Verification - disclosure inserted:", $inserted.length > 0)
        if ($inserted.length > 0) {
          console.log("[AI Disclosure] Disclosure successfully visible in form:", $form.attr("id") || "no-id")
        }
      } else {
        console.log("[AI Disclosure] No form found")
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
