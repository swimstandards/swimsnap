<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/parser/psych_sheets_parser.php';

$slug = basename($_GET['slug'] ?? '');

$raw_path = RAW_DIR . "psych-sheets/{$slug}.txt";
$meta = null;

// Try MongoDB first
if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $meta = $mongo->find_doc(['slug' => $slug, 'type' => 'psych_sheets']);
} else {
  // Fallback to meta.json
  $meta_path = META_DIR . 'meta.json';
  if (file_exists($meta_path)) {
    $all_meta = json_decode(file_get_contents($meta_path), true) ?: [];

    foreach ($all_meta as $doc) {
      if (($doc['slug'] ?? '') === $slug && ($doc['type'] ?? '') === 'psych_sheets') {
        $meta = $doc;
        break;
      }
    }
  }
}

if (!$meta || !file_exists($raw_path)) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Psych sheet not found.']);
  exit;
}

// Now parse
$content = file_get_contents($raw_path);
$parsed_data = process_psych_sheet($content);

// Render
echo $templates->render('psych-sheets-view', [
  'slug' => $slug,
  'meet_info' => $meta,
  'parsed_events' => $parsed_data['events']
]);
