<?php $this->layout('layout', [
  'title' => $meet_name,
  'meta_title' => $meet_name . ' (' . $meet_start_date . ')',
  'meta_description' => 'Meet information and documents for ' . $meet_name,
  'meta_canonical_url' => $base_url . '/meet/' . $meet_slug
]) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-3">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($meet_name) ?></li>
  </ol>
</nav>

<h1 class="mb-1"><?= htmlspecialchars($meet_name) ?></h1>
<p class="text-muted mb-4">
  <?= date('F j, Y', strtotime($meet_start_date)) ?>
  <?php if (!empty($meet_end_date) && $meet_end_date !== $meet_start_date): ?>
    to <?= date('F j, Y', strtotime($meet_end_date)) ?>
  <?php endif; ?>
</p>

<?php
$grouped = ['events' => [], 'psych_sheets' => [], 'heat_sheets' => [], 'results' => []];
foreach ($meet_docs as $doc) {
  if (isset($grouped[$doc['type']])) {
    $grouped[$doc['type']][] = $doc;
  }
}

function renderSection1($label, $type, $docs, $base_url, $emptyText = 'Not available')
{
  echo "<h2 class=\"h5 mt-4\">$label</h2>";

  if (!empty($docs)) {
    echo '<div class="mb-3">';
    foreach ($docs as $doc) {
      $meetName = htmlspecialchars($doc['meet_name'] ?? '');
      $sheetName = trim($doc['sheet_name'] ?? '');
      $displayTitle = $sheetName ? "$meetName — $sheetName" : $meetName;

      $org = htmlspecialchars($doc['venue'] ?? $doc['organization'] ?? '');
      $time = $doc['file_datetime'] ?? $doc['updated_at'] ?? '';
      $timeFormatted = $time ? date('M j, Y', strtotime($time)) : '';

      $slugType = str_replace('_', '-', $type) . 's';
      $link = "{$base_url}/{$slugType}/{$doc['slug']}";

      echo "<a href=\"$link\" class=\" list-clickable text-decoration-none text-body d-flex align-items-center gap-2 py-2 border-bottom hover-row\">";
      echo '<i class="bi bi-link-45deg text-secondary"></i>'; // Link icon
      echo "<div>$displayTitle";
      if ($org || $timeFormatted) {
        echo " <span class=\"text-muted small\">• ";
        if ($org) echo "$org";
        if ($org && $timeFormatted) echo " — ";
        if ($timeFormatted) echo "Updated $timeFormatted";
        echo "</span>";
      }
      echo '</div>';
      echo "</a>";
    }
    echo '</div>';
  } else {
    $uploadPath = ($type === 'events') ? 'upload-file.php' : 'upload-data.php';
    echo "<div class=\"mb-3\">$emptyText. <a href=\"{$base_url}/$uploadPath\" class=\"btn btn-sm btn-outline-secondary ms-2\">Upload</a></div>";
  }
}
function renderSection($label, $type, $docs, $base_url, $emptyText = 'Not available')
{
  // Icon mapping
  $icons = [
    'events' => 'calendar3',
    'psych_sheet' => 'people-fill',
    'heat_sheet' => 'layout-three-columns',
    'result' => 'clipboard-check'
  ];

  $uploadPath = ($type === 'events') ? 'upload-file.php' : 'upload-data.php';
  $icon = $icons[$type] ?? 'file-earmark';

  // Header with icon and small upload link
  echo '<div class="d-flex justify-content-between align-items-center mt-4 mb-2">';
  echo "<h2 class=\"h5 mb-0\"><i class=\"bi bi-{$icon} me-2\"></i> $label</h2>";

  echo '</div>';

  if (!empty($docs)) {
    echo '<div class="mb-3">';
    foreach ($docs as $doc) {
      $meetName = htmlspecialchars($doc['meet_name'] ?? '');
      $sheetName = trim($doc['sheet_name'] ?? '');
      $displayTitle = $sheetName ? "$meetName — $sheetName" : $meetName;

      $org = htmlspecialchars($doc['venue'] ?? $doc['organization'] ?? '');
      $time = $doc['file_datetime'] ?? $doc['updated_at'] ?? '';
      $timeFormatted = $time ? date('M j, Y', strtotime($time)) : '';

      $slugType = str_replace('_', '-', $type);
      $link = "{$base_url}/{$slugType}/{$doc['slug']}";

      echo "<a href=\"$link\" class=\"list-clickable text-decoration-none text-body d-flex align-items-center gap-2 py-2 border-bottom hover-row\">";
      echo '<i class="bi bi-link-45deg text-secondary"></i>';
      echo "<div>$displayTitle";
      if ($org || $timeFormatted) {
        echo " <span class=\"text-muted small\">• ";
        if ($org) echo "$org";
        if ($org && $timeFormatted) echo " — ";
        if ($timeFormatted) echo "Updated $timeFormatted";
        echo "</span>";
      }
      echo '</div>';
      echo "</a>";
    }
    echo '</div>';
  } else {
    // Empty state with small upload link
    echo "<div class=\"mb-3\">$emptyText.";
    echo "<a href=\"{$base_url}/$uploadPath\" class=\"text-muted small text-decoration-none ms-2\">";
    echo "<i class=\"bi bi-upload me-1\"></i>Upload</a></div>";
  }
}


?>

<?php
renderSection('Event Schedule', 'events', $grouped['events'], $base_url);
renderSection('Psych Sheet', 'psych_sheets', $grouped['psych_sheets'], $base_url);
renderSection('Heat Sheets', 'heat_sheets', $grouped['heat_sheets'], $base_url);
renderSection('Results', 'results', $grouped['results'], $base_url);
?>