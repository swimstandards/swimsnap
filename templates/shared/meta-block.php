<?php
$meet_name = htmlspecialchars($meet_info['meet_name'] ?? '');
$meet_name_original = $meet_info['meet_name'];
$meet_start = $meet_info['meet_start_date'] ?? '';
$meet_end = $meet_info['meet_end_date'] ?? '';
$meet_date = $meet_info['meet_date'] ?? '';
$file_updated = $meet_info['file_datetime'] ?? null;
$slug = slugify("$meet_name_original $meet_start");
$meet_link = "$base_url/meet/$slug";
$org = $meet_info['organization'] ?? null;
$venue = $meet_info['venue'] ?? null;
$urlToShare = $base_url . $_SERVER['REQUEST_URI'];
?>

<div class="row g-4 align-items-center mb-4">
  <div class="col-md-8">
    <p class="text-muted mb-0">
      <strong>Meet:</strong> <a href="<?= $meet_link ?>"><?= $meet_name ?></a><br>
      <strong>Date:</strong>
      <?php if (!empty($meet_start) && !empty($meet_end)): ?>
        <?= date('m/d/Y', strtotime($meet_start)) ?> to <?= date('m/d/Y', strtotime($meet_end)) ?><br>
      <?php else: ?>
        <?= htmlspecialchars($meet_date) ?><br>
      <?php endif; ?>

      <?php if (!empty($venue)): ?>
        <strong>Venue:</strong> <?= htmlspecialchars($venue) ?><br>
      <?php elseif (!empty($org)): ?>
        <strong>Host:</strong> <?= htmlspecialchars($org) ?><br>
      <?php endif; ?>

      <?php if (!empty($file_updated)): ?>
        <strong>Document Updated:</strong> <?= htmlspecialchars($file_updated) ?><br>
      <?php endif; ?>
    </p>
  </div>

  <div class="col-md-4 text-md-end text-start">
    <div class="d-inline-flex flex-wrap gap-2 align-items-center">
      <div id="qrcode"></div>
      <button class="btn btn-sm btn-outline-primary" id="copyLinkBtn">
        <i class="bi bi-clipboard"></i> Copy
      </button>
      <a
        href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($urlToShare) ?>"
        target="_blank"
        class="btn btn-sm btn-outline-secondary"
        title="Share on Facebook">
        <i class="bi bi-facebook"></i>
      </a>
      <a
        href="https://twitter.com/intent/tweet?url=<?= urlencode($urlToShare) ?>"
        target="_blank"
        class="btn btn-sm btn-outline-secondary"
        title="Share on X">
        <i class="bi bi-twitter-x"></i>
      </a>
    </div>
  </div>
</div>