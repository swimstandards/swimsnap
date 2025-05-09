<?php

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/mongodb.php';
require_once __DIR__ . '/../lib/utils.php';

session_start();

function set_status_and_redirect(string $status, string $message): void
{
  $_SESSION['upload_status'] = $status;
  $_SESSION['upload_message'] = $message;
  header("Location: upload-file.php");
  exit;
}

function cleanup_temp_upload(string $zip_path, string $tmp_dir): void
{
  if (file_exists($zip_path)) unlink($zip_path);
  foreach (glob("$tmp_dir/*") as $file) unlink($file);
  @rmdir($tmp_dir);
}

// reCAPTCHA
if (!empty($_ENV['RECAPTCHA_SECRET'])) {
  $token = $_POST['g-recaptcha-response'] ?? '';
  if (!$token) {
    set_status_and_redirect('danger', "❌ reCAPTCHA verification failed (no token).");
  }
  $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' .
    urlencode($_ENV['RECAPTCHA_SECRET']) . '&response=' . urlencode($token) .
    '&remoteip=' . $_SERVER['REMOTE_ADDR']);
  $result = json_decode($verify, true);

  if (empty($result['success'])) {
    set_status_and_redirect('danger', "❌ reCAPTCHA verification failed.");
  }
}

// Upload handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zip_file'])) {
  $file = $_FILES['zip_file'];

  // Check extension
  if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
    set_status_and_redirect('danger', "❌ Only ZIP files are allowed.");
  }

  // Check size (50KB max)
  if ($file['size'] > 50 * 1024) {
    set_status_and_redirect('danger', "❌ ZIP file too large. Must be under 50KB.");
  }

  $tmp_dir = UPLOAD_DIR . uniqid('unzipped_', true) . '/';
  mkdir($tmp_dir, 0755, true);

  $zip_path = UPLOAD_DIR . basename($file['name']);
  move_uploaded_file($file['tmp_name'], $zip_path);

  $zip = new ZipArchive();
  if ($zip->open($zip_path) === TRUE) {
    $zip->extractTo($tmp_dir);
    $zip->close();

    $hyv_path = null;
    $ev3_path = null;

    foreach (scandir($tmp_dir) as $entry) {
      $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
      if ($ext === 'hyv') $hyv_path = $tmp_dir . $entry;
      if ($ext === 'ev3') $ev3_path = $tmp_dir . $entry;
    }

    if (!$ev3_path) {
      cleanup_temp_upload($zip_path, $tmp_dir);
      set_status_and_redirect('danger', "❌ No .ev3 file found inside the ZIP.");
    }

    $first_line = fgets(fopen($hyv_path, 'r'));
    $parts = explode(';', $first_line);
    $meet_name = preg_replace('/\bQT\b/i', '', trim($parts[0] ?? 'Unknown Meet'));

    $start_date = isset($parts[1]) ? format_date_to_iso($parts[1]) : '';
    $end_date = isset($parts[2]) ? format_date_to_iso($parts[2]) : '';
    $venue = trim($parts[5] ?? '');

    $slug_parts = array_filter([$meet_name, $start_date, $venue]);
    $slug = slugify(implode('-', $slug_parts));

    $base_metadata = [
      'slug' => $slug,
      'meet_name' => $meet_name,
      'meet_start_date' => $start_date,
      'meet_end_date' => $end_date,
      'venue' => $venue,
    ];

    $already_exists = false;

    // Check duplication and save metadata
    if (!empty($_ENV['MONGODB_URI'])) {
      $mongo = new MongoDBLibrary();
      $existing = $mongo->find_doc(['slug' => $slug, 'type' => 'events']);
      if ($existing) {
        $already_exists = true;
      } else {
        $mongo->update_doc_if_newer(['slug' => $slug, 'type' => 'events'], $base_metadata + ['type' => 'events']);
      }
    } else {
      $all_meta = load_meta_json();
      foreach ($all_meta as $doc) {
        if ($doc['slug'] === $slug && $doc['type'] === 'events') {
          $already_exists = true;
          break;
        }
      }

      if (!$already_exists) {
        $all_meta[] = $base_metadata + ['type' => 'events'];
        save_meta_json($all_meta);
      }
    }

    // Save raw .hyv and .ev3
    $standards_dir = RAW_DIR . "standards/";
    $events_dir = RAW_DIR . "events/";
    if (!is_dir($standards_dir)) mkdir($standards_dir, 0755, true);
    if (!is_dir($events_dir)) mkdir($events_dir, 0755, true);

    rename($hyv_path, $standards_dir . "$slug.hyv");
    rename($ev3_path, $events_dir . "$slug.ev3");

    // Build message
    $link_event = BASE_URL . "/events/$slug";
    $message = $already_exists
      ? "⏩ Skipped (already uploaded): <a href=\"$link_event\" target=\"_blank\">View Event Schedule for $meet_name</a>"
      : "✅ Uploaded: <a href=\"$link_event\" target=\"_blank\">View Event Schedule for $meet_name</a>";

    cleanup_temp_upload($zip_path, $tmp_dir);
    set_status_and_redirect($already_exists ? 'warning' : 'success', $message);
  } else {
    set_status_and_redirect('danger', "❌ Failed to open zip file.");
  }
}
