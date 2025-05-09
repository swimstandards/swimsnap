<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/parser/results_parser.php';
require_once __DIR__ . '/../../lib/mongodb.php'; // Add MongoDB wrapper

$slug = basename($_GET['slug'] ?? '');
$type = 'results'; // since this is results-view

$raw_path = RAW_DIR . "results/{$slug}.txt"; // fixed path

if (!file_exists($raw_path)) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Results file not found']);
  exit;
}

$meta = null;

// If using MongoDB
if (!empty($_ENV['MONGODB_URI'])) {
  $mongo = new MongoDBLibrary();
  $meta = $mongo->find_doc([
    'slug' => $slug,
    'type' => $type
  ]);
  if ($meta) {
    $meta = (array) $meta; // force array for template
  }
} else {
  // Using meta.json
  $meta_path = META_DIR . "meta.json";

  if (file_exists($meta_path)) {
    $meta_list = json_decode(file_get_contents($meta_path), true);

    foreach ($meta_list as $item) {
      if (($item['slug'] ?? '') === $slug && ($item['type'] ?? '') === 'results') {
        $meta = $item;
        break;
      }
    }
  }
}

if (!$meta) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Results metadata not found']);
  exit;
}

// pr_pre($meta);

// Parse results content
$results = process_results(file_get_contents($raw_path));

// pr_pre($results);

// Render
echo $templates->render('results-view', [
  'slug' => $slug,
  'meet_info' => $meta,
  'results' => $results
]);
