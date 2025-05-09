<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/parser/parser.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $content = $_POST['meetContent'] ?? '';
  $result = handle_text_upload($content);

  $_SESSION['upload_status'] = $result['status'] ?? 'error';
  $_SESSION['upload_message'] = $result['message'] ?? 'Unknown error.';

  header("Location: upload-data.php");
  exit;
}
