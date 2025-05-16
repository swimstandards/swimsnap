<?php

require_once __DIR__ . '/../lib/bootstrap.php';

session_start();

$status = $_SESSION['upload_status'] ?? '';
$message = $_SESSION['upload_message'] ?? '';

unset($_SESSION['upload_status'], $_SESSION['upload_message']);

$recaptcha_site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';

$templates->addData([
  'meta_title' => 'Upload Swim Meet File – SwimSnap',
  'meta_description' => 'Contribute to SwimSnap by uploading swim meet documents like psych sheets, event orders, and results. Help keep the swimming community up to date.',
  'meta_keywords' => 'upload swim meet files, contribute psych sheets, add heat sheets, meet results submission',
  'meta_canonical_url' => BASE_URL . '/upload'
]);

echo $templates->render('upload-file', [
  'status' => $status,
  'message' => $message,
  'recaptcha_site_key' => $recaptcha_site_key
]);
