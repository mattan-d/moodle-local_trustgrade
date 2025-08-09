define(["jquery"], ($) => {
  const tryParseJSON = (input) => {
    if (typeof input !== "string") return null
    try {
      const obj = JSON.parse(input)
      return obj && typeof obj === "object" ? obj : null
    } catch (e) {
      return null
    }
  }

  const escapeHtml = (str) => {
    return String(str ?? "").replace(/[&<>"'`]/g, (c) => {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;", "`": "&#96;" }[c]
    })
  }

  const renderTable = (table) => {
    const titleHtml = table && table.title ? `<h4 class="mb-2">${escapeHtml(table.title)}</h4>` : ""
    const rows = Array.isArray(table?.rows) ? table.rows : []
    const head = ["Criterion", "Met (y/n)", "Suggestions"].map((h) => `<th scope="col">${escapeHtml(h)}</th>`).join("")
    const body = rows
      .map((r) => {
        return `<tr>
          <td>${escapeHtml(r?.["Criterion"] ?? "")}</td>
          <td>${escapeHtml(r?.["Met (y/n)"] ?? "")}</td>
          <td>${escapeHtml(r?.["Suggestions"] ?? "")}</td>
        </tr>`
      })
      .join("")
    return `${titleHtml}
      <table class="generaltable boxaligncenter">
        <thead><tr>${head}</tr></thead>
        <tbody>${body}</tbody>
      </table>`
  }

  const renderEvaluationText = (evaluationObj) => {
    const content = evaluationObj?.content ?? ""
    return `<div class="box py-2">
      <h4 class="mb-1">Evaluation</h4>
      <div>${escapeHtml(content).replace(/\n/g, "<br>")}</div>
    </div>`
  }

  const renderImprovedAssignment = (improvedObj) => {
    const content = improvedObj?.content ?? ""
    return `<div class="box py-2">
      <h4 class="mb-1">Improved Assignment</h4>
      <div>${escapeHtml(content).replace(/\n/g, "<br>")}</div>
    </div>`
  }

  const renderRecommendation = (recommendation, fromCache) => {
    const cacheBanner = fromCache ? '<div class="alert alert-info">Loaded from cache</div>' : ""

    // If we already have an object in the expected shape, render it:
    if (
      recommendation &&
      typeof recommendation === "object" &&
      (recommendation.table || recommendation.EvaluationText || recommendation.ImprovedAssignment)
    ) {
      let html = ""
      if (recommendation.table) html += renderTable(recommendation.table)
      if (recommendation.EvaluationText) html += renderEvaluationText(recommendation.EvaluationText)
      if (recommendation.ImprovedAssignment) html += renderImprovedAssignment(recommendation.ImprovedAssignment)
      return cacheBanner + (html || "<div class='box'>No content</div>")
    }

    // If it's a string, try to parse JSON. If parse fails, fallback to plaintext display.
    if (typeof recommendation === "string") {
      const parsed = tryParseJSON(recommendation)
      if (parsed) {
        return renderRecommendation(parsed, fromCache)
      }
      return cacheBanner + `<div class="box">${escapeHtml(recommendation).replace(/\n/g, "<br>")}</div>`
    }

    // Unknown shape fallback
    return cacheBanner + `<div class="box">No recommendation content available.</div>`
  }

  // Assume this is the existing AJAX response handling logic
  const handleAjaxResponse = (response) => {
    if (response.status === "success") {
      // Compute HTML for new JSON shape or fallback to plaintext
      const recommendationHtml = renderRecommendation(response.recommendation, response.from_cache)

      // Try a few common containers used in the UI; if you already have a specific container,
      // keep using it and just set its HTML to recommendationHtml.
      const $container = $("#ai-recommendation-output").length
        ? $("#ai-recommendation-output")
        : $("#trustgrade-recommendation-output").length
          ? $("#trustgrade-recommendation-output")
          : $(".trustgrade-recommendation").first()

      if ($container && $container.length) {
        $container.html(recommendationHtml)
      } else {
        // If the existing code uses a specific variable or selector, keep it and set its HTML instead:
        // existingContainer.html(recommendationHtml)
      }
    } else {
      // Handle other statuses here
    }
  }

  // Assume this is the existing code structure
  const trustgrade = {
    init: () => {
      // Initialization code here
    },
    checkInstructions: () => {
      $.ajax({
        url: "local_trustgrade_check_instructions",
        method: "GET",
        success: (response) => {
          handleAjaxResponse(response)
        },
        error: (xhr, status, error) => {
          // Error handling code here
        },
      })
    },
  }

  return trustgrade
})
