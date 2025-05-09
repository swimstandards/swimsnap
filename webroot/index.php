<?php

require_once __DIR__ . '/../lib/bootstrap.php';

$templates->addData([
  'meta_title' => 'SwimSnap – Community-Powered Swim Meet Info',
  'meta_description' => 'SwimSnap is a fast, community-powered swim meet platform. Browse standards, event orders, psych sheets, and results — mobile-friendly and free.',
  'meta_keywords' => 'swim meet results, time standards, psych sheets, heat sheets, event schedule, meet mobile alternative, meethub, swim community',
  'meta_og_image' => BASE_URL . '/images/og/landing-preview.png',
  'meta_canonical_url' => BASE_URL . "/"
]);

echo $templates->render('home');
