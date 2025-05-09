<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/parser/events_parser.php';

$slug = basename($_GET['slug'] ?? '');
$raw_path = RAW_DIR . 'events/' . $slug . '.ev3';

if (!file_exists($raw_path)) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Event schedule file not found.']);
  exit;
}

// Load metadata
$meet_info = null;

if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $meet_info = $mongo->find_doc(['slug' => $slug, 'type' => 'events']);
} else {
  require_once __DIR__ . '/../../lib/utils.php';
  $all_meta = load_meta_json();
  foreach ($all_meta as $doc) {
    if (($doc['slug'] ?? '') === $slug && ($doc['type'] ?? '') === 'events') {
      $meet_info = $doc;
      break;
    }
  }
}

if (!$meet_info) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Event metadata not found.']);
  exit;
}

$event_sessions = parse_ev3_file($raw_path);

echo $templates->render('events-view', [
  'slug' => $slug,
  'meet_info' => $meet_info,
  'event_sessions' => $event_sessions
]);
