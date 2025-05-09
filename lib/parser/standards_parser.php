<?php

/**
 * Standards Parser for SwimSnap
 * Parses .hyv files into structured JSON.
 * Updated: 2025-04-30
 */

require_once __DIR__ . '/../utils.php'; // For helpers like format_date_to_iso()

function process_standards(string $content): array
{
  $lines = explode("\n", $content);
  $parsed_rows = [];

  if (!$lines || count($lines) < 2) {
    return ['standards' => []];
  }

  // Start from line 2 (skip header)
  for ($i = 1; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;

    $fields = explode(';', $line);

    if (count($fields) < 10) continue; // Invalid or broken line

    $age_group = ($fields[4] == 0 && $fields[5] == 0)
      ? 'Open'
      : "{$fields[4]}–{$fields[5]}";

    $parsed_rows[] = [
      'event_number' => $fields[0],
      'age_group'    => $age_group,
      'gender'       => $fields[2] === 'F' ? 'F' : 'M',
      'distance'     => $fields[6],
      'stroke'       => get_stroke_label($fields[7], $fields[3]),
      'lcm_cut'      => get_first_valid_cut($fields, [9]),
      'scy_cut'      => get_first_valid_cut($fields, [13, 15, 16, 17])
    ];
  }

  return ['standards' => $parsed_rows];
}

function get_stroke_label($code, $type)
{
  $map = [
    1 => 'Free',
    2 => 'Back',
    3 => 'Breast',
    4 => 'Fly',
    5 => 'IM'
  ];
  $label = $map[(int) $code] ?? 'Unknown';

  return $type === 'R'
    ? ($code == 5 ? 'Medley Relay' : "$label Relay")
    : $label;
}

function get_first_valid_cut(array $fields, array $indexes): string
{
  foreach ($indexes as $i) {
    if (isset($fields[$i]) && trim($fields[$i]) !== '' && $fields[$i] !== '0.01') {
      return $fields[$i];
    }
  }
  return '-';
}
