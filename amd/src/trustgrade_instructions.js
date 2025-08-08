define([
  "jquery",
  "core/ajax",
  "core/notification",
  "core/config",
  "core/str",
], ($, Ajax, Notification, M, Str) => {
  var cmid

  /**
   * Initialize the instructions analysis functionality
   * @param {int} courseModuleId Course module ID
   */
  function init(courseModuleId) {
    cmid = courseModuleId || 0
    bindCheckInstructionsEvent()
  }

  /**
   * Bind the check instructions button event
   */
  function bindCheckInstructionsEvent() {
    $("#check-instructions-btn").on("click", function () {
      var instructions = getInstructionsText()
      if (!instructions.trim()) {
        Notification.addNotification({
          message: "No instructions found to analyze",
          type: "error",
        })
        return
      }
      checkInstructions(instructions)
    })
  }

  /**
   * Extract instructions text from the assignment
   */
  function getInstructionsText() {
    // Try multiple selectors to find assignment instructions
    var selectors = [
      "#id_assignmentintro_editor",
      "[name='assignmentintro[text]']",
      "#id_assignmentintro",
      ".assignment-intro",
      ".intro",
    ]

    for (var i = 0; i < selectors.length; i++) {
      var $element = $(selectors[i])
      if ($element.length) {
        var text = ""
        if ($element.is("textarea") || $element.is("input")) {
          text = $element.val()
        } else if ($element.hasClass("editor_atto_content")) {
          text = $element.html()
        } else {
          text = $element.text() || $element.html()
        }
        if (text && text.trim()) {
          return text.trim()
        }
      }
    }

    // Fallback: try to get any visible text content
    var $content = $(".assignment-content, .mod-assign-content, .generalbox")
    if ($content.length) {
      return $content.first().text().trim()
    }

    return ""
  }

  /**
   * Send instructions to AI for analysis
   * @param {string} instructions The assignment instructions
   */
  function checkInstructions(instructions) {
    var $button = $("#check-instructions-btn")
    var originalText = $button.html()

    // Update button state
    $button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Processing...')

    var promise = Ajax.call([
      {
        methodname: "local_trustgrade_check_instructions",
        args: {
          cmid: cmid,
          instructions: instructions,
        },
      },
    ])[0]

    promise
      .done(function (response) {
        if (response.success) {
          displayInstructionsAnalysis(response)
        } else {
          Notification.addNotification({
            message: response.error || "Error analyzing instructions",
            type: "error",
          })
        }
      })
      .fail(function (ex) {
        Notification.exception(ex)
      })
      .always(function () {
        $button.prop("disabled", false).html(originalText)
      })
  }

  /**
   * Display the AI analysis results with new structured format
   * @param {object} response The API response
   */
  function displayInstructionsAnalysis(response) {
    var $container = $("#ai-recommendation-container")
    
    if (!$container.length) {
      // Create container if it doesn't exist
      $container = $('<div id="ai-recommendation-container" class="trustgrade-analysis-container"></div>')
      $("#check-instructions-btn").after($container)
    }

    var html = '<div class="alert alert-info">'
    html += '<h4><i class="fa fa-lightbulb-o"></i> AI Analysis Results</h4>'

    // Handle new structured format
    if (response.table && response.evaluation_text && response.improved_assignment) {
      // Display criteria table
      if (response.table.rows && response.table.rows.length > 0) {
        html += '<div class="criteria-analysis">'
        html += '<h5>Assignment Criteria Analysis</h5>'
        html += '<div class="table-responsive">'
        html += '<table class="table table-striped table-bordered">'
        html += '<thead class="thead-light">'
        html += '<tr><th>Criterion</th><th>Met</th><th>Suggestions</th></tr>'
        html += '</thead><tbody>'
        
        response.table.rows.forEach(function(row) {
          var metClass = row['Met (y/n)'].toLowerCase() === 'y' || row['Met (y/n)'].toLowerCase() === 'yes' ? 'text-success' : 'text-danger'
          var metIcon = row['Met (y/n)'].toLowerCase() === 'y' || row['Met (y/n)'].toLowerCase() === 'yes' ? 'fa-check' : 'fa-times'
          
          html += '<tr>'
          html += '<td><strong>' + escapeHtml(row.Criterion) + '</strong></td>'
          html += '<td class="' + metClass + '"><i class="fa ' + metIcon + '"></i> ' + escapeHtml(row['Met (y/n)']) + '</td>'
          html += '<td>' + escapeHtml(row.Suggestions) + '</td>'
          html += '</tr>'
        })
        
        html += '</tbody></table></div></div>'
      }

      // Display evaluation text
      if (response.evaluation_text && response.evaluation_text.content) {
        html += '<div class="evaluation-summary">'
        html += '<h5>Evaluation Summary</h5>'
        html += '<div class="well">' + formatText(response.evaluation_text.content) + '</div>'
        html += '</div>'
      }

      // Display improved assignment
      if (response.improved_assignment && response.improved_assignment.content) {
        html += '<div class="improved-assignment">'
        html += '<h5>Suggested Improved Assignment</h5>'
        html += '<div class="well improved-text">' + formatText(response.improved_assignment.content) + '</div>'
        html += '<button type="button" class="btn btn-sm btn-secondary copy-improved-btn" data-clipboard-target=".improved-text">'
        html += '<i class="fa fa-copy"></i> Copy Improved Text</button>'
        html += '</div>'
      }
    } else if (response.recommendation) {
      // Fallback to legacy format
      html += '<div class="legacy-recommendation">'
      html += '<h5>AI Recommendation</h5>'
      html += '<div class="well">' + formatText(response.recommendation) + '</div>'
      html += '</div>'
    }

    // Add cache indicator if applicable
    if (response.from_cache) {
      html += '<small class="text-muted"><i class="fa fa-database"></i> Response from cache</small>'
    }

    html += '</div>'

    $container.html(html)
    $container.show()

    // Bind copy button event
    $container.find('.copy-improved-btn').on('click', function() {
      var text = $(this).siblings('.improved-text').text()
      copyToClipboard(text)
      
      var $btn = $(this)
      var originalText = $btn.html()
      $btn.html('<i class="fa fa-check"></i> Copied!').addClass('btn-success')
      
      setTimeout(function() {
        $btn.html(originalText).removeClass('btn-success')
      }, 2000)
    })

    // Scroll to results
    $("html, body").animate({
      scrollTop: $container.offset().top - 100,
    }, 500)
  }

  /**
   * Escape HTML characters
   * @param {string} text
   * @return {string}
   */
  function escapeHtml(text) {
    var div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }

  /**
   * Format text content, preserving line breaks and basic formatting
   * @param {string} text
   * @return {string}
   */
  function formatText(text) {
    return escapeHtml(text).replace(/\n/g, '<br>')
  }

  /**
   * Copy text to clipboard
   * @param {string} text
   */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text)
    } else {
      // Fallback for older browsers
      var textArea = document.createElement('textarea')
      textArea.value = text
      textArea.style.position = 'fixed'
      textArea.style.left = '-999999px'
      textArea.style.top = '-999999px'
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      document.execCommand('copy')
      textArea.remove()
    }
  }

  return {
    init: init,
  }
})
