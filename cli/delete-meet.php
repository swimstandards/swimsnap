<?php

require_once __DIR__ . '/../lib/bootstrap.php';

$slug = $argv[1] ?? '';
if (!$slug) {
  echo "Usage: php deleteMeet.php [slug]\n";
  exit(1);
}

$paths = [
  'meta' => META_DIR . $slug . '.json',
  'hyv' => RAW_DIR . $slug . '.hyv',
  'ev3' => RAW_DIR . $slug . '.ev3',
  'txt' => RAW_DIR . $slug . '.txt'
];

$trash_dir = __DIR__ . '/../trash/';
if (!is_dir($trash_dir)) {
  mkdir($trash_dir);
}

$deleted = [];

foreach ($paths as $type => $path) {
  if (file_exists($path)) {
    $target = $trash_dir . basename($path);
    rename($path, $target);
    $deleted[] = $type;
  }
}

if ($deleted) {
  echo "✅ Deleted (" . implode(', ', $deleted) . ") for meet: $slug\n";
} else {
  echo "❌ No files found for slug: $slug\n";
}
