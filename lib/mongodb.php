<?php

require_once __DIR__ . "/../vendor/autoload.php"; // Adjust path if needed

class MongoDBLibrary
{
    private $client;
    private $db;
    public $collection;

    public function ensure_unique_slug_type_index()
    {
        return $this->collection->createIndex(
            ['slug' => 1, 'type' => 1],
            ['unique' => true]
        );
    }

    public function ensure_text_index()
    {
        return $this->collection->createIndex(['meet_name' => 'text', 'venue' => 'text', 'organization' => 'text']);
    }

    public function __construct($collection_name = 'docs')
    {
        $mongo_uri = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
        $db_name = $_ENV['DBNAME'] ?? 'swimsnap';
        $this->client = new MongoDB\Client($mongo_uri);
        $this->db = $this->client->selectDatabase($db_name);
        $this->collection = $this->db->selectCollection($collection_name);

        // Ensure slug+type unique index
        $this->ensure_unique_slug_type_index();
        $this->ensure_text_index();
    }

    public function save_doc(array $doc)
    {
        $now = new MongoDB\BSON\UTCDateTime();

        $doc['added_at'] = $doc['added_at'] ?? $now;
        $doc['updated_at'] = $now;

        $parts = [$doc['meet_name'] ?? '', $doc['meet_start_date'] ?? ''];
        // We can only use meet_name and meet_start_date to group meets.
        // Event files include venue, but no organization.
        // PDF docs include organization, but no venue.
        // To ensure grouping works across all types, only name and date are safe.
        // if (!empty($doc['organization'])) {
        //     $parts[] = $doc['organization'];
        // } elseif (!empty($doc['venue'])) {
        //     $parts[] = $doc['venue'];
        // }
        $doc['meet_slug'] = slugify(implode('-', $parts));

        return $this->collection->insertOne($doc);
    }

    public function find_doc(array $filter)
    {
        return $this->collection->findOne($filter);
    }

    public function update_doc_if_newer(array $filter, array $new_doc)
    {
        $parts = [$new_doc['meet_name'] ?? '', $new_doc['meet_start_date'] ?? ''];
        // We can only use meet_name and meet_start_date to group meets.
        // Event files include venue, but no organization.
        // PDF docs include organization, but no venue.
        // To ensure grouping works across all types, only name and date are safe.

        // if (!empty($new_doc['organization'])) {
        //     $parts[] = $new_doc['organization'];
        // } elseif (!empty($doc['venue'])) {
        //     $parts[] = $new_doc['venue'];
        // }
        $new_doc['meet_slug'] = slugify(implode('-', $parts));

        $existing = $this->collection->findOne($filter);

        if (!$existing) {
            // If no existing, insert
            $new_doc['added_at'] = new MongoDB\BSON\UTCDateTime();
            $new_doc['updated_at'] = new MongoDB\BSON\UTCDateTime();

            return $this->collection->insertOne($new_doc);
        }

        // Compare file_datetime if both have it
        if (!empty($new_doc['file_datetime']) && !empty($existing['file_datetime'])) {
            $new_time = strtotime($new_doc['file_datetime']);
            $old_time = strtotime($existing['file_datetime']);

            if ($new_time <= $old_time) {
                // No update needed
                return null;
            }
        }

        $new_doc['updated_at'] = new MongoDB\BSON\UTCDateTime();
        return $this->collection->updateOne($filter, ['$set' => $new_doc], ['upsert' => true]);
    }

    public function get_recent_docs(string $type, int $limit = 3)
    {
        return $this->collection->find(
            ['type' => $type],
            [
                'sort' => ['updated_at' => -1],
                'limit' => $limit
            ]
        );
    }

    public function get_latest_docs(string $type, int $limit = 30)
    {
        return $this->collection->find(
            ['type' => $type],
            [
                'sort' => ['meet_start_date' => -1],
                'limit' => $limit
            ]
        );
    }

    public function get_docs_by_meet(string $meet_name, string $meet_start_date)
    {
        return $this->collection->find([
            'meet_name' => $meet_name,
            'meet_start_date' => $meet_start_date
        ]);
    }

    public function get_doc_by_id(string $id)
    {
        return $this->collection->findOne([
            '_id' => new MongoDB\BSON\ObjectId($id)
        ]);
    }

    public function delete_doc(array $filter)
    {
        return $this->collection->deleteOne($filter);
    }

    public function get_all_docs(string $type)
    {
        return $this->collection->find(
            ['type' => $type],
            [
                'sort' => ['meet_start_date' => -1]
            ]
        );
    }
}
