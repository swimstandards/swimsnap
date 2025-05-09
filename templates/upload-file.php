<?php $this->layout('layout', ['title' => 'Upload Time Standards']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Upload Hytek Event Setup</li>
  </ol>
</nav>

<h1>Upload Hytek Event Setup File (.zip)</h1>


<?php if (!empty($message)): ?>
  <div class="alert <?= $status === 'success' ? 'alert-success' : ($status === 'skipped' ? 'alert-warning' : 'alert-danger') ?>">
    <?= $message ?>
  </div>
<?php endif; ?>


<form method="POST" action="upload-file-handler.php" id="upload-file-form" enctype="multipart/form-data" class="mb-4">
  <div class="mb-3">
    <label for="zip_file" class="form-label">Upload a ZIP with .ev3 and .hyv files:</label>
    <input type="file" name="zip_file" id="zip_file" class="form-control" required accept=".zip">
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
        document.getElementById('upload-file-form').submit();
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