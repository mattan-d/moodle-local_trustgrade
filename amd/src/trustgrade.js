// amd/src/trustgrade.js

function checkInstructions() {
  var promise = getRecommendation() // Assume this function fetches the recommendation
  var $resultContainer = $("#result-container") // Assume this is the container for displaying results

  promise
    .done((response) => {
      if (response.success) {
        var recommendationData
        try {
          // Try to parse the recommendation as JSON
          recommendationData = JSON.parse(response.recommendation)
        } catch (e) {
          // If it fails, it's likely plain text
          recommendationData = null
        }

        var content = ""
        if (recommendationData && typeof recommendationData === "object") {
          // Handle structured JSON response
          content += "<h4>AI Recommendation</h4>"

          // 1. Render Table
          if (recommendationData.table && recommendationData.table.rows && recommendationData.table.rows.length > 0) {
            if (recommendationData.table.title) {
              content += "<h5>" + $("<div>").text(recommendationData.table.title).html() + "</h5>"
            }
            content += '<table class="table table-striped table-bordered generictable">'
            content += "<thead><tr>"
            content += "<th>Criterion</th>"
            content += "<th>Met (y/n)</th>"
            content += "<th>Suggestions</th>"
            content += "</tr></thead>"
            content += "<tbody>"
            recommendationData.table.rows.forEach((row) => {
              content += "<tr>"
              content +=
                "<td>" +
                $("<div>")
                  .text(row.Criterion || "")
                  .html() +
                "</td>"
              content +=
                "<td>" +
                $("<div>")
                  .text(row["Met (y/n)"] || "")
                  .html() +
                "</td>"
              content +=
                "<td>" +
                $("<div>")
                  .text(row.Suggestions || "")
                  .html() +
                "</td>"
              content += "</tr>"
            })
            content += "</tbody></table>"
          }

          // 2. Render Evaluation Text
          if (recommendationData.EvaluationText && recommendationData.EvaluationText.content) {
            content += "<h5>Evaluation Summary</h5>"
            var evalText = $("<div>").text(recommendationData.EvaluationText.content).html()
            content += '<div class="recommendation-content">' + evalText.replace(/\n/g, "<br>") + "</div>"
          }

          // 3. Render Improved Assignment
          if (recommendationData.ImprovedAssignment && recommendationData.ImprovedAssignment.content) {
            content += "<h5>Improved Assignment Instructions</h5>"
            var improvedText = $("<div>").text(recommendationData.ImprovedAssignment.content).html()
            content += '<pre class="p-2 bg-light border rounded">' + improvedText + "</pre>"
          }
        } else {
          // Fallback to plain text for backward compatibility or if JSON parsing fails
          content =
            '<p class="mb-1"><strong>AI Recommendation:</strong></p>' +
            '<div class="recommendation-content">' +
            response.recommendation.replace(/\n/g, "<br>") +
            "</div>"
        }

        if (response.from_cache) {
          content += '<div class="text-muted small mt-1"><em>(This response was retrieved from cache)</em></div>'
        }
        $resultContainer.html(content).addClass("alert-success").removeClass("hidden")
      } else {
        $resultContainer
          .html("<strong>Error:</strong> " + response.error)
          .addClass("alert-danger")
          .removeClass("hidden")
      }
    })
    .fail((error) => {
      $resultContainer
        .html("<strong>Error:</strong> Failed to fetch recommendation")
        .addClass("alert-danger")
        .removeClass("hidden")
    })
}

// Assume this function fetches the recommendation
function getRecommendation() {
  return $.ajax({
    url: "/api/recommendation",
    method: "GET",
    dataType: "json",
  })
}
