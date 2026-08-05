<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/resource_links.php';
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Invalid request.');
    if (!empty($_ENV['RECAPTCHA_SECRET'])) {
        $token = $_POST['g-recaptcha-response'] ?? '';
        if (!$token) throw new RuntimeException('reCAPTCHA verification failed. Please try again.');
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($_ENV['RECAPTCHA_SECRET']) . '&response=' . urlencode($token) . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'] ?? ''));
        $result = json_decode($verify ?: '', true);
        if (empty($result['success'])) throw new RuntimeException('reCAPTCHA verification failed. Please try again.');
    }
    $url = normalize_resource_url($_POST['url'] ?? '');
    $category = $_POST['category'] ?? '';
    if (!$url || !in_array($category, ['national', 'zone', 'lsc'], true)) throw new RuntimeException('Enter a valid URL and category.');
    $regions = require __DIR__ . '/../../lib/swimming_regions.php';
    $zoneCode = $_POST['zone_code'] ?? '';
    $lscCode = $_POST['lsc_code'] ?? '';
    if ($category === 'national') { $zoneCode = ''; $lscCode = ''; }
    if ($category === 'zone' && !isset($regions[$zoneCode])) throw new RuntimeException('Select a valid zone.');
    if ($category === 'lsc' && (!isset($regions[$zoneCode]) || !isset($regions[$zoneCode][1][$lscCode]))) throw new RuntimeException('Select a valid zone and LSC.');
    $preview = fetch_resource_preview($url); // Never trust client supplied preview fields.
    $title = trim($_POST['title'] ?? '') ?: $preview['title'];
    $description = trim($_POST['description'] ?? '');
    if ($title === '' || mb_strlen($title) > 180 || mb_strlen($description) > 500) {
        throw new RuntimeException('Title must be 1–180 characters and description no more than 500 characters.');
    }
    // array_merge intentionally lets the contributor's edits override fetched metadata.
    $resource = array_merge($preview, [
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'zone_code' => $zoneCode,
        'zone_name' => $zoneCode ? $regions[$zoneCode][0] : '',
        'lsc_code' => $lscCode,
        'lsc_name' => $lscCode ? $regions[$zoneCode][1][$lscCode] : '',
        'screenshot_url' => $preview['image_url'] ?: resource_screenshot_url($url),
        'created_at' => gmdate('c'),
    ]);

    if (!empty($_ENV['MONGODB_URI'])) {
        require_once __DIR__ . '/../../lib/mongodb.php';
        $mongo = new MongoDBLibrary('resources');
        if ($mongo->collection->findOne(['url' => $url])) throw new RuntimeException('That website has already been shared.');
        try { $mongo->collection->insertOne($resource); }
        catch (MongoDB\Driver\Exception\BulkWriteException $e) { throw new RuntimeException('That website has already been shared.'); }
    } else {
        $meta = load_meta_json();
        $items = $meta['resources'] ?? [];
        foreach ($items as $item) if (($item['url'] ?? '') === $url) throw new RuntimeException('That website has already been shared.');
        $items[] = $resource;
        $meta['resources'] = $items;
        save_meta_json($meta);
    }
    $_SESSION['resource_flash'] = ['success', 'Resource added.'];
} catch (Throwable $e) {
    $_SESSION['resource_flash'] = ['danger', $e->getMessage()];
}
header('Location: ' . BASE_URL . '/resources/');
