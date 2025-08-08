/**
 * TrustGrade module for handling AI recommendations
 */
define(['core/str'], function(Str) {
  /**
   * Format AI recommendation for display
   * @param {string|object} recommendation
   * @returns {string}
   */
  function formatRecommendation(recommendation) {
    // Check for new structured response
    if (
      typeof recommendation === "object" &&
      recommendation !== null &&
      recommendation.table &&
      recommendation.EvaluationText &&
      recommendation.ImprovedAssignment
    ) {
      let html = '<div class="trustgrade-recommendation-wrapper">';

      // 1. Table
      if (recommendation.table.rows && recommendation.table.rows.length > 0) {
        html += `<h4>${recommendation.table.title || "Evaluation Criteria"}</h4>`;
        html += '<table class="table table-bordered table-striped generatable">';
        html +=
          '<thead class="thead-light"><tr><th scope="col">Criterion</th><th scope="col">Met</th><th scope="col">Suggestions</th></tr></thead>';
        html += "<tbody>";
        recommendation.table.rows.forEach((row) => {
          html += `<tr>
            <td>${row.Criterion || ""}</td>
            <td>${row.Met || ""}</td>
            <td>${row.Suggestions || ""}</td>
          </tr>`;
        });
        html += "</tbody></table>";
      }

      // 2. Evaluation Text
      if (recommendation.EvaluationText.content) {
        html += `<h4 class="mt-4">Evaluation Summary</h4>`;
        html += `<div class="card card-body bg-light mb-3">${recommendation.EvaluationText.content.replace(
          /\n/g,
          "<br>",
        )}</div>`;
      }

      // 3. Improved Assignment Text
      if (recommendation.ImprovedAssignment.content) {
        html += `<h4 class="mt-4">Suggested Improvement</h4>`;
        html += `<div class="card card-body bg-light">${recommendation.ImprovedAssignment.content.replace(
          /\n/g,
          "<br>",
        )}</div>`;
      }

      html += "</div>";
      return html;
    }

    // Fallback for old response format (string or simple object)
    let content = "";
    if (typeof recommendation === "object" && recommendation !== null) {
      content = recommendation.content || recommendation.recommendation || JSON.stringify(recommendation, null, 2);
    } else {
      content = recommendation;
    }

    return (
      "<h5>" +
      Str.get_string("ai_recommendation", "local_trustgrade") +
      "</h5>" +
      "<p>" +
      String(content).replace(/\n/g, "<br>") +
      "</p>"
    );
  }

  // Export the function for use in other modules
  return {
    formatRecommendation: formatRecommendation
  };
});
