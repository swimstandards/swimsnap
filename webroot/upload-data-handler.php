<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/parser/parser.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $content = $_POST['meetContent'] ?? '';
  $manual_start_date = $_POST['meet_start_date'] ?? '';
  $manual_end_date = $_POST['meet_end_date'] ?? '';
  $result = handle_text_upload($content, $manual_start_date, $manual_end_date);

  $_SESSION['upload_status'] = $result['status'] ?? 'error';
  $_SESSION['upload_message'] = $result['message'] ?? 'Unknown error.';
  if (($_SESSION['upload_status'] ?? '') === 'missing_dates') {
    $_SESSION['upload_draft'] = [
      'meetContent' => $content,
      'meet_start_date' => $manual_start_date,
      'meet_end_date' => $manual_end_date,
    ];
  } else {
    unset($_SESSION['upload_draft']);
  }

  header("Location: upload-data.php");
  exit;
}
