<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/resource_links.php';
$regions = require __DIR__ . '/../../lib/swimming_regions.php';

$url = normalize_resource_url($_GET['url'] ?? '');
if (!$url) { http_response_code(404); exit('Resource not found.'); }
$resource = null;
if (!empty($_ENV['MONGODB_URI'])) {
    require_once __DIR__ . '/../../lib/mongodb.php';
    $mongo = new MongoDBLibrary('resources');
    $doc = $mongo->collection->findOne(['url' => $url]);
    $resource = $doc ? (array) $doc : null;
} else {
    foreach (load_meta_json()['resources'] ?? [] as $item) if (($item['url'] ?? '') === $url) { $resource = $item; break; }
}
if (!$resource) { http_response_code(404); exit('Resource not found.'); }
echo $templates->render('resources-edit', ['resource' => $resource, 'regions' => $regions]);
