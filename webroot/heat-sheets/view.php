<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/parser/heat_sheets_parser.php';
require_once __DIR__ . '/../../lib/utils.php';
require_once __DIR__ . '/../../lib/mongodb.php'; // MongoDBLibrary

$slug = basename($_GET['slug'] ?? '');

$meet_info = null;
$raw_path = RAW_DIR . "heat-sheets/{$slug}.txt";

// First: Try to load metadata from MongoDB
if (!empty($_ENV['MONGODB_URI'])) {
  $mongo = new MongoDBLibrary();
  $meet_info = $mongo->collection->findOne(['slug' => $slug, 'type' => 'heat_sheets']);
} else {
  // Fallback: Load from meta.json
  $all_meta = load_meta_json();
  foreach ($all_meta as $doc) {
    if (($doc['slug'] ?? '') === $slug && ($doc['type'] ?? '') === 'heat_sheets') {
      $meet_info = $doc;
      break;
    }
  }
}

if (!$meet_info || !file_exists($raw_path)) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Meet program/Heat Sheet not found']);
  exit;
}

$parsed_data = process_heat_sheet(file_get_contents($raw_path));

// Render page
echo $templates->render('heat-sheets-view', [
  'slug' => $slug,
  'meet_info' => is_array($meet_info) ? $meet_info : iterator_to_array($meet_info),
  'parsed_data' => $parsed_data
]);
