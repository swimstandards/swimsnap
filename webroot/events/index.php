<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

$meets = [];

if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $docs = $mongo->get_all_docs('events');

  foreach ($docs as $doc) {
    $meets[] = [
      'slug' => $doc['slug'],
      'title' => $doc['meet_name'] ?? '',
      'start_date' => $doc['meet_start_date'] ?? '',
      'end_date' => $doc['meet_end_date'] ?? '',
      'course' => $doc['course'] ?? '',
      'venue' => $doc['venue'] ?? '',
    ];
  }
} else {

  // Fallback to meta.json
  $meta_path = META_DIR . 'meta.json';
  if (file_exists($meta_path)) {
    $all_meta = json_decode(file_get_contents($meta_path), true) ?: [];


    foreach ($all_meta as $doc) {
      if (($doc['type'] ?? '') !== 'events') continue;

      $meets[] = [
        'slug' => $doc['slug'],
        'title' => $doc['meet_name'] ?? '',
        'start_date' => $doc['meet_start_date'] ?? '',
        'end_date' => $doc['meet_end_date'] ?? '',
        'course' => $doc['course'] ?? '',
        'venue' => $doc['venue'] ?? '',
      ];
    }
  }
}

// Sort by start_date descending
usort($meets, fn($a, $b) => ($b['start_date'] ?? '') <=> ($a['start_date'] ?? ''));

echo $templates->render('events-index', ['meets' => $meets]);
