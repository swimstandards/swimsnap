<div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-start">
  <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
    <span>
      If something looks incorrect or missing, it may be due to formatting issues in the original PDF.
      Please <a href="https://community.swimstandards.com/category/13/swimsnap" target="_blank">report it here</a> with the page URL.
      <span class="text-decoration-underline small" role="button" id="copyUrlInline" tabindex="0">
        Click to Copy URL
      </span>
      <span id="copyUrlFeedback" class="text-success small ms-2" style="display: none;">Copied!</span>
    </span>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const inline = document.getElementById('copyUrlInline');
    const feedback = document.getElementById('copyUrlFeedback');

    if (inline) {
      inline.addEventListener('click', () => {
        navigator.clipboard.writeText(window.location.href).then(() => {
          feedback.style.display = 'inline';
          setTimeout(() => feedback.style.display = 'none', 2000);
        });
      });
    }
  });
</script>