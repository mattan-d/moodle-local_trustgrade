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
 * Polls instructor generation status on question bank page and shows indicator.
 *
 * @module     local_trustgrade/question_bank_status
 * @copyright  2025 CentricApp LTD <support@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification"], ($, Ajax, Notification) => {
  var POLL_INTERVAL_MS = 3000;
  var pollTimer = null;

  function renderStatus(html) {
    var $el = $("#instructor-generation-status");
    if (!$el.length) return;
    if (html) {
      $el.html(html).show();
    } else {
      $el.empty().hide();
    }
  }

  function checkStatus(cmid) {
    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_get_instructor_generation_status",
        args: { cmid: cmid },
      },
    ])[0];

    promise
      .then(function (data) {
        if (!data.has_task) {
          renderStatus(null);
          stopPolling();
          return;
        }

        var status = data.status || "";
        var message = data.message || "";
        var error = data.error || "";

        if (status === "pending" || status === "processing") {
          renderStatus(
            '<div class="alert alert-info mb-4">' +
              '<i class="fa fa-spinner fa-spin"></i> ' +
              message +
              "</div>"
          );
          startPolling(cmid);
        } else if (status === "ready") {
          renderStatus(
            '<div class="alert alert-success mb-4">' +
              '<i class="fa fa-check-circle"></i> ' +
              message +
              "</div>"
          );
          stopPolling();
          setTimeout(function () {
            window.location.reload();
          }, 1500);
        } else if (status === "failed") {
          renderStatus(
            '<div class="alert alert-danger mb-4">' +
              '<i class="fa fa-exclamation-triangle"></i> ' +
              message +
              (error ? " " + error : "") +
              "</div>"
          );
          stopPolling();
        } else {
          renderStatus(null);
          stopPolling();
        }
      })
      .catch(function () {
        stopPolling();
      });
  }

  function startPolling(cmid) {
    stopPolling();
    pollTimer = setInterval(function () {
      checkStatus(cmid);
    }, POLL_INTERVAL_MS);
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  return {
    init: function (cmid) {
      if (!cmid) return;
      $(document).ready(function () {
        checkStatus(cmid);
      });
    },
  };
});
