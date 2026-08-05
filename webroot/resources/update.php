<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/resource_links.php';
session_start();
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Invalid request.');
    $url = normalize_resource_url($_POST['url'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    if (!$url || !$title || mb_strlen($title) > 180 || mb_strlen($description) > 500 || !in_array($category, ['national', 'zone', 'lsc'], true)) throw new RuntimeException('Please provide a valid title, description, and category.');
    $regions = require __DIR__ . '/../../lib/swimming_regions.php';
    $zoneCode = $_POST['zone_code'] ?? ''; $lscCode = $_POST['lsc_code'] ?? '';
    if ($category === 'national') { $zoneCode = ''; $lscCode = ''; }
    if ($category === 'zone') $lscCode = '';
    if ($category === 'zone' && !isset($regions[$zoneCode])) throw new RuntimeException('Select a specific zone.');
    if ($category === 'lsc' && (!isset($regions[$zoneCode]) || !isset($regions[$zoneCode][1][$lscCode]))) throw new RuntimeException('Select a valid zone and LSC.');
    $changes = ['$set' => ['title' => $title, 'description' => $description, 'category' => $category, 'zone_code' => $zoneCode, 'zone_name' => $zoneCode ? $regions[$zoneCode][0] : '', 'lsc_code' => $lscCode, 'lsc_name' => $lscCode ? $regions[$zoneCode][1][$lscCode] : '', 'updated_at' => gmdate('c')]];
    if (!empty($_ENV['MONGODB_URI'])) {
        require_once __DIR__ . '/../../lib/mongodb.php';
        $mongo = new MongoDBLibrary('resources');
        if ($mongo->collection->updateOne(['url' => $url], $changes)->getMatchedCount() === 0) throw new RuntimeException('Resource not found.');
    } else {
        $meta = load_meta_json(); $found = false;
        foreach ($meta['resources'] ?? [] as &$item) if (($item['url'] ?? '') === $url) { $item = array_merge($item, $changes['$set']); $found = true; break; }
        unset($item);
        if (!$found) throw new RuntimeException('Resource not found.');
        save_meta_json($meta);
    }
    $_SESSION['resource_flash'] = ['success', 'Resource updated.'];
} catch (Throwable $e) { $_SESSION['resource_flash'] = ['danger', $e->getMessage()]; }
header('Location: ' . BASE_URL . '/resources/');
