<?php
require_once __DIR__ . '/../lib/bootstrap.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

if (!empty($_ENV['MONGODB_URI'])) {
    require_once __DIR__ . '/../lib/mongodb.php';
    $mongo = new MongoDBLibrary();

    $cursor = $mongo->collection->aggregate([
        ['$match' => ['$text' => ['$search' => $query]]],
        ['$group' => [
            '_id' => '$meet_slug',
            'meet_name' => ['$first' => '$meet_name'],
            'meet_start_date' => ['$first' => '$meet_start_date'],
            'types' => ['$addToSet' => '$type'],
            'updated_at' => ['$max' => '$updated_at'],
            'score' => ['$max' => ['$meta' => 'textScore']]
        ]],
        ['$sort' => ['score' => -1, 'meet_start_date' => -1]]
    ]);

    foreach ($cursor as $doc) {
        $results[] = [
            'meet_name' => $doc['meet_name'],
            'meet_start_date' => $doc['meet_start_date'],
            'slug' => BASE_URL . "/meet/" . $doc['_id'],
            'types' => $doc['types']
        ];
    }
} else {
    $all_meta = load_meta_json();
    $seen = [];

    foreach ($all_meta as $doc) {
        if (stripos($doc['meet_name'], $query) === false) continue;

        $slug = slugify(($doc['meet_name'] ?? '') . '-' . ($doc['meet_start_date'] ?? ''));
        $key = $doc['meet_name'] . '|' . $doc['meet_start_date'];

        if (!isset($seen[$key])) {
            $seen[$key] = [
                'meet_name' => $doc['meet_name'],
                'meet_start_date' => $doc['meet_start_date'],
                'slug' => BASE_URL . "/meet/" . $slug,
                'types' => [$doc['type']]
            ];
        } else {
            $seen[$key]['types'][] = $doc['type'];
        }
    }

    $results = array_values($seen);
}

echo json_encode($results);
