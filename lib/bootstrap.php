<?php

// lib/bootstrap.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', ['.env', '.env.local'], false);
$dotenv->load();

define('BASE_URL', rtrim($_ENV['BASE_URL'] ?? '', '/'));
define('RAW_DIR', __DIR__ . '/../raw/');
define('META_DIR', __DIR__ . '/../meta/');
define('UPLOAD_DIR', __DIR__ . '/../upload/');


define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

if ($_ENV['APP_ENV'] === 'PRODUCTION') {
  error_reporting(0);
  ini_set('display_errors', '0');
} else {
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
}


$templates = new League\Plates\Engine(__DIR__ . '/../templates');

// This adds global data for all views:
$templates->addData([
  'base_url' => BASE_URL,
  'recaptcha_site_key' => RECAPTCHA_SITE_KEY,
  'build_version' => get_build_version(),
]);
