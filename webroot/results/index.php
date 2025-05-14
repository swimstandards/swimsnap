<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/mongodb.php'; // MongoDB wrapper

$meets = [];

if (!empty($_ENV['MONGODB_URI'])) {
  // MongoDB mode
  $mongo = new MongoDBLibrary();
  $cursor = $mongo->get_all_docs('results');

  foreach ($cursor as $doc) {
    $meets[] = [
      'slug' => $doc['slug'],
      'sheet_name' => $doc['sheet_name'] ?? '',
      'meet_name' => $doc['meet_name'] ?? '',
      'meet_start_date' => $doc['meet_start_date'] ?? '',
      'meet_end_date' => $doc['meet_end_date'] ?? '',
      'file_datetime' => $doc['file_datetime'] ?? '',
      'organization' => $doc['organization'] ?? '',
    ];
  }
} else {
  // File-based fallback: Read meta.json
  $meta_file = META_DIR . 'meta.json';
  if (file_exists($meta_file)) {
    $meta_all = json_decode(file_get_contents($meta_file), true);

    foreach ($meta_all as $doc) {
      if (($doc['type'] ?? '') !== 'results') continue;

      $meets[] = [
        'slug' => $doc['slug'],
        'sheet_name' => $doc['sheet_name'] ?? '',
        'meet_name' => $doc['meet_name'] ?? '',
        'meet_start_date' => $doc['meet_start_date'] ?? '',
        'meet_end_date' => $doc['meet_end_date'] ?? '',
        'file_datetime' => $doc['file_datetime'] ?? '',
        'organization' => $doc['organization'] ?? '',
      ];
    }
  }
}

// Sort by start_date descending
usort($meets, fn($a, $b) => ($b['meet_start_date'] ?? '') <=> ($a['meet_start_date'] ?? ''));

echo $templates->render('results-index', ['meets' => $meets]);
