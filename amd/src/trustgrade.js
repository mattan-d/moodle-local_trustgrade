/**
 * AMD module for trustgrade functionality.
 *
 * @module mod_trustgrade/trustgrade
 */

// Helper function to get the intro draft item ID
function getIntroDraftItemId() {
  // Common Moodle editor hidden input pattern: intro[itemid] or similar
  const candidates = [
    'input[name="intro[itemid]"]',
    'input[name="intro_editor[itemid]"]',
    'input[name="introeditor[itemid]"]',
    'input[data-field="intro"][name$="[itemid]"]',
  ]
  for (const sel of candidates) {
    const el = document.querySelector(sel)
    if (el && el.value) {
      const v = Number.parseInt(el.value, 10)
      if (!isNaN(v)) return v
    }
  }
  // Some forms expose a direct filemanager itemid for the intro editor:
  const fm = document.querySelector('input[name="intro"]')
  const v = fm ? Number.parseInt(fm.value, 10) : 0
  return isNaN(v) ? 0 : v
}

// Helper function to get the intro attachments draft item ID
function getIntroAttachmentsDraftItemId() {
  // Moodle filemanager typically has a hidden input with the element name (e.g., introattachments)
  const candidates = [
    'input[name="introattachments"]',
    'input[name="intro_attachments"]',
    'input[data-field="introattachments"]',
    'input[name="introattachments[itemid]"]',
  ]
  for (const sel of candidates) {
    const el = document.querySelector(sel)
    if (el && el.value) {
      const v = Number.parseInt(el.value, 10)
      if (!isNaN(v)) return v
    }
  }
  return 0
}

// Function to generate questions
function generateQuestions(cmid, instructions) {
  const data = {
    cmid,
    instructions,
    intro_itemid: getIntroDraftItemId(),
    intro_attachments_itemid: getIntroAttachmentsDraftItemId(),
  }

  // AJAX call to generate questions
  const request = new XMLHttpRequest()
  request.open("POST", M.cfg.wwwroot + "/mod/trustgrade/ajax/generate_questions.php", true)
  request.setRequestHeader("Content-Type", "application/json;charset=UTF-8")
  request.onreadystatechange = () => {
    if (request.readyState === XMLHttpRequest.DONE) {
      if (request.status === 200) {
        const response = JSON.parse(request.responseText)
        console.log("Questions generated:", response)
      } else {
        console.error("Failed to generate questions:", request.status)
      }
    }
  }
  request.send(JSON.stringify(data))
}

// Export the function for use in other modules
export { generateQuestions }
