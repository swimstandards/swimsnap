<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/resource_links.php';
$regions = require __DIR__ . '/../../lib/swimming_regions.php';

$resources = [];
if (!empty($_ENV['MONGODB_URI'])) {
    require_once __DIR__ . '/../../lib/mongodb.php';
    $mongo = new MongoDBLibrary('resources');
    foreach ($mongo->collection->find([], ['sort' => ['created_at' => -1]]) as $doc) $resources[] = (array) $doc;
} else {
    $resources = load_meta_json()['resources'] ?? [];
    usort($resources, static fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));
}

echo $templates->render('resources-index', ['resources' => $resources, 'regions' => $regions]);
