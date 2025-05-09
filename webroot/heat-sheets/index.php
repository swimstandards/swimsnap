<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

$meets = [];

// Check MongoDB first
if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $cursor = $mongo->get_all_docs('heat_sheets');

  foreach ($cursor as $doc) {
    $meets[] = [
      'slug' => $doc['slug'],
      'sheet_name' => $doc['sheet_name'] ?? '',
      'meet_name' => $doc['meet_name'] ?? '',
      'meet_start_date' => $doc['meet_start_date'] ?? '',
      'meet_end_date' => $doc['meet_end_date'] ?? '',
      'organization' => $doc['organization'] ?? '',
      'file_datetime' => $doc['file_datetime'] ?? ''
    ];
  }
} else {
  // Fallback to meta.json
  $meta_path = META_DIR . 'meta.json';
  if (file_exists($meta_path)) {
    $all_meta = json_decode(file_get_contents($meta_path), true) ?: [];

    foreach ($all_meta as $doc) {
      if (($doc['type'] ?? '') === 'heat_sheets') {
        $meets[] = [
          'slug' => $doc['slug'],
          'sheet_name' => $doc['sheet_name'] ?? '',
          'meet_name' => $doc['meet_name'] ?? '',
          'meet_start_date' => $doc['meet_start_date'] ?? '',
          'meet_end_date' => $doc['meet_end_date'] ?? '',
          'organization' => $doc['organization'] ?? '',
          'file_datetime' => $doc['file_datetime'] ?? ''
        ];
      }
    }
  }
}

echo $templates->render('heat-sheets-index', ['meets' => $meets]);
