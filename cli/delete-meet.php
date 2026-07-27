<?php

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/mongodb.php';

function usage(int $exit_code = 0): void
{
  $stream = $exit_code === 0 ? STDOUT : STDERR;
  fwrite($stream, "Usage: php cli/delete-meet.php <meet-slug> [--yes]\n");
  fwrite($stream, "\n");
  fwrite($stream, "Deletes every document belonging to the meet from MongoDB and meta/meta.json,\n");
  fwrite($stream, "and moves all associated raw files into a timestamped trash directory.\n");
  fwrite($stream, "An individual document slug is also accepted for backward compatibility.\n");
  fwrite($stream, "Use --yes to skip the interactive confirmation.\n");
  exit($exit_code);
}

function fail(string $message): void
{
  fwrite(STDERR, "Error: $message\n");
  exit(1);
}

$args = array_slice($argv, 1);
if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
  usage();
}

$skip_confirmation = in_array('--yes', $args, true);
$positional = array_values(array_filter(
  $args,
  static fn(string $arg): bool => $arg !== '--yes'
));

if (count($positional) !== 1) {
  usage(1);
}

$requested_slug = $positional[0];
if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $requested_slug)) {
  fail('Invalid slug. Only lowercase letters, numbers, and hyphens are allowed.');
}

$meta_path = META_DIR . 'meta.json';
$all_file_metadata = [];
if (is_file($meta_path)) {
  $decoded = json_decode((string) file_get_contents($meta_path), true);
  if (!is_array($decoded)) {
    fail('meta/meta.json is not valid JSON; no changes were made.');
  }
  $all_file_metadata = $decoded;
}

$matching_file_metadata = array_values(array_filter(
  $all_file_metadata,
  static function (array $doc) use ($requested_slug): bool {
    $derived_meet_slug = slugify(
      ($doc['meet_name'] ?? '') . '-' . ($doc['meet_start_date'] ?? '')
    );

    return ($doc['meet_slug'] ?? null) === $requested_slug ||
      $derived_meet_slug === $requested_slug ||
      ($doc['slug'] ?? null) === $requested_slug;
  }
));

$mongo = null;
$matching_mongo_metadata = [];
$mongo_filter = [
  '$or' => [
    ['meet_slug' => $requested_slug],
    ['slug' => $requested_slug],
  ],
];
if (!empty($_ENV['MONGODB_URI'])) {
  try {
    $mongo = new MongoDBLibrary();
    $matching_mongo_metadata = iterator_to_array(
      $mongo->collection->find($mongo_filter),
      false
    );
  } catch (Throwable $e) {
    fail('Could not query MongoDB: ' . $e->getMessage() . '. No changes were made.');
  }
}

$document_slugs = [];
foreach (array_merge($matching_mongo_metadata, $matching_file_metadata) as $doc) {
  $document_slug = (string) ($doc['slug'] ?? '');
  if ($document_slug !== '') {
    $document_slugs[$document_slug] = $document_slug;
  }
}
if ($document_slugs === []) {
  $document_slugs[$requested_slug] = $requested_slug;
}
$document_slugs = array_values($document_slugs);

$raw_files = [];
$raw_directories = [
  RAW_DIR,
  RAW_DIR . 'events/',
  RAW_DIR . 'heat-sheets/',
  RAW_DIR . 'psych-sheets/',
  RAW_DIR . 'results/',
  RAW_DIR . 'standards/',
];

foreach ($document_slugs as $document_slug) {
  foreach ($raw_directories as $directory) {
    if (!is_dir($directory)) {
      continue;
    }

    foreach (glob($directory . $document_slug . '.*') ?: [] as $path) {
      if (is_file($path)) {
        $raw_files[$path] = $path;
      }
    }
  }
}
$raw_files = array_values($raw_files);

$mongo_count = count($matching_mongo_metadata);
$json_count = count($matching_file_metadata);
$file_count = count($raw_files);

if ($mongo_count === 0 && $json_count === 0 && $file_count === 0) {
  fwrite(STDOUT, "Nothing found for meet slug or document slug: $requested_slug\n");
  exit(2);
}

fwrite(STDOUT, "Found for '$requested_slug':\n");
fwrite(STDOUT, "  MongoDB documents: $mongo_count\n");
fwrite(STDOUT, "  meta.json records: $json_count\n");
fwrite(STDOUT, "  raw files: $file_count\n");
fwrite(STDOUT, "  document slugs:\n");
foreach ($document_slugs as $document_slug) {
  fwrite(STDOUT, "    - $document_slug\n");
}

if (!$skip_confirmation) {
  fwrite(STDOUT, "\nDelete this entire meet and move its files to trash? [y/N] ");
  $answer = strtolower(trim((string) fgets(STDIN)));
  if (!in_array($answer, ['y', 'yes'], true)) {
    fwrite(STDOUT, "Cancelled; no changes were made.\n");
    exit(0);
  }
}

$trash_root = dirname(__DIR__) . '/trash';
if (!is_dir($trash_root) && !mkdir($trash_root, 0755, true) && !is_dir($trash_root)) {
  fail("Could not create trash directory: $trash_root");
}

$trash_name = gmdate('Ymd-His') . '-' . $requested_slug . '-' . bin2hex(random_bytes(3));
$trash_dir = $trash_root . '/' . $trash_name;
if (!mkdir($trash_dir, 0755, true)) {
  fail("Could not create trash entry: $trash_dir");
}

$backup = [
  'requested_slug' => $requested_slug,
  'document_slugs' => $document_slugs,
  'deleted_at' => gmdate(DATE_ATOM),
  'mongodb_documents' => $matching_mongo_metadata,
  'meta_json_records' => $matching_file_metadata,
  'raw_files' => array_map(
    static fn(string $path): string => substr($path, strlen(dirname(__DIR__)) + 1),
    $raw_files
  ),
];

$backup_path = $trash_dir . '/metadata.json';
$backup_json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($backup_json === false || file_put_contents($backup_path, $backup_json) === false) {
  @rmdir($trash_dir);
  fail('Could not write the trash metadata backup; no records were deleted.');
}

if ($mongo !== null && $mongo_count > 0) {
  try {
    $result = $mongo->collection->deleteMany($mongo_filter);
    if ($result->getDeletedCount() !== $mongo_count) {
      fail(
        "MongoDB deleted {$result->getDeletedCount()} of $mongo_count expected documents. " .
        "A backup is at $backup_path; filesystem and meta.json changes were not attempted."
      );
    }
  } catch (Throwable $e) {
    fail("MongoDB deletion failed: {$e->getMessage()}. A backup is at $backup_path.");
  }
}

if ($json_count > 0) {
  $remaining_metadata = array_values(array_filter(
    $all_file_metadata,
    static function (array $doc) use ($requested_slug): bool {
      $derived_meet_slug = slugify(
        ($doc['meet_name'] ?? '') . '-' . ($doc['meet_start_date'] ?? '')
      );

      return ($doc['meet_slug'] ?? null) !== $requested_slug &&
        $derived_meet_slug !== $requested_slug &&
        ($doc['slug'] ?? null) !== $requested_slug;
    }
  ));
  $encoded = json_encode($remaining_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($encoded === false || file_put_contents($meta_path, $encoded) === false) {
    fail("Could not update meta/meta.json. Deleted MongoDB records are backed up at $backup_path.");
  }
}

$moved_count = 0;
foreach ($raw_files as $path) {
  $relative = substr($path, strlen(RAW_DIR));
  $target = $trash_dir . '/raw/' . $relative;
  $target_directory = dirname($target);

  if (!is_dir($target_directory) &&
      !mkdir($target_directory, 0755, true) &&
      !is_dir($target_directory)) {
    fwrite(STDERR, "Warning: could not create $target_directory; left $path in place.\n");
    continue;
  }

  if (!rename($path, $target)) {
    fwrite(STDERR, "Warning: could not move $path; it was left in place.\n");
    continue;
  }
  $moved_count++;
}

fwrite(STDOUT, "\nDeleted metadata and moved $moved_count of $file_count raw files.\n");
fwrite(STDOUT, "Recoverable backup: $trash_dir\n");

if ($moved_count !== $file_count) {
  exit(1);
}
