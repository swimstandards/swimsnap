<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

$meets = [];

// MongoDB first
if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $cursor = $mongo->get_all_docs('psych_sheets');

  foreach ($cursor as $doc) {
    $meets[] = [
      'slug' => $doc['slug'],
      'title' => $doc['meet_name'],
      'start_date' => $doc['meet_start_date'] ?? '',
      'end_date' => $doc['meet_end_date'] ?? '',
      'organization' => $doc['organization'] ?? '',
      'file_datetime' => $doc['file_datetime'] ?? '',
    ];
  }
} else {
  // Fallback to meta.json
  $meta_path = META_DIR . 'meta.json';
  if (file_exists($meta_path)) {
    $all_meta = json_decode(file_get_contents($meta_path), true) ?: [];

    foreach ($all_meta as $doc) {
      if (($doc['type'] ?? '') === 'psych_sheets') {
        $meets[] = [
          'slug' => $doc['slug'],
          'title' => $doc['meet_name'],
          'start_date' => $doc['meet_start_date'] ?? '',
          'end_date' => $doc['meet_end_date'] ?? '',
          'organization' => $doc['organization'] ?? '',
          'file_datetime' => $doc['file_datetime'] ?? '',
        ];
      }
    }
  }
}

// Sort by start_date descending
usort($meets, fn($a, $b) => ($b['start_date'] ?? '') <=> ($a['start_date'] ?? ''));

echo $templates->render('psych-sheets-index', ['meets' => $meets]);
