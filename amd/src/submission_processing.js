define(["jquery", "core/ajax", "core/notification"], ($, Ajax, Notification) => {
  var M = window.M

  var SubmissionProcessing = {
    redirectToQuiz: function () {
      var quizUrl = M.cfg.wwwroot + "/local/trustgrade/quiz_interface.php?cmid=" + this.cmid
      window.location.href = quizUrl
    },
  }

  return SubmissionProcessing
})
