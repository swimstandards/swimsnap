<?php

/**
 * parser.php
 *
 * Entry point for processing uploaded meet documents (pasted text).
 * Automatically detects the document type — such as psych sheet,
 * meet program, and results — based on content patterns and headers,
 * and saves the content to the appropriate folder under /raw.
 *
 * This script is used by the upload form to parse and store raw data.
 */


require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../mongodb.php'; // MongoDB wrapper


function handle_text_upload(string $content): array
{
  $content = trim($content);

  if (empty($content)) {
    return [
      'status' => 'error',
      'message' => '❌ Error: No content provided.'
    ];
  }

  $lines = explode("\n", $content);
  $metadata = [
    "organization" => "",
    "meet_name" => "",
    "meet_start_date" => "",
    "meet_end_date" => "",
    "file_datetime" => "",
    "type" => "",
    "sheet_name" => ""
  ];

  // Type to folder mapping (for raw and URL)
  $type_to_folder = [
    'heat_sheets' => 'heat-sheets',
    'psych_sheets' => 'psych-sheets',
    'results'       => 'results',
    'standards'     => 'standards', // handled by upload-file-handler.php
    'events'        => 'events' // handled by upload-file-handler.php
  ];

  foreach ($lines as $index => $line) {
    $line = trim($line);

    if ($index === 0) {
      if (preg_match('/^(.*?)\s*-?\s*HY-TEK/i', $line, $matches)) {
        $org = trim($matches[1]);
        $org_parts = preg_split('/\s+/', $org);
        $metadata["organization"] = $org;
        $org_slug = $org_parts[0] ?? $org; // use first letter for slug
      } else {
        return [
          'status' => 'error',
          'message' => '❌ Error: Unable to detect organization or HY-TEK header. Unsupported file format.'
        ];
      }

      if (preg_match('/(\d{1,2}:\d{2} (AM|PM) \d{1,2}\/\d{1,2}\/\d{4})/', $line, $matches)) {
        $metadata["file_datetime"] = trim($matches[1]);
      }
    }

    if ($index === 1) {
      if (preg_match('/^(.+?)\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4}) to (\d{1,2}\/\d{1,2}\/\d{4})$/', $line, $matches)) {
        $metadata["meet_name"] = trim($matches[1]);
        $metadata["meet_start_date"] = format_date_to_iso($matches[2]);
        $metadata["meet_end_date"] = format_date_to_iso($matches[3]);
      } elseif (preg_match('/^(.+?)\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})$/', $line, $matches)) {
        $metadata["meet_name"] = trim($matches[1]);
        $metadata["meet_start_date"] = format_date_to_iso($matches[2]);
        $metadata["meet_end_date"] = format_date_to_iso($matches[2]);
      }
    }

    if (stripos($line, "Psych Sheet") !== false) {
      $metadata["type"] = "psych_sheets";
      $metadata["sheet_name"] = "";
      break;
    } elseif (stripos($line, "Meet Program") !== false) {
      $metadata["type"] = "heat_sheets";
      $metadata["sheet_name"] = trim(preg_replace('/^Meet Program\s*-\s*/i', '', $line));
      break;
    } elseif (stripos($line, "Results") !== false) {
      $metadata["type"] = "results";
      if (preg_match('/^Results\s*-\s*(.+)$/i', $line, $matches)) {
        $metadata["sheet_name"] = trim($matches[1]);
      } elseif (strcasecmp(trim($line), "Results") === 0) {
        $metadata["sheet_name"] = '';
      } else {
        $metadata["sheet_name"] = trim($line);
      }
      break;
    }
  }

  if (empty($metadata["meet_name"]) || empty($metadata["type"])) {
    return [
      'status' => 'error',
      'message' => '❌ Error: Could not identify meet name or type.'
    ];
  }

  // Generate slug
  $parts = [];
  if (!empty($metadata['sheet_name'])) {
    $parts[] = $metadata['sheet_name'];
  }
  $parts[] = $metadata['meet_name'];
  $parts[] = $metadata['meet_start_date'];
  $parts[] = $org_slug;

  $slug = slugify(implode('-', $parts));
  $slug = preg_replace('/-+/', '-', $slug); // Collapse double dashes
  $metadata['slug'] = $slug;

  // Save raw content
  $folder = $type_to_folder[$metadata['type']] ?? $metadata['type'];
  $raw_dir = RAW_DIR . "$folder/";
  if (!is_dir($raw_dir)) {
    mkdir($raw_dir, 0755, true);
  }
  $raw_path = $raw_dir . "$slug.txt";
  file_put_contents($raw_path, $content);

  // Save metadata
  if (isset($_ENV['MONGODB_URI'])) {
    $mongo = new MongoDBLibrary();
    $result = $mongo->update_doc_if_newer(['slug' => $slug, 'type' => $metadata['type']], $metadata);

    $link = BASE_URL . "/$folder/$slug";

    if ($result) {
      return [
        'status' => 'success',
        'message' => "✅ Uploaded: <a href=\"$link\" target=\"_blank\">View " . htmlspecialchars($metadata['meet_name']) . ($metadata['sheet_name'] ? " (" . htmlspecialchars($metadata['sheet_name']) . ")" : "") . "</a>."
      ];
    } else {
      return [
        'status' => 'skipped',
        'message' => "⏩ Skipped (Already Exists): <a href=\"$link\" target=\"_blank\">View " . htmlspecialchars($metadata['meet_name']) . ($metadata['sheet_name'] ? " (" . htmlspecialchars($metadata['sheet_name']) . ")" : "") . "</a>."
      ];
    }
  } else {
    $all_meta = load_meta_json();
    $exists = false;

    foreach ($all_meta as &$doc) {
      if ($doc['slug'] === $slug && $doc['type'] === $metadata['type']) {
        if (!empty($doc['file_datetime']) && strtotime($metadata['file_datetime']) <= strtotime($doc['file_datetime'])) {
          $exists = true;
        } else {
          $doc = $metadata;
        }
        break;
      }
    }

    if (!$exists) {
      $all_meta[] = $metadata;
    }

    save_meta_json($all_meta);

    $link = BASE_URL . "/$folder/$slug";

    return [
      'status' => $exists ? 'skipped' : 'success',
      'message' => ($exists ? "⏩ Skipped:" : "✅ Uploaded:") . " <a href=\"$link\" target=\"_blank\">View " . htmlspecialchars($metadata['meet_name']) . ($metadata['sheet_name'] ? " (" . htmlspecialchars($metadata['sheet_name']) . ")" : "") . "</a>."
    ];
  }
}
