<?php
require_once __DIR__ . '/../lib/bootstrap.php';

session_start();

$status = $_SESSION['upload_status'] ?? '';
$message = $_SESSION['upload_message'] ?? '';

unset($_SESSION['upload_status'], $_SESSION['upload_message']);

$recaptcha_site_key = $_ENV['RECAPTCHA_SITE_KEY'] ?? '';

echo $templates->render('upload-data', [
  'status' => $status,
  'message' => $message,
  'recaptcha_site_key' => $recaptcha_site_key
]);
