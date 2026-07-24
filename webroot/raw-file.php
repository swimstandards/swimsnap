<?php

require_once __DIR__ . '/../lib/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? null;
$allowed_folders = [
  'psych-sheets',
  'heat-sheets',
  'results',
];

if (!is_string($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
  http_response_code(404);
  exit('Raw file not found.');
}

if ($type !== null && (!is_string($type) || !in_array($type, $allowed_folders, true))) {
  http_response_code(404);
  exit('Raw file not found.');
}

// Current links identify the document type so meets that share a slug across
// multiple document categories resolve to the file from the current page.
// Keep the full list as a fallback for older bookmarked links without `type`.
$folders = $type === null ? $allowed_folders : [$type];

$raw_path = null;
foreach ($folders as $folder) {
  $candidate = RAW_DIR . $folder . '/' . $slug . '.txt';
  if (is_file($candidate)) {
    $raw_path = $candidate;
    break;
  }
}

// Older public URLs used a date in the slug while the stored raw file used a
// short hash. Resolve that legacy form only when it identifies one file.
if ($raw_path === null && preg_match('/^(.+)-\d{4}-\d{2}-\d{2}$/', $slug, $matches)) {
  $legacy_stem = $matches[1];
  foreach ($folders as $folder) {
    $legacy_files = glob(RAW_DIR . $folder . '/' . $legacy_stem . '-*.txt');
    if (count($legacy_files) === 1 && is_file($legacy_files[0])) {
      $raw_path = $legacy_files[0];
      break;
    }
  }
}

if ($raw_path === null) {
  http_response_code(404);
  exit('Raw file not found.');
}

header('Content-Type: text/plain; charset=UTF-8');
header('Content-Disposition: inline; filename="' . $slug . '.txt"');
header('X-Content-Type-Options: nosniff');

readfile($raw_path);
