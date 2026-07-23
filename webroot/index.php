<?php

require_once __DIR__ . '/../lib/bootstrap.php';

$active_meets = [];
$timezone = new DateTimeZone('America/New_York');
$today = new DateTimeImmutable('today', $timezone);
$today_str = $today->format('Y-m-d');
$upcoming_cutoff = $today->modify('+7 days')->format('Y-m-d');

if (!empty($_ENV['MONGODB_URI'])) {
  require_once __DIR__ . '/../lib/mongodb.php';
  $mongo = new MongoDBLibrary();

  $cursor = $mongo->collection->find([
    'meet_start_date' => ['$lte' => $upcoming_cutoff],
    'meet_end_date' => ['$gte' => $today_str]
  ]);
  $docs = iterator_to_array($cursor, false);
} else {
  $docs = load_meta_json();
}

foreach ($docs as $doc) {
  $start_date = $doc['meet_start_date'] ?? '';
  $end_date = $doc['meet_end_date'] ?? $start_date;
  if (!$start_date || !$end_date || $start_date > $upcoming_cutoff || $end_date < $today_str) {
    continue;
  }

  $meet_slug = $doc['meet_slug'] ?? slugify(($doc['meet_name'] ?? '') . '-' . $start_date);
  if (isset($active_meets[$meet_slug])) {
    continue;
  }

  $start = (new DateTimeImmutable($start_date, $timezone))->format('M j');
  $end = (new DateTimeImmutable($end_date, $timezone))->format('M j');
  $active_meets[$meet_slug] = [
    'name' => $doc['meet_name'] ?? 'Untitled Meet',
    'start_date' => $start_date,
    'is_in_progress' => $start_date <= $today_str,
    'dates' => $start === $end ? $start : "$start – $end",
    'url' => BASE_URL . '/meet/' . $meet_slug,
  ];
}

$active_meets = array_values($active_meets);
usort($active_meets, static function ($a, $b) {
  return [$b['is_in_progress'], $a['start_date']] <=> [$a['is_in_progress'], $b['start_date']];
});

$templates->addData([
  'meta_title' => 'SwimSnap – Community-Powered Swim Meet Info',
  'meta_description' => 'SwimSnap is a fast, community-powered swim meet platform. Browse standards, event orders, psych sheets, and results — mobile-friendly and free.',
  'meta_keywords' => 'swim meet results, time standards, psych sheets, heat sheets, event schedule, meet mobile alternative, meethub, swim community',
  'meta_og_image' => BASE_URL . '/images/og/landing-preview.png',
  'meta_canonical_url' => BASE_URL . "/",
  'active_meets' => $active_meets
]);

echo $templates->render('home');
