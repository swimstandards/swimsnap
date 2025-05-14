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
?>

<p class="text-muted">
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