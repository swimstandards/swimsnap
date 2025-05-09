<?php $this->layout('layout', ['title' => 'Upload Meet Text']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Upload Meet Document</li>
  </ol>
</nav>

<h1>Upload Meet Document</h1>
<p class="text-muted">
  Paste the content from a meet document (Psych Sheet, Meet Program/Heat Sheet, or Results) below and submit. Only text extracted from PDF is supported at this time.
</p>

<?php if (!empty($message)): ?>
  <div class="alert <?= $status === 'success' ? 'alert-success' : ($status === 'skipped' ? 'alert-warning' : 'alert-danger') ?>">
    <?= $message ?>
  </div>
<?php endif; ?>

<form method="POST" action="<?= $base_url ?>/upload-data-handler.php" id="text-upload-form" class="mb-4">
  <div class="mb-3">
    <label for="meetContent" class="form-label">Paste PDF Content Here:</label>
    <textarea class="form-control" id="meetContent" name="meetContent" rows="12" required></textarea>
  </div>

  <?php if (!empty($recaptcha_site_key)): ?>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad" async defer></script>
    <button
      id="recaptcha-btn"
      class="btn btn-primary g-recaptcha"
      data-sitekey="<?= htmlspecialchars($recaptcha_site_key) ?>"
      data-callback="onSubmit"
      data-action="submit"
      disabled>
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      Loading reCAPTCHA...
    </button>

    <script>
      function onSubmit(token) {
        const form = document.getElementById('text-upload-form');
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'g-recaptcha-response';
        hidden.value = token;
        form.appendChild(hidden);
        form.submit();
      }

      function onRecaptchaLoad() {
        const btn = document.getElementById('recaptcha-btn');
        if (btn) {
          setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-upload me-1"></i> Upload';
          }, 1000); // 1000 milliseconds = 1 second
        }
      }
    </script>
  <?php else: ?>
    <button type="submit" class="btn btn-primary">Upload</button>
  <?php endif; ?>
</form>