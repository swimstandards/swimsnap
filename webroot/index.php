<?php

require_once __DIR__ . '/../lib/bootstrap.php';

$currentMeet = null;

if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../lib/mongodb.php';
  $mongo = new MongoDBLibrary();

  $todayStr = (new DateTime())->format('Y-m-d');

  $doc = $mongo->collection->findOne([
    'type' => 'psych_sheets',
    'meet_start_date' => ['$lte' => $todayStr],
    'meet_end_date' => ['$gte' => $todayStr]
  ], [
    'sort' => ['meet_start_date' => -1]
  ]);

  if ($doc) {
    $slug = $doc['meet_slug'] ?? slugify($doc['meet_name'] . '-' . $doc['meet_start_date']);
    $start = (new DateTime($doc['meet_start_date']))->format('n/j');
    $end = isset($doc['meet_end_date']) ? (new DateTime($doc['meet_end_date']))->format('n/j') : null;

    $currentMeet = [
      'name' => $doc['meet_name'],
      'start' => $start,
      'end' => $end,
      'url' => BASE_URL . '/meet/' . $slug
    ];
  }
}

$templates->addData([
  'meta_title' => 'SwimSnap – Community-Powered Swim Meet Info',
  'meta_description' => 'SwimSnap is a fast, community-powered swim meet platform. Browse standards, event orders, psych sheets, and results — mobile-friendly and free.',
  'meta_keywords' => 'swim meet results, time standards, psych sheets, heat sheets, event schedule, meet mobile alternative, meethub, swim community',
  'meta_og_image' => BASE_URL . '/images/og/landing-preview.png',
  'meta_canonical_url' => BASE_URL . "/",
  'currentMeet' => $currentMeet
]);

echo $templates->render('home');
