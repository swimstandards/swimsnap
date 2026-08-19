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
  <div class="alert <?= $status === 'success' ? 'alert-success' : (($status === 'skipped' || $status === 'missing_dates') ? 'alert-warning' : 'alert-danger') ?>">
    <?= $message ?>
  </div>
<?php endif; ?>

<label for="meetContent" class="form-label">Paste PDF Content Here:</label>

<form method="POST" action="<?= $base_url ?>/upload-data-handler.php" id="text-upload-form" class="mb-4">
  <div class="row">
    <div class="col-md-8 mb-3">

      <textarea class="form-control mb-3" id="meetContent" name="meetContent" rows="12" required><?= htmlspecialchars($draft['meetContent'] ?? '') ?></textarea>

      <div id="manual-meet-dates" class="border rounded p-3 mb-3"<?= $status === 'missing_dates' ? '' : ' hidden' ?>>
        <p class="mb-2">This document does not contain the usual HY-TEK meet-date line. Enter the official meet dates to continue.</p>
        <div class="row g-2">
          <div class="col-sm-6">
            <label for="meet_start_date" class="form-label">Meet start date</label>
            <input class="form-control" type="date" id="meet_start_date" name="meet_start_date" value="<?= htmlspecialchars($draft['meet_start_date'] ?? '') ?>">
          </div>
          <div class="col-sm-6">
            <label for="meet_end_date" class="form-label">Meet end date <span class="text-muted">(optional)</span></label>
            <input class="form-control" type="date" id="meet_end_date" name="meet_end_date" value="<?= htmlspecialchars($draft['meet_end_date'] ?? '') ?>">
          </div>
        </div>
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
              }, 1000);
            }
          }
        </script>
      <?php else: ?>
        <button type="submit" class="btn btn-primary">Upload</button>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
      <div class="alert alert-info">
        <strong>📌 Upload Instructions</strong><br><br>
        <ol class="mb-2 ps-3">
          <li>Download the PDF and open it directly in the Google Chrome browser—not in Google Drive’s built-in PDF viewer</li>
          <li>Press <kbd>Ctrl + A</kbd> (Windows) or <kbd>Command + A</kbd> (Mac)</li>
          <li>Copy and paste the content into the box</li>
        </ol>

        <strong class="d-block mb-1">Notes:</strong>
        <ul class="mb-0 ps-3">
          <li>Only PDFs exported from <strong>HY-TEK’s MEET MANAGER</strong> are supported</li>
          <li>Due to layout differences, not all PDF content formats are supported</li>
          <li>If parsing fails, copy the page URL and report it <a href="https://community.swimstandards.com/category/13/swimsnap" target="_blank">here</a></li>
        </ul>
      </div>
    </div>
  </div>


</form>
<?php $this->start('scripts') ?>
<script>
  const contentInput = document.getElementById('meetContent');
  const dateFields = document.getElementById('manual-meet-dates');

  function documentHasHytekMeetDates(content) {
    const lines = content.replace(/\r\n?/g, '\n').split('\n');
    const headerIndex = lines.findIndex(line => /HY-TEK.*MEET MANAGER/i.test(line));
    if (headerIndex < 0) return false;

    const date = '(?:\\d{1,2}\\/\\d{1,2}\\/\\d{4}|\\d{4}-\\d{2}-\\d{2})';
    const meetTitle = new RegExp(`^.+?\\s*-\\s*${date}(?:\\s+to\\s+${date})?\\s*\\\\?\\s*$`, 'i');

    for (let index = headerIndex + 1; index < lines.length; index++) {
      const line = lines[index].trim();
      if (/^(?:Psych Sheet|Meet Program|Results)\b/i.test(line)) break;
      if (meetTitle.test(line)) return true;
    }

    return false;
  }

  function updateMeetDatePrompt() {
    dateFields.hidden = !contentInput.value.trim() || documentHasHytekMeetDates(contentInput.value);
  }

  contentInput.addEventListener('input', updateMeetDatePrompt);
  updateMeetDatePrompt();
</script>
<?php $this->stop() ?>
