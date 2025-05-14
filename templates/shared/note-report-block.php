<div class="position-relative mb-3">
  <!-- Report Button -->
  <button class="btn btn-sm btn-warning position-absolute" style="top: -40px; right: 0;"
    type="button" data-bs-toggle="collapse" data-bs-target="#reportIssueBox"
    aria-expanded="false" aria-controls="reportIssueBox" title="Report a parsing issue">
    <i class="bi bi-bug"></i>
  </button>

  <!-- Collapsible Report Block -->
  <div class="collapse" id="reportIssueBox">
    <div class="alert alert-warning small d-flex flex-wrap justify-content-between align-items-start mb-0">
      <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
        <span>
          If something looks incorrect or missing, it may be due to formatting issues in the original PDF.
          Please <a href="https://community.swimstandards.com/category/13/swimsnap" target="_blank">report it here</a> with <a href="#" id="copyUrlInline" class="text-teal text-decoration-underline"
            data-bs-toggle="tooltip" data-bs-placement="top" title="Click to copy URL">
            the page URL
          </a>.
          <span id="copyUrlFeedback" class="text-success ms-2" style="display: none;">Copied!</span>
        </span>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const copyLink = document.getElementById('copyUrlInline');
    const feedback = document.getElementById('copyUrlFeedback');

    if (copyLink) {
      copyLink.addEventListener('click', () => {
        navigator.clipboard.writeText(window.location.href).then(() => {
          feedback.style.display = 'inline';

          setTimeout(() => feedback.style.display = 'none', 2000);
        });
      });
    }
  });
</script>