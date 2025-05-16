<?php
require_once __DIR__ . '/../lib/bootstrap.php';

session_start();

$status = $_SESSION['upload_status'] ?? '';
$message = $_SESSION['upload_message'] ?? '';

unset($_SESSION['upload_status'], $_SESSION['upload_message']);

$recaptcha_site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';

$templates->addData([
  'meta_title' => 'Upload Meet Data – SwimSnap',
  'meta_description' => 'Paste and upload swim meet data such as psych sheets, heat sheets, or results. Help keep SwimSnap accurate and up to date for the swimming community.',
  'meta_keywords' => 'upload swim meet data, paste psych sheet, meet results contribution, swim community uploads',
  'meta_canonical_url' => BASE_URL . '/upload-data'
]);

echo $templates->render('upload-data', [
  'status' => $status,
  'message' => $message,
  'recaptcha_site_key' => $recaptcha_site_key
]);
