<?php

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/standards_parser.php'; // for process_standards()

function get_meet_start_date(string $path): ?DateTime
{
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (empty($lines)) return null;

  $header = explode(';', $lines[0]);
  $start_date_raw = $header[2] ?? null;

  if (!$start_date_raw) return null;

  $formats = ['m/d/Y', 'm/d/y'];
  foreach ($formats as $format) {
    $date = DateTime::createFromFormat($format, $start_date_raw);
    if ($date instanceof DateTime) return $date;
  }

  return null;
}

function parse_ev3_file(string $path): array
{
  $slug = basename($path, '.ev3');
  $meet_start_date = get_meet_start_date($path);
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $events = [];

  // Try to load standards if available
  $standards_path = RAW_DIR . 'standards/' . $slug . '.hyv';
  $cut_lookup = [];

  if (file_exists($standards_path)) {
    $content = file_get_contents($standards_path);
    $parsed = process_standards($content);

    foreach ($parsed['standards'] as $cut) {
      $key = $cut['event_number'];
      $cut_lookup[$key] = [
        'lcm_cut' => $cut['lcm_cut'],
        'scy_cut' => $cut['scy_cut']
      ];
    }
  }

  foreach ($lines as $line) {
    if (!preg_match('/^\d+;\w+;/', $line)) continue; // skip non-event lines

    $line = rtrim($line, "*>\r\n");
    $fields = explode(';', $line);

    if (count($fields) < 26) continue;

    $event_number = (int)$fields[0];
    $gender_code = strtoupper($fields[5] ?? '');
    $type = strtoupper($fields[4] ?? 'I');
    $stroke_code = strtoupper($fields[9] ?? '');
    $distance = $fields[8] ?? '';
    $event_time = $fields[24] ?? '';
    $course = $fields[25] ?? '';
    $session = (int)($fields[23] ?? 0);
    $age_min = (int)($fields[6] ?? 0);
    $age_max = (int)($fields[7] ?? 0);

    $age_group = match (true) {
      $age_min === 0 && $age_max >= 109 => "All Ages",
      $age_min === $age_max             => "{$age_min}",
      $age_min === 0                    => "{$age_max} & Under",
      $age_max >= 109 || $age_max > 18  => "{$age_min} & Over",
      default                           => "{$age_min}-{$age_max}"
    };

    $gender = match ($gender_code) {
      'G', 'W', 'F' => 'Girls',
      'B', 'M'      => 'Boys',
      'X', 'P'      => 'Mixed',
      default       => 'Unknown'
    };

    $stroke_map = [
      'A' => 'Free',
      'B' => 'Back',
      'C' => 'Breast',
      'D' => 'Fly',
      'E' => 'IM' // Medley Relay if type is Relay
    ];
    $stroke_base = $stroke_map[$stroke_code] ?? 'Unknown';
    $stroke = ($type === 'R')
      ? ($stroke_code === 'E' ? 'Medley Relay' : "$stroke_base Relay")
      : $stroke_base;

    $session_label = $session;
    if ($session > 0 && $meet_start_date) {
      $session_date = (clone $meet_start_date)->modify('+' . ($session - 1) . ' days');
      $session_label = $session . ' - ' . $session_date->format('l, M j');
    }

    // Cut time lookup
    $cut_key = $event_number;
    $cuts = $cut_lookup[$cut_key] ?? ['lcm_cut' => '-', 'scy_cut' => '-'];

    $events[$session_label][] = [
      'event_number' => $event_number,
      'age_group'    => $age_group,
      'gender'       => $gender,
      'distance'     => $distance,
      'stroke'       => $stroke,
      'type'         => $type === 'R' ? 'Relay' : 'Individual',
      'event_time'   => $event_time,
      'course'       => $course,
      'lcm_cut'      => $cuts['lcm_cut'],
      'scy_cut'      => $cuts['scy_cut']
    ];
  }

  ksort($events);
  return $events;
}
