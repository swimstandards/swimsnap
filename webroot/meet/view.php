<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

$slug = basename($_GET['slug'] ?? '');
if (!$slug) {
  http_response_code(400);
  echo "Missing slug.";
  exit;
}

$meet_docs = [];
$meet_name = '';
$meet_start_date = '';

// 1. Try MongoDB if available
if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../lib/mongodb.php';
  $mongo = new MongoDBLibrary();
  $cursor = $mongo->collection->find(['meet_slug' => $slug]);
  $meet_docs = iterator_to_array($cursor, false);

  if (!empty($meet_docs)) {
    $meet_name = $meet_docs[0]['meet_name'] ?? '';
    $meet_start_date = $meet_docs[0]['meet_start_date'] ?? '';
    $meet_end_date = $meet_docs[0]['meet_end_date'] ?? '';
  }
}

// 2. Fallback to meta.json (use slugified name+date)
if (empty($meet_docs)) {
  $meta_path = META_DIR . 'meta.json';
  if (file_exists($meta_path)) {
    $all_meta = json_decode(file_get_contents($meta_path), true) ?: [];
    foreach ($all_meta as $doc) {
      $doc_slug = slugify(($doc['meet_name'] ?? '') . '-' . ($doc['meet_start_date'] ?? ''));
      if ($doc_slug === $slug) {
        $meet_docs[] = $doc;
      }
    }

    if (!empty($meet_docs)) {
      $meet_name = $meet_docs[0]['meet_name'] ?? '';
      $meet_start_date = $meet_docs[0]['meet_start_date'] ?? '';
      $meet_end_date = $meet_docs[0]['meet_end_date'] ?? '';
    }
  }
}

if (!$meet_name || !$meet_start_date) {
  http_response_code(404);
  echo $templates->render('error', ['message' => 'Meet not found.']);
  exit;
}

// pr_pre(($meet_docs));

// Render the template

echo $templates->render('meet-view', [
  'meet_name' => $meet_name,
  'meet_start_date' => $meet_start_date,
  'meet_end_date' => $meet_end_date,
  'meet_slug' => $slug,
  'meet_docs' => $meet_docs
]);
