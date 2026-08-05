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
require_once __DIR__ . '/results_layout_adapters.php';


function handle_text_upload(string $content, string $manual_start_date = '', string $manual_end_date = ''): array
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

  // Type to folder mapping
  $type_to_folder = [
    'heat_sheets' => 'heat-sheets',
    'psych_sheets' => 'psych-sheets',
    'results'       => 'results',
    'standards'     => 'standards', // handled by upload-file-handler.php
    'events'        => 'events' // handled by upload-file-handler.php
  ];

  // Find the first HY-TEK header line
  $start_index = null;
  foreach ($lines as $i => $line) {
    if (stripos($line, 'HY-TEK') !== false && preg_match('/-?\s*HY-TEK.*MEET MANAGER/i', $line)) {
      $start_index = $i;
      break;
    }
  }

  // Error if no HY-TEK line found
  if ($start_index === null) {
    return [
      'status' => 'error',
      'message' => '❌ Error: HY-TEK header not found. Unsupported file format.'
    ];
  }

  // Slice from first valid HY-TEK line onward
  $lines = array_slice($lines, $start_index);

  // Try to get org and file_datetime from first line
  foreach ($lines as $index => $line) {
    $line = trim($line);

    if ($index === 0) {
      // Try to get organization before HY-TEK
      if (preg_match('/^(.*?)\s*-?\s*HY-TEK/i', $line, $matches)) {
        $org = trim($matches[1]);
        $org = preg_replace('/\s+-?\s*Organization License$/i', '', $org);
        if (!empty($org)) {
          $metadata["organization"] = $org;
        }
      } else {
        return [
          'status' => 'error',
          'message' => '❌ Error: Unable to detect organization or HY-TEK header. Unsupported file format.'
        ];
      }

      // Try to get full datetime
      if (preg_match('/(\d{1,2}:\d{2} (AM|PM) \d{1,2}\/\d{1,2}\/\d{4})/', $line, $matches)) {
        $metadata["file_datetime"] = trim($matches[1]);
      }

      // Fallback: just a date like 3/31/2025
      if (empty($metadata["file_datetime"]) && preg_match('/\b(\d{1,2}\/\d{1,2}\/\d{4})\b/', $line, $matches)) {
        $metadata["file_datetime"] = trim($matches[1]);
      }
    }

    // Get meet name + date range
    if ($index === 1) {
      if (preg_match('/^(.+?)\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4}) to (\d{1,2}\/\d{1,2}\/\d{4})$/', $line, $matches)) {
        $metadata["meet_name"] = trim($matches[1]);
        $metadata["meet_start_date"] = format_date_to_iso($matches[2]);
        $metadata["meet_end_date"] = format_date_to_iso($matches[3]);
      } elseif (preg_match('/^(.+?)\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})$/', $line, $matches)) {
        $metadata["meet_name"] = trim($matches[1]);
        $metadata["meet_start_date"] = format_date_to_iso($matches[2]);
        $metadata["meet_end_date"] = format_date_to_iso($matches[2]);
      } elseif ($line !== '') {
        // HY-TEK 8.0 meet programs can omit dates from this title line.
        // Preserve the meet name and request the dates from the uploader.
        $metadata["meet_name"] = $line;
      }
    }

    // Detect type
    if (stripos($line, "Psych Sheet") !== false) {
      $metadata["type"] = "psych_sheets";
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

  // Fallback org from "Hosted by"
  if (empty($metadata["organization"])) {
    foreach ($lines as $line) {
      if (stripos($line, "Hosted by") !== false) {
        if (preg_match('/Hosted by\s+(?:the\s+)?(.+?)(,|\s+Sanction|\s*$)/i', $line, $matches)) {
          $metadata["organization"] = trim($matches[1]);
        }
        break;
      }
    }
  }

  // Some HY-TEK exports omit the usual "Meet - start to end" line. Accept
  // dates supplied by the uploader in that case, but never overwrite dates
  // that were present in the source document.
  if (empty($metadata['meet_start_date']) && $manual_start_date !== '') {
    $start = DateTime::createFromFormat('!Y-m-d', $manual_start_date);
    $start_errors = DateTime::getLastErrors();
    if (!$start || ($start_errors !== false && ($start_errors['warning_count'] || $start_errors['error_count'])) || $start->format('Y-m-d') !== $manual_start_date) {
      return [
        'status' => 'missing_dates',
        'message' => 'Please enter a valid meet start date.'
      ];
    }

    $metadata['meet_start_date'] = $manual_start_date;
    $metadata['meet_end_date'] = $manual_end_date !== '' ? $manual_end_date : $manual_start_date;

    if ($manual_end_date !== '') {
      $end = DateTime::createFromFormat('!Y-m-d', $manual_end_date);
      $end_errors = DateTime::getLastErrors();
      if (!$end || ($end_errors !== false && ($end_errors['warning_count'] || $end_errors['error_count'])) || $end->format('Y-m-d') !== $manual_end_date || $end < $start) {
        return [
          'status' => 'missing_dates',
          'message' => 'Please enter a valid meet end date that is on or after the start date.'
        ];
      }
    }
  }

  // `file_datetime` belongs to the source document. Do not substitute the
  // upload time here, or it becomes misleading on the public page.
  $metadata['added_at'] = date(DATE_ATOM);

  if (empty($metadata["meet_name"]) || empty($metadata["type"])) {
    return [
      'status' => 'error',
      'message' => '❌ Error: Could not identify meet name or type.'
    ];
  }

  if (empty($metadata['meet_start_date'])) {
    return [
      'status' => 'missing_dates',
      'message' => 'This HY-TEK document does not include meet dates. Please enter them below.'
    ];
  }

  if ($metadata['type'] === 'results') {
    $paste_quality = (new ResultsPasteQualityDetector())->analyze($content);
    if ($paste_quality['quality'] === 'scrambled') {
      return [
        'status' => 'error',
        'message' => '❌ The pasted results appear to have scrambled PDF columns. Download the PDF and open it directly in the Google Chrome browser—not in Google Drive’s built-in PDF viewer. Then select all, copy, and paste again.'
      ];
    }
  }

  // Build base string for hashing (include sheet_name if present)
  $base_parts = [];
  if (!empty($metadata['sheet_name'])) {
    $base_parts[] = $metadata['sheet_name'];
  }
  $base_parts[] = $metadata['meet_name'];
  $base_parts[] = $metadata['meet_start_date'];
  $base_parts[] = $metadata['organization'];

  $base = implode('-', $base_parts);
  $hash = substr(sha1($base), 0, 6);

  // Slugified visible portion (omit org)
  $slug_parts = [];
  if (!empty($metadata['sheet_name'])) {
    $slug_parts[] = $metadata['sheet_name'];
  }
  $slug_parts[] = $metadata['meet_name'];
  // $slug_parts[] = $metadata['meet_start_date']; // remove this to make url shorter
  $slug_parts[] = $hash;

  $slug = slugify(implode('-', $slug_parts));
  $slug = preg_replace('/-+/', '-', $slug); // Collapse dashes
  $metadata['slug'] = $slug;

  // Save raw content
  $folder = $type_to_folder[$metadata['type']] ?? $metadata['type'];
  $raw_dir = RAW_DIR . "$folder/";
  if (!is_dir($raw_dir)) {
    mkdir($raw_dir, 0755, true);
  }
  $raw_path = $raw_dir . "$slug.txt";
  file_put_contents($raw_path, $content);

  // Save metadata (MongoDB or file-based)
  if (isset($_ENV['MONGODB_URI'])) {
    $mongo = new MongoDBLibrary();
    $result = $mongo->update_doc_if_newer(['slug' => $slug, 'type' => $metadata['type']], $metadata);

    $link = BASE_URL . "/$folder/$slug";

    return [
      'status' => $result ? 'success' : 'skipped',
      'message' => ($result ? "✅ Uploaded:" : "⏩ Skipped (Already Exists):") .
        " <a href=\"$link\" target=\"_blank\">View " . htmlspecialchars($metadata['meet_name']) .
        ($metadata['sheet_name'] ? " (" . htmlspecialchars($metadata['sheet_name']) . ")" : "") . "</a>."
    ];
  } else {
    // Fallback to meta.json
    $all_meta = load_meta_json();
    $exists = false;
    $matched = false;

    foreach ($all_meta as &$doc) {
      if ($doc['slug'] === $slug && $doc['type'] === $metadata['type']) {
        $matched = true;
        if (
          !empty($doc['file_datetime']) &&
          !empty($metadata['file_datetime']) &&
          strtotime($metadata['file_datetime']) < strtotime($doc['file_datetime'])
        ) {
          $exists = true;
        } else {
          // Preserve when this document first appeared on SwimSnap.
          $metadata['added_at'] = $doc['added_at'] ?? $metadata['added_at'];
          $doc = $metadata;
        }
        break;
      }
    }
    unset($doc);

    if (!$matched) {
      $all_meta[] = $metadata;
    }

    save_meta_json($all_meta);

    $link = BASE_URL . "/$folder/$slug";

    return [
      'status' => $exists ? 'skipped' : 'success',
      'message' => ($exists ? "⏩ Skipped:" : "✅ Uploaded:") .
        " <a href=\"$link\" target=\"_blank\">View " . htmlspecialchars($metadata['meet_name']) .
        ($metadata['sheet_name'] ? " (" . htmlspecialchars($metadata['sheet_name']) . ")" : "") . "</a>."
    ];
  }
}
