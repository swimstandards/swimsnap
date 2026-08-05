<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/resource_links.php';

// This endpoint is consumed by fetch(), so PHP notices must never prepend HTML
// to its JSON response in local/development environments.
ini_set('display_errors', '0');
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
header('Content-Type: application/json');
try {
    $url = normalize_resource_url($_GET['url'] ?? '');
    if (!$url) throw new RuntimeException('Enter a valid website URL.');
    $preview = fetch_resource_preview($url);
    $preview['screenshot_url'] = $preview['image_url'] ?: resource_screenshot_url($url);
    echo json_encode(['ok' => true, 'preview' => $preview]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
