<?php

/**
 * Meet Results Parser for SwimSnap
 * Parses HY-TEK Results text into structured JSON.
 * Updated: 2025-04-30
 */

require_once __DIR__ . '/../utils.php';

function parse_result_line($line)
{
  // Compact dual-meet individual row:
  // "Lakeville North High School71 Linsmeyer, Ariah 2:20.54 6"
  // "Lakeville South High School8--- Schliep, Claire X2:59.91"
  if (preg_match('/^(.+?)(\d{1,2})(\*?\d+|---)\s+([^,]+),\s+(.+?)\s+([Xx]?(?:NT|DQ|DFS|(?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}))(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)) {
    $result = $m[6];
    $note = null;
    if (preg_match('/^[Xx](.+)$/', $result, $mx)) {
      $note = 'X';
      $result = $mx[1];
    }

    return [
      "rank" => $m[3] === '---' ? null : ltrim($m[3], '*'),
      "name" => trim($m[4]) . ' ' . trim($m[5]),
      "age" => (int)$m[2],
      "team" => trim($m[1]),
      "seed_time" => null,
      "result_time" => in_array($result, ['DQ', 'DFS', 'NT']) ? null : $result,
      "note" => in_array($result, ['DQ', 'DFS', 'NT']) ? $result : $note,
      "points" => isset($m[7]) ? (float)$m[7] : null,
      "qualified" => false,
      "relay" => null,
    ];
  }


  // echo $line . "<br />";
  // DQ line
  if (preg_match('/^---\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+([A-Z\-]{2,})$/i', $line, $m)) {
    return [
      "rank" => null,
      "name" => trim($m[1]),
      "age" => (int)$m[2],
      "team" => trim($m[3]),
      "seed_time" => $m[4],
      "result_time" => $m[5],
      "note" => "",
      "qualified" => false,
      "relay" => null,
    ];
  }

  // Full team, no points, with optional trailing note (e.g. q, J1)
  if (preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[A-Z]?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)(?:\s+(\w{1,4}))?$/i', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => $m[6],
      "result_time" => $m[7],
      "note" => $m[8] ?? null,
      "qualified" => false,
      "relay" => null
    ];
  }

  // Full team + points, no seed time
  if (preg_match('/^(\*?\d+|---)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([Xx]?(?:NT|DQ|DFS|(?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}))\s+(\d+(?:\.\d+)?)$/i', $line, $m)) {
    $result = $m[6];
    $note = null;
    if (preg_match('/^[Xx](.+)$/', $result, $mx)) {
      $note = 'X';
      $result = $mx[1];
    }

    return [
      "rank" => $m[1] === '---' ? null : ltrim($m[1], '*'),
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => null,
      "result_time" => in_array($result, ['DQ', 'DFS', 'NT']) ? null : $result,
      "note" => in_array($result, ['DQ', 'DFS', 'NT']) ? $result : $note,
      "points" => (float)$m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Full team + points
  if (preg_match('/^(\*?\d+)\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+(\d+)$/i', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => trim($m[4]),
      "seed_time" => $m[5],
      "result_time" => $m[6],
      "points" => (int)$m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Abbreviated team + points
  if (preg_match('/^(\*?\d+)\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+(\d+)$/i', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => trim($m[4]),
      "seed_time" => $m[5],
      "result_time" => $m[6],
      "points" => (int)$m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Full team, no points
  if (preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[A-Z]?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)$/i', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => $m[6],
      "result_time" => $m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Abbreviated team, no points
  if (preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[A-Z]?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)$/i', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => $m[6],
      "result_time" => $m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Fallback for: rank name1 name2 age team "NT" seed + final time
  if (preg_match('/^(\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?-[A-Z]{2})\s+NT\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    // echo "<pre>🧪 Matched NT seed fallback: $line</pre>";
    return [
      "rank" => $m[1],
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => 'NT',
      "result_time" => $m[6],
      "qualified" => false,
      "relay" => null
    ];
  }

  // fallback for simple results: rank, name, age, team, final time only
  if (preg_match('/^(\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/i', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => $m[5],
      "seed_time" => null,
      "result_time" => $m[6],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Fallback: rank name age team result_time (no seed, no points)
  if (preg_match('/^(\d+)\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => $m[4],
      "seed_time" => null,
      "result_time" => $m[5],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Fallback for: rank name1 name2 age team final_time (no comma, no seed)
  if (preg_match('/^(\d+)\s+([A-Za-z\'\-\.\(\)]+)\s+([A-Za-z\'\-\.\(\)]+)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    echo "<pre>🧪 Matched simple fallback: $line</pre>";
    return [
      "rank" => $m[1],
      "name" => $m[2] . ' ' . $m[3],
      "age" => (int)$m[4],
      "team" => $m[5],
      "seed_time" => null,
      "result_time" => $m[6],
      "qualified" => false,
      "relay" => null
    ];
  }

  // New fallback: rank name age team seed_time final_time (no comma, no points)
  if (preg_match('/^(\d+)\s+([A-Za-z\'\-\.\(\)]+(?:\s+[A-Za-z\'\-\.\(\)]+)+)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => trim($m[4]),
      "seed_time" => $m[5],
      "result_time" => $m[6],
      "qualified" => false,
      "relay" => null
    ];
  }

  // New fallback: rank (optional *), name, age, team, seed_time, result_time (with optional suffix)
  if (preg_match('/^(\*?\d+)\s+([A-Za-z\'\-\.\(\)]+(?:\s+[A-Za-z\'\-\.\(\)]+)+)\s+(\d{1,2})\s+(.+?)\s+([A-Z]?\d{1,2}[:.]\d{1,2}(?:\.\d{2})?[YLS]?)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)([A-Z])?$/', $line, $m)) {
    return [
      "rank" => ltrim($m[1], '*'),
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => trim($m[4]),
      "seed_time" => $m[5],
      "result_time" => $m[6] . ($m[7] ?? ''),
      "qualified" => false,
      "relay" => null
    ];
  }

  // Time trial fallback: rank name age team seed_time final_time (no comma)
  if (preg_match('/^(\d+)\s+([A-Za-z\'\-\.\(\)]+(?:\s+[A-Za-z\'\-\.\(\)]+)*)\s+(\d{1,2})\s+(.+?)\s+(NT|\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]),
      "age" => (int)$m[3],
      "team" => trim($m[4]),
      "seed_time" => $m[5],
      "result_time" => $m[6],
      "qualified" => false,
      "relay" => null
    ];
  }


  // fallback for: rank, name, age, FULL team name, NT seed, result time
  if (preg_match('/^(\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+(NT|\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => $m[6],
      "result_time" => $m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Prelim-style fallback: rank, name, age, FULL team name, one time (prelim/final), optional note
  if (preg_match('/^\*?(\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)(?:\s+([A-Z]{2,6}))?$/', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => trim($m[5]),
      "seed_time" => null,
      "result_time" => $m[6],
      "note" => $m[7] ?? null,
      "qualified" => false,
      "relay" => null
    ];
  }

  // // fallback: rank, last, first, age, team, final_time, note and with optional asterisk before rank
  if (preg_match('/^\*?(\d+)\s+([^,]+),\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)\s+([A-Z]{2,4})$/i', $line, $m)) {
    return [
      "rank" => $m[1],
      "name" => trim($m[2]) . ' ' . trim($m[3]),
      "age" => (int)$m[4],
      "team" => $m[5],
      "seed_time" => null,
      "result_time" => $m[6],
      "note" => $m[7],
      "qualified" => false,
      "relay" => null
    ];
  }

  // Compact individual row used in some relay-meet exports:
  // "Gyor i, Caleb1 22.20 16"
  if (str_contains($line, ',') && preg_match('/^(.+?)(\*?\d+)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2})(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)) {
    return [
      "rank" => ltrim($m[2], '*'),
      "name" => trim($m[1]),
      "age" => null,
      "team" => null,
      "seed_time" => null,
      "result_time" => $m[3],
      "points" => isset($m[4]) ? (float) $m[4] : null,
      "qualified" => false,
      "relay" => null
    ];
  }

  return null;
}

function parse_relay_line($line)
{
  // Compact dual-meet relay row:
  // "A1 Lakeville South High School 2:17.21 8"
  // "C5 Lakeville North High School x2:09.88"
  if (preg_match('/^([A-Z])(\*?\d+)\s+(.+?)\s+([Xx]?(?:DQ|DFS|NS|(?:\d{1,2}:)?\d{1,2}\.\d{2}))(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)) {
    $final = $m[4];
    $note = null;
    if (preg_match('/^[Xx](.+)$/', $final, $mx)) {
      $note = 'X';
      $final = $mx[1];
    }

    return [
      'rank' => ltrim($m[2], '*'),
      'team' => trim($m[3]),
      'relay' => $m[1],
      'seed_time' => null,
      'finals_time' => in_array($final, ['DQ', 'DFS', 'NS']) ? null : $final,
      'status' => in_array($final, ['DQ', 'DFS', 'NS']) ? $final : null,
      'note' => $note,
      'points' => isset($m[5]) ? (float)$m[5] : null,
    ];
  }

  // Compact dual-meet relay DQ row:
  // "C--- Lakeville South High School DQ"
  if (preg_match('/^([A-Z])---\s+(.+?)\s+(DQ|DFS|NS)$/', $line, $m)) {
    return [
      'rank' => null,
      'team' => trim($m[2]),
      'relay' => $m[1],
      'seed_time' => null,
      'finals_time' => null,
      'status' => $m[3],
      'points' => null,
    ];
  }

  // Standard high-school relay row: "1 Lakeville South High School A 2:25.98 8"
  if (preg_match('/^(\*?\d+|---)\s+(.+?)\s+([A-Z])\s+([Xx]?(?:DQ|DFS|NS|(?:\d{1,2}:)?\d{1,2}\.\d{2}))\s*(\d+(?:\.\d+)?)?$/', $line, $m)) {
    $final = $m[4];
    $note = null;
    if (preg_match('/^[Xx](.+)$/', $final, $mx)) {
      $note = 'X';
      $final = $mx[1];
    }

    return [
      'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
      'team' => trim($m[2]),
      'relay' => $m[3],
      'seed_time' => null,
      'finals_time' => in_array($final, ['DQ', 'DFS', 'NS']) ? null : $final,
      'status' => in_array($final, ['DQ', 'DFS', 'NS']) ? $final : null,
      'note' => $note,
      'points' => isset($m[5]) ? (float)$m[5] : null,
    ];
  }

  // Compact relay row: "AKELL-NC1 1:50.99 32"
  if (preg_match('/^([A-Z0-9\-]+)(\*?\d+)\s+(\d{1,2}:\d{2}\.\d{2}|\d{1,2}\.\d{2})(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)) {
    return [
      'rank' => ltrim($m[2], '*'),
      'team' => trim($m[1]),
      'relay' => 'A',
      'seed_time' => null,
      'finals_time' => $m[3],
      'status' => null,
      'points' => isset($m[4]) ? (float) $m[4] : null,
    ];
  }

  // Compact relay status row: "CLTCS-NC--- DQ"
  if (preg_match('/^([A-Z0-9\-]+)---\s+(DQ|DFS|NS)$/', $line, $m)) {
    return [
      'rank' => null,
      'team' => trim($m[1]),
      'relay' => 'A',
      'seed_time' => null,
      'finals_time' => null,
      'status' => $m[2],
      'points' => null,
    ];
  }

  if (preg_match('/^(\*?\d+|---)\s+(.+?)\s+(\w)\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)\s+(DQ|DFS|\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)(?:\s+(\d+))?$/', $line, $m)) {
    return [
      'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
      'team' => trim($m[2]),
      'relay' => $m[3],
      'seed_time' => $m[4],
      'finals_time' => in_array($m[5], ['DQ', 'DFS']) ? null : $m[5],
      'status' => in_array($m[5], ['DQ', 'DFS']) ? $m[5] : null,
      'points' => isset($m[6]) ? (int)$m[6] : null,
    ];
  }

  // Fallback: relay result with only final time (no seed time)
  if (preg_match('/^(\*?\d+|---)\s+([A-Z0-9\'\-\. ]+)\s+([A-Z])\s+(\d{1,2}[:.]\d{1,2}(?:\.\d{2})?)$/', $line, $m)) {
    return [
      'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
      'team' => trim($m[2]),
      'relay' => $m[3],
      'seed_time' => null,
      'finals_time' => $m[4],
      'status' => null,
      'points' => null,
    ];
  }

  return null;
}

function parse_split_line($line)
{
  $splits = [];
  $tokens = preg_split('/\s+/', trim($line));

  if (empty($tokens)) {
    return $splits;
  }

  // Remove reaction time tokens like r:+0.74
  $tokens = array_filter($tokens, function ($token) {
    return !preg_match('/^r:\+\d+\.\d{2}$/', $token);
  });

  $tokens = array_values($tokens);

  if (isset($tokens[1]) && preg_match('/^\(.*\)$/', $tokens[1])) {
    // Relay style
    foreach ($tokens as $token) {
      if (preg_match('/\(([\d:.]+)\)/', $token, $match)) {
        $splits[] = $match[1];
      }
    }
  } else {
    // Normal style
    if (isset($tokens[0])) {
      $splits[] = $tokens[0];
    }
    foreach ($tokens as $token) {
      if (preg_match('/\(([\d:.]+)\)/', $token, $match)) {
        $splits[] = $match[1];
      }
    }
  }

  // Final safety check: all items in $splits must be valid time format
  foreach ($splits as $time) {
    if (!preg_match('/^(\d{1,2}:)?\d{1,2}\.\d{2}$/', $time)) {
      return []; // Invalid, discard as split line
    }
  }

  return $splits;
}

function process_results($content)
{
  $lines = explode("\n", $content);

  // 🚨 Limit maximum lines processed
  if (count($lines) > 10000) {
    $lines = array_slice($lines, 0, 10000);
  }


  $events = [];
  $current_event = null;
  $current_results = [];
  $current_round = 'Finals'; // default
  $in_relay = false;
  $stacked_layout = false;
  $pending_team = null;
  $pending_seed_time = null;
  $pending_individual = null;
  $pending_relay = null;
  $pending_stacked_result = null;
  $pending_dual_individual = null;
  $pending_relay_header = null;
  $pending_ranks = [];
  $last_line_type = 'other';
  $newspaper_pending = null;

  $reset_stacked_state = static function () use (&$stacked_layout, &$pending_team, &$pending_seed_time, &$pending_individual, &$pending_relay, &$pending_stacked_result, &$pending_dual_individual, &$pending_relay_header, &$pending_ranks, &$last_line_type, &$newspaper_pending) {
    $stacked_layout = false;
    $pending_team = null;
    $pending_seed_time = null;
    $pending_individual = null;
    $pending_relay = null;
    $pending_stacked_result = null;
    $pending_dual_individual = null;
    $pending_relay_header = null;
    $pending_ranks = [];
    $last_line_type = 'other';
    $newspaper_pending = null;
  };

  $is_metric = static function (string $value): bool {
    $value = trim($value);
    return (bool) preg_match('/^(?:NT|DQ|DFS|(?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2})$/', $value);
  };

  $parse_points = static function (?string $value) {
    if ($value === null) return null;
    $value = preg_replace('/\s*\.\s*/', '.', trim($value));
    if ($value === '' || !is_numeric($value)) return null;
    $num = (float) $value;
    return floor($num) == $num ? (int) $num : $num;
  };

  $is_school_year = static function (string $value): bool {
    return (bool) preg_match('/^(?:FR|SO|JR|SR|[0-9]{1,2})$/i', trim($value));
  };

  $is_newspaper_header = static function (string $value): bool {
    return (bool) preg_match('/^(?:Name(?:\s+Age\s+Team)?|Finals Time|Relay|Team Finals Time)$/i', trim($value));
  };

  $split_stacked_school_and_name = static function (string $value): ?array {
    if (!str_contains($value, ',')) return null;
    if (!preg_match('/^(.+)([A-Z][A-Za-z\'\.\-]+,\s+[A-Za-z\'\.\-\(\) ]+)$/', $value, $m)) {
      return null;
    }

    $team = trim($m[1]);
    $name = trim($m[2]);

    if ($team === '' || $name === '') return null;

    return [$team, $name];
  };

  $finalize_pending_relay = static function () use (&$pending_relay, &$current_results, &$current_round) {
    if (!$pending_relay) return;
    if (!isset($pending_relay['finals_time']) && isset($pending_relay['_pending_time'])) {
      $pending_relay['finals_time'] = $pending_relay['_pending_time'];
      unset($pending_relay['_pending_time']);
    }
    unset($pending_relay['_awaiting_seed']);
    $pending_relay['round'] = $current_round;
    $current_results[] = $pending_relay;
    $pending_relay = null;
  };

  foreach ($lines as $line) {
    $line = trim($line);

    if (empty($line) || str_contains($line, 'HY-TEK') || str_starts_with($line, 'Sanction #:') || str_starts_with($line, 'Page')) {
      continue;
    }

    if (strcasecmp(trim($line), 'Results') === 0) {
      continue;
    }

    if (
      str_starts_with($line, '3A State Rec:') ||
      str_starts_with($line, 'State Record:') ||
      str_starts_with($line, 'All-Am. Auto:') ||
      str_starts_with($line, 'All-Am. Cons:')
    ) {
      continue;
    }

    if (
      strcasecmp($line, 'Prelim Time Points') === 0 ||
      strcasecmp($line, 'Seed Time') === 0 ||
      strcasecmp($line, 'Seed Time Points') === 0 ||
      strcasecmp($line, 'Name Finals Time') === 0 ||
      strcasecmp($line, 'Yr School') === 0 ||
      strcasecmp($line, 'Name School Finals Time') === 0 ||
      strcasecmp($line, 'Name School Finals Time Points') === 0 ||
      strcasecmp($line, 'Name School Finals Score') === 0 ||
      strcasecmp($line, 'Name School Finals Score Points') === 0 ||
      strcasecmp($line, 'Yr SchoolName Finals Time') === 0
    ) {
      $stacked_layout = true;
      $last_line_type = 'other';
      continue;
    }

    if (
      strcasecmp($line, 'YrName School Finals Time') === 0 ||
      strcasecmp($line, 'YrName School Finals Time Points') === 0 ||
      strcasecmp($line, 'YrName School Finals Score') === 0 ||
      strcasecmp($line, 'YrName School Finals Score Points') === 0
    ) {
      $stacked_layout = false;
      $in_relay = false;
      $last_line_type = 'other';
      continue;
    }

    if (preg_match('/^Team Relay\s+(?:Finals TimePrelim Time|Prelim TimeSeed Time|Finals TimeSeed Time)$/i', $line)) {
      $stacked_layout = true;
      $in_relay = true;
      $last_line_type = 'other';
      continue;
    }

    if (
      strcasecmp($line, 'Team Relay Finals Time Points') === 0 ||
      strcasecmp($line, 'Team Relay Finals Time Score') === 0
    ) {
      $stacked_layout = true;
      $in_relay = true;
      $last_line_type = 'other';
      continue;
    }

    if (preg_match('/^Name School\s+(?:Finals TimePrelim Time|Prelim TimeSeed Time|Finals TimeSeed Time)$/i', $line)) {
      $stacked_layout = true;
      $in_relay = false;
      $last_line_type = 'other';
      continue;
    }

    if (
      strcasecmp($line, 'Name Age Team Finals Time') === 0 ||
      strcasecmp($line, 'Name Age Team Seed Time Finals Time') === 0
    ) {
      $stacked_layout = false;
      $last_line_type = 'other';
      continue;
    }

    if (
      !$in_relay &&
      $current_event &&
      $current_event['event_number'] === null &&
      preg_match('/^(?:Girls|Boys)\s+\d/', $current_event['gender'] . ' ' . $current_event['event_name'])
    ) {
      if (
        $newspaper_pending &&
        $is_newspaper_header($line)
      ) {
        continue;
      }

      if (
        !$newspaper_pending &&
        preg_match('/^(\*?\d+|---)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS|SCR)(?:\s+([A-Z]+|\d+(?:\.\d+)?))?$/', $line, $m)
      ) {
        $extra = $m[3] ?? null;
        $newspaper_pending = [
          'count' => 1,
          'stage' => 'name',
          'index' => 0,
          'entries' => [[
            'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
            'name' => null,
            'age' => null,
            'team' => null,
            'seed_time' => null,
            'result_time' => in_array($m[2], ['DQ', 'DFS', 'SCR']) ? null : $m[2],
            'note' => in_array($m[2], ['DQ', 'DFS', 'SCR']) ? $m[2] : (is_numeric((string) $extra) ? null : $extra),
            'points' => is_numeric((string) $extra) ? $parse_points($extra) : null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ]],
        ];
        continue;
      }

      if (
        !$newspaper_pending &&
        preg_match('/^(\d+)(?:\s+(\d+))?\s+(.+)$/', $line, $m) &&
        !preg_match('/^\(?Event\b/i', $line)
      ) {
        $newspaper_pending = [
          'count' => isset($m[2]) && $m[2] !== '' ? 2 : 1,
          'stage' => isset($m[2]) && $m[2] !== '' ? 'name2' : 'ages',
          'index' => 0,
          'entries' => [
            [
              'rank' => (int) $m[1],
              'name' => trim($m[3]),
              'age' => null,
              'team' => null,
              'seed_time' => null,
              'result_time' => null,
              'qualified' => false,
              'relay' => null,
              'round' => $current_round,
            ],
          ],
        ];

        if (isset($m[2]) && $m[2] !== '') {
          $newspaper_pending['entries'][] = [
            'rank' => (int) $m[2],
            'name' => null,
            'age' => null,
            'team' => null,
            'seed_time' => null,
            'result_time' => null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ];
        }

        continue;
      }

      if ($newspaper_pending) {
        $count = $newspaper_pending['count'];
        $idx = $newspaper_pending['index'];

        if ($newspaper_pending['stage'] === 'name') {
          $newspaper_pending['entries'][0]['name'] = $line;
          $newspaper_pending['stage'] = 'ages';
          continue;
        }

        if ($newspaper_pending['stage'] === 'name2') {
          $newspaper_pending['entries'][1]['name'] = $line;
          $newspaper_pending['stage'] = 'ages';
          $newspaper_pending['index'] = 0;
          continue;
        }

        if ($newspaper_pending['stage'] === 'ages' && preg_match('/^\d{1,2}$/', $line)) {
          $newspaper_pending['entries'][$idx]['age'] = (int) $line;
          $newspaper_pending['index']++;
          if ($newspaper_pending['index'] >= $count) {
            $newspaper_pending['stage'] = 'teams';
            $newspaper_pending['index'] = 0;
          }
          continue;
        }

        if ($newspaper_pending['stage'] === 'teams' && preg_match('/^[A-Z0-9\-]+$/', $line)) {
          $newspaper_pending['entries'][$idx]['team'] = $line;
          $newspaper_pending['index']++;
          if ($newspaper_pending['index'] >= $count) {
            $newspaper_pending['stage'] = 'times';
            $newspaper_pending['index'] = 0;
          }
          continue;
        }

        if ($newspaper_pending['stage'] === 'times' && preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)) {
          $entry = &$newspaper_pending['entries'][$idx];
          $entry['result_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
          $entry['note'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : null;
          $entry['points'] = $parse_points($m[2] ?? null);
          unset($entry);

          $newspaper_pending['index']++;
          if ($newspaper_pending['index'] >= $count) {
            foreach ($newspaper_pending['entries'] as $entry) {
              if (!empty($entry['name']) && $entry['age'] !== null && !empty($entry['team'])) {
                $current_results[] = $entry;
              }
            }
            $newspaper_pending = null;
          }
          continue;
        }
      }
    }

    // 🔥 Support simple event headers like "#101 Girls 11-12 100 Free"
    if (preg_match('/^#?(\d+)\s+([MF]ixed|Girls|Boys)\s+(.*)$/i', $line, $m)) {
      $finalize_pending_relay();
      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = [
        'event_number' => (int)$m[1],
        'gender' => $m[2],
        'event_name' => normalize_event_name($m[3]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = stripos($current_event['event_name'], 'Relay') !== false;
      $reset_stacked_state();
      continue;
    }

    // Fallback event header: "Girls 8 & Under 100 LC Meter Freestyle"
    if (preg_match('/^(Girls|Boys|Mixed)\s+(\d.*)$/i', $line, $m)) {
      $finalize_pending_relay();
      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = [
        'event_number' => null,
        'gender' => ucfirst(strtolower($m[1])),
        'event_name' => normalize_event_name($m[2]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = stripos($current_event['event_name'], 'Relay') !== false;
      $reset_stacked_state();
      continue;
    }

    // Additional fallback: "Women/Men 50 LC Meter Freestyle"
    if (preg_match('/^(Women|Men)\s+(\d.*)$/i', $line, $m)) {
      $gender = ucfirst(strtolower($m[1]));
      $event_name = normalize_event_name($m[2]);

      // 🔁 Skip if same event (e.g. repeated due to page break or round split)
      if ($current_event && $current_event['gender'] === $gender && $current_event['event_name'] === $event_name) {
        continue;
      }

      // ✅ Otherwise treat as new event
      $finalize_pending_relay();
      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = [
        'event_number' => null,
        'gender' => $gender,
        'event_name' => $event_name,
        'results' => []
      ];
      $current_results = [];
      $in_relay = stripos($current_event['event_name'], 'Relay') !== false;
      $reset_stacked_state();
      continue;
    }

    // Finals, Prelims round indicator
    if (preg_match('/^(Prelims|Preliminaries|Finals|Swim[- ]?Off|Time Trials?)/i', $line, $m)) {
      $finalize_pending_relay();
      $current_round = $m[1];
      $pending_team = null;
      $pending_seed_time = null;
      $pending_individual = null;
      $pending_ranks = [];
      $last_line_type = 'other';
      continue;
    }
    // Add support for rounds like "A - Final", "B - Final", etc.
    if (preg_match('/^([A-Z])\s*-\s*Final/i', $line, $m)) {
      $finalize_pending_relay();
      $current_round = $m[1] . ' Final';
      $pending_team = null;
      $pending_seed_time = null;
      $pending_individual = null;
      $pending_ranks = [];
      $last_line_type = 'other';
      continue;
    }

    if ($stacked_layout && preg_match('/^-\s*Swim-off$/i', $line)) {
      $current_round = 'Swim-Off';
      $pending_ranks = [];
      $last_line_type = 'other';
      continue;
    }

    // Standard "Event 1 Boys 200 Free" format
    if (preg_match('/\bEvent\s+\d+[A-Za-z]?\s+(?:Girls|Boys|Men|Women|Mixed)\b/i', $line, $m, PREG_OFFSET_CAPTURE)) {
      $finalize_pending_relay();
      $event_fragment = substr($line, $m[0][1]);
      $info = extract_event_info($event_fragment);
      if (!$info) continue;

      if ($current_event && $current_event['event_number'] === $info['event_number']) continue;

      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = $info + ['results' => []];
      $current_results = [];
      $in_relay = stripos($current_event['event_name'], 'Relay') !== false;
      $reset_stacked_state();
      continue;
    }

    if (preg_match('/^Team\s+Relay.*Finals\s+Time$/i', $line)) {
      $in_relay = true;
      $stacked_layout = true;
      $last_line_type = 'other';
      continue;
    }

    if (strcasecmp($line, 'Team Finals Time') === 0) {
      $in_relay = true;
      $stacked_layout = true;
      $last_line_type = 'other';
      continue;
    }

    if (preg_match('/^RelayTeam Finals Time$/i', $line)) {
      $in_relay = true;
      $stacked_layout = true;
      $last_line_type = 'other';
      continue;
    }

    if (str_starts_with($line, 'Team Relay Seed Time')) {
      $in_relay = true;
      $last_line_type = 'other';
      continue;
    }

    if ($in_relay) {
      $relay = parse_relay_line($line);
      if ($relay) {
        $relay['round'] = $current_round;
        $current_results[] = $relay;
        $last_line_type = 'other';
        continue;
      }
    }

    if ($stacked_layout && preg_match('/^\d+\)\s+/', $line)) {
      $finalize_pending_relay();
      $last_line_type = 'swimmer_list';
      continue;
    }

    if ($stacked_layout && preg_match('/^(?:\*?\d+|---)$/', $line)) {
      $pending_ranks[] = $line === '---' ? null : ltrim($line, '*');
      $last_line_type = 'other';
      continue;
    }

    if ($in_relay && $stacked_layout) {
      if (preg_match('/^[A-Z0-9\-]{2,10}$/', $line)) {
        if (!$pending_relay_header) {
          $pending_relay_header = [];
        }
        $pending_relay_header['team'] = $line;
        $last_line_type = 'other';
        continue;
      }

      if (($pending_relay_header['team'] ?? null) && preg_match('/^[A-Z]$/', $line)) {
        $pending_relay_header['relay'] = $line;
        $last_line_type = 'other';
        continue;
      }

      if (preg_match('/^(\*?\d+|---)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS|SCR)(?:\s+([A-Z]+|\d+(?:\.\d+)?))?$/', $line, $m)) {
        if (!$pending_relay_header) {
          $pending_relay_header = [];
        }
        $extra = $m[3] ?? null;
        $pending_relay_header['rank'] = $m[1] === '---' ? null : ltrim($m[1], '*');
        $pending_relay_header['status'] = in_array($m[2], ['DQ', 'DFS', 'SCR']) ? $m[2] : null;
        $pending_relay_header['finals_time'] = in_array($m[2], ['DQ', 'DFS', 'SCR']) ? null : $m[2];
        if ($extra !== null && $extra !== '') {
          if (is_numeric((string) $extra)) {
            $pending_relay_header['points'] = $parse_points($extra);
            unset($pending_relay_header['note']);
          } else {
            $pending_relay_header['note'] = $extra;
            unset($pending_relay_header['points']);
          }
        }

        if (($pending_relay_header['team'] ?? null) && ($pending_relay_header['relay'] ?? null)) {
          $pending_relay = [
            'rank' => $pending_relay_header['rank'],
            'team' => $pending_relay_header['team'],
            'relay' => $pending_relay_header['relay'],
            'seed_time' => null,
            'finals_time' => $pending_relay_header['finals_time'] ?? null,
            'status' => $pending_relay_header['status'] ?? null,
            'note' => $pending_relay_header['note'] ?? null,
            'points' => $pending_relay_header['points'] ?? null,
          ];
          $pending_relay_header = null;
        }

        $last_line_type = 'other';
        continue;
      }

      if ($pending_relay && preg_match('/^(\*?\d+|---)\s+/', $line) && !$is_metric($line)) {
        $finalize_pending_relay();
      }

      if ($pending_relay && $is_metric($line)) {
        if (isset($pending_relay['_pending_time'])) {
          if ($line === 'NT') {
            $pending_relay['seed_time'] = 'NT';
            $pending_relay['finals_time'] = $pending_relay['_pending_time'];
          } else {
            $pending_relay['seed_time'] = $pending_relay['_pending_time'];
            $pending_relay['finals_time'] = $line;
          }
          unset($pending_relay['_pending_time']);
          $last_line_type = 'other';
          continue;
        }

        if (($pending_relay['_awaiting_seed'] ?? false) && !isset($pending_relay['seed_time'])) {
          $pending_relay['seed_time'] = $line;
          unset($pending_relay['_awaiting_seed']);
          $last_line_type = 'other';
          continue;
        }
      }

      if (
        $pending_relay &&
        preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m) &&
        isset($pending_relay['_pending_time'])
      ) {
        $pending_relay['seed_time'] = $pending_relay['_pending_time'];
        $pending_relay['finals_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
        $pending_relay['status'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : ($pending_relay['status'] ?? null);
        $pending_relay['points'] = $parse_points($m[2] ?? null);
        unset($pending_relay['_pending_time']);
        $last_line_type = 'other';
        continue;
      }

      if ($pending_seed_time && preg_match('/^(\*?\d+|---)\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)) {
        $pending_relay = [
          'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
          'team' => trim($m[2]),
          'relay' => 'Relay',
          'seed_time' => $pending_seed_time,
          'finals_time' => in_array($m[3], ['DQ', 'DFS']) ? null : $m[3],
          'status' => in_array($m[3], ['DQ', 'DFS']) ? $m[3] : null,
          'points' => $parse_points($m[4] ?? null),
        ];
        $pending_seed_time = null;
        $last_line_type = 'other';
        continue;
      }

      if (preg_match('/^(\*?\d+|---)\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|NT|DQ|DFS)(?:\s+(.+))?$/', $line, $m)) {
        $time_or_status = $m[3];
        $points = $parse_points($m[4] ?? null);
        $pending_relay = [
          'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
          'team' => trim($m[2]),
          'relay' => 'Relay',
          'points' => $points,
          'status' => in_array($time_or_status, ['DQ', 'DFS']) ? $time_or_status : null,
        ];

        if ($time_or_status === 'NT') {
          $pending_relay['seed_time'] = 'NT';
        } elseif (in_array($time_or_status, ['DQ', 'DFS'])) {
          if ($pending_seed_time) {
            $pending_relay['seed_time'] = $pending_seed_time;
            $pending_seed_time = null;
          }
        } elseif ($pending_seed_time) {
          $pending_relay['seed_time'] = $pending_seed_time;
          $pending_relay['finals_time'] = $time_or_status;
          $pending_seed_time = null;
        } elseif ($points !== null) {
          $pending_relay['finals_time'] = $time_or_status;
          $pending_relay['_awaiting_seed'] = true;
        } else {
          $pending_relay['_pending_time'] = $time_or_status;
        }

        $last_line_type = 'other';
        continue;
      }

      if (
        !empty($pending_ranks) &&
        preg_match('/^(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s*([A-Z]))?\s*((?:\d{1,2}:)?\d{1,2}\.\d{2})?$/', $line, $m)
      ) {
        $rank = array_shift($pending_ranks);
        $team = trim($m[1]);
        $first_time_or_status = $m[2];
        $note = $m[3] ?? null;
        $second_time = $m[4] ?? null;

        $pending_relay = [
          'rank' => $rank,
          'team' => $team,
          'relay' => 'Relay',
          'status' => in_array($first_time_or_status, ['DQ', 'DFS']) ? $first_time_or_status : null,
        ];

        if (in_array($first_time_or_status, ['DQ', 'DFS'])) {
          if ($pending_seed_time) {
            $pending_relay['seed_time'] = $pending_seed_time;
            $pending_seed_time = null;
          }
        } elseif ($second_time !== null) {
          $pending_relay['finals_time'] = $first_time_or_status;
          $pending_relay['seed_time'] = $second_time;
        } elseif ($pending_seed_time) {
          $pending_relay['seed_time'] = $pending_seed_time;
          $pending_relay['finals_time'] = $first_time_or_status;
          $pending_seed_time = null;
        } else {
          $pending_relay['_pending_time'] = $first_time_or_status;
        }

        if ($note) {
          $pending_relay['note'] = $note;
        }

        $last_line_type = 'other';
        continue;
      }

      if ($is_metric($line) && $last_line_type !== 'swimmer_list' && $last_line_type !== 'split') {
        $pending_seed_time = $line;
        $last_line_type = 'other';
        continue;
      }

    }

    if ($stacked_layout && !$in_relay) {
      if (
        !$pending_individual &&
        !empty($pending_ranks) &&
        $is_school_year($line)
      ) {
        $pending_individual = [
          'rank' => array_shift($pending_ranks),
          'name' => null,
          'age' => preg_match('/^\d{1,2}$/', $line) ? (int) $line : null,
          'team' => null,
          'seed_time' => null,
          'result_time' => null,
          'note' => null,
          'points' => null,
          'qualified' => false,
          'relay' => null,
          'round' => $current_round,
          '_old_newspaper' => true,
          '_awaiting_team' => true,
          '_awaiting_seed_or_result' => true,
        ];
        $last_line_type = 'other';
        continue;
      }

      if (($pending_individual['_old_newspaper'] ?? false) === true) {
        if (
          ($pending_individual['_awaiting_team'] ?? false) &&
          !$is_metric($line) &&
          !str_contains($line, ',') &&
          !preg_match('/^(?:Name|Yr|School|Finals Time|Finals Score|Prelim Time|Seed Time|Points|Preliminaries|Prelims|Finals)$/i', $line)
        ) {
          $pending_individual['team'] = $line;
          unset($pending_individual['_awaiting_team']);
          $last_line_type = 'other';
          continue;
        }

        if (
          ($pending_individual['_awaiting_seed_or_result'] ?? false) &&
          $pending_individual['team'] !== null &&
          $is_metric($line) &&
          !str_contains($line, ',')
        ) {
          $pending_individual['seed_time'] = $line;
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_individual['team'] !== null &&
          preg_match('/^([^,]+),\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)([A-Z])?(?:\s+([A-Z][A-Z\-]*))?(?:\s+(\d+(?:\.\d+)?))?$/', $line, $m)
        ) {
          $pending_individual['name'] = trim($m[1]) . ' ' . trim($m[2]);
          $pending_individual['result_time'] = in_array($m[3], ['DQ', 'DFS']) ? null : $m[3];

          $note_parts = [];
          if (in_array($m[3], ['DQ', 'DFS'])) {
            $note_parts[] = $m[3];
          } else {
            if (!empty($m[4])) $note_parts[] = $m[4];
            if (!empty($m[5])) $note_parts[] = $m[5];
          }

          $pending_individual['note'] = !empty($note_parts) ? implode(' ', $note_parts) : null;
          $pending_individual['points'] = $parse_points($m[6] ?? null);

          unset(
            $pending_individual['_old_newspaper'],
            $pending_individual['_awaiting_team'],
            $pending_individual['_awaiting_seed_or_result']
          );
          $current_results[] = $pending_individual;
          $pending_individual = null;
          $last_line_type = 'other';
          continue;
        }
      }

      if (
        !$pending_stacked_result &&
        preg_match('/^(\*?\d+|---)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS|SCR)(?:\s+([A-Z]+|\d+(?:\.\d+)?))?$/', $line, $m)
      ) {
        $extra = $m[3] ?? null;
        $pending_stacked_result = [
          'rank' => $m[1] === '---' ? null : ltrim($m[1], '*'),
          'name' => null,
          'age' => null,
          'team' => null,
          'seed_time' => null,
          'result_time' => in_array($m[2], ['DQ', 'DFS', 'SCR']) ? null : $m[2],
          'note' => in_array($m[2], ['DQ', 'DFS', 'SCR']) ? $m[2] : null,
          'points' => is_numeric((string) $extra) ? $parse_points($extra) : null,
          'qualified' => false,
          'relay' => null,
          'round' => $current_round,
          '_meta' => is_numeric((string) $extra) ? null : $extra,
          '_stage' => 'name',
        ];
        $last_line_type = 'other';
        continue;
      }

      if ($pending_stacked_result) {
        if (
          $pending_stacked_result['_stage'] === 'name' &&
          !$is_metric($line) &&
          !preg_match('/^(?:Name|Yr School|Yr|School|Finals Time|Preliminaries|Prelims|Finals)$/i', $line)
        ) {
          $pending_stacked_result['name'] = $line;
          $pending_stacked_result['_stage'] = 'meta_or_team';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_stacked_result['_stage'] === 'meta_or_team' &&
          $is_school_year($line)
        ) {
          if (preg_match('/^\d{1,2}$/', $line)) {
            $pending_stacked_result['age'] = (int) $line;
          }
          $pending_stacked_result['_stage'] = 'team';
          $last_line_type = 'other';
          continue;
        }

        if (
          in_array($pending_stacked_result['_stage'], ['meta_or_team', 'team'], true) &&
          preg_match('/^[A-Z0-9\-]+$/', $line)
        ) {
          $pending_stacked_result['team'] = $line;
          unset($pending_stacked_result['_stage'], $pending_stacked_result['_meta']);
          $current_results[] = $pending_stacked_result;
          $pending_stacked_result = null;
          $last_line_type = 'other';
          continue;
        }
      }

      if (
        !$pending_dual_individual &&
        preg_match('/^([^,]+,\s+.+?)(\*?\d+)\s+([^,]+,\s+.+?)(\*?\d+)\s+(FR|SO|JR|SR|\d+)$/', $line, $m) &&
        !preg_match('/^\(?Event\b/i', $line)
      ) {
        $pending_dual_individual = [
          'first' => [
            'rank' => ltrim($m[2], '*'),
            'name' => trim($m[1]),
            'age' => preg_match('/^\d{1,2}$/', $m[5]) ? (int) $m[5] : null,
            'team' => null,
            'seed_time' => null,
            'result_time' => null,
            'note' => null,
            'points' => null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ],
          'second' => [
            'rank' => ltrim($m[4], '*'),
            'name' => trim($m[3]),
            'age' => preg_match('/^\d{1,2}$/', $m[5]) ? (int) $m[5] : null,
            'team' => null,
            'seed_time' => null,
            'result_time' => null,
            'note' => null,
            'points' => null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ],
          'stage' => 'first_meta_or_team',
        ];
        $pending_team = null;
        $pending_seed_time = null;
        $last_line_type = 'other';
        continue;
      }

      if (
        !$pending_dual_individual &&
        preg_match('/^([^,]+,\s+.+?)(\*?\d+)\s+([^,]+,\s+.+?)(\*?\d+)\s+([A-Z0-9\-]+)$/', $line, $m) &&
        !preg_match('/^\(?Event\b/i', $line)
      ) {
        $pending_dual_individual = [
          'first' => [
            'rank' => ltrim($m[2], '*'),
            'name' => trim($m[1]),
            'age' => null,
            'team' => null,
            'seed_time' => null,
            'result_time' => null,
            'note' => null,
            'points' => null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ],
          'second' => [
            'rank' => ltrim($m[4], '*'),
            'name' => trim($m[3]),
            'age' => null,
            'team' => trim($m[5]),
            'seed_time' => null,
            'result_time' => null,
            'note' => null,
            'points' => null,
            'qualified' => false,
            'relay' => null,
            'round' => $current_round,
          ],
          'stage' => 'first_team',
        ];
        $pending_team = null;
        $pending_seed_time = null;
        $last_line_type = 'other';
        continue;
      }

      if ($pending_dual_individual) {
        if (
          $pending_dual_individual['stage'] === 'first_meta_or_team' &&
          $is_school_year($line)
        ) {
          if (preg_match('/^\d{1,2}$/', $line)) {
            $pending_dual_individual['first']['age'] = (int) $line;
          }
          $pending_dual_individual['stage'] = 'first_team';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_dual_individual['stage'] === 'first_meta_or_team' &&
          preg_match('/^[A-Z0-9\-]+$/', $line)
        ) {
          $pending_dual_individual['first']['team'] = $line;
          $pending_dual_individual['stage'] = 'second_team';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_dual_individual['stage'] === 'first_team' &&
          preg_match('/^[A-Z0-9\-]+$/', $line)
        ) {
          $pending_dual_individual['first']['team'] = $line;
          $pending_dual_individual['stage'] = 'second_team';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_dual_individual['stage'] === 'second_team' &&
          preg_match('/^[A-Z0-9\-]+$/', $line)
        ) {
          $pending_dual_individual['second']['team'] = $line;
          $pending_dual_individual['stage'] = 'first_result';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_dual_individual['stage'] === 'first_result' &&
          preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)
        ) {
          $pending_dual_individual['first']['result_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
          $pending_dual_individual['first']['note'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : null;
          $pending_dual_individual['first']['points'] = $parse_points($m[2] ?? null);
          $pending_dual_individual['stage'] = 'second_result';
          $last_line_type = 'other';
          continue;
        }

        if (
          $pending_dual_individual['stage'] === 'second_result' &&
          preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)
        ) {
          $pending_dual_individual['second']['result_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
          $pending_dual_individual['second']['note'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : null;
          $pending_dual_individual['second']['points'] = $parse_points($m[2] ?? null);
          $current_results[] = $pending_dual_individual['first'];
          $current_results[] = $pending_dual_individual['second'];
          $pending_dual_individual = null;
          $last_line_type = 'other';
          continue;
        }
      }
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^(?:(\*?\d+)\s*)?(.+?)\s+(\d+\.\d{2}|NT)\s*(\d+\.\d{2}|NT)$/', $line, $m)
    ) {
      $parts = $split_stacked_school_and_name(trim($m[2]));
      $rank = $m[1] !== '' ? ltrim($m[1], '*') : (!empty($pending_ranks) ? array_shift($pending_ranks) : null);
      if ($parts && $rank !== null) {
        [$team, $name] = $parts;
        $current_results[] = [
          'rank' => $rank,
          'name' => $name,
          'age' => null,
          'team' => $team,
          'seed_time' => $m[4] === 'NT' ? 'NT' : $m[4],
          'result_time' => $m[3] === 'NT' ? null : $m[3],
          'note' => $m[3] === 'NT' ? 'NT' : null,
          'qualified' => false,
          'relay' => null,
          'round' => $current_round,
        ];
        $last_line_type = 'other';
        continue;
      }
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      !empty($pending_ranks) &&
      preg_match('/^(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2})\s*((?:\d{1,2}:)?\d{1,2}\.\d{2})$/', $line, $m)
    ) {
      $parts = $split_stacked_school_and_name(trim($m[1]));
      if ($parts) {
        $rank = array_shift($pending_ranks);
        [$team, $name] = $parts;
        $current_results[] = [
          'rank' => $rank,
          'name' => $name,
          'age' => null,
          'team' => $team,
          'seed_time' => $m[3],
          'result_time' => $m[2],
          'qualified' => false,
          'relay' => null,
          'round' => $current_round,
        ];
        $last_line_type = 'other';
        continue;
      }
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^([^,]+,\s+.+?)(\*?\d+)\s+(FR|SO|JR|SR|\d+)$/', $line, $m) &&
      !preg_match('/\d+\.\d{2}/', $m[1]) &&
      !preg_match('/^\(?Event\b/i', $line)
    ) {
      $pending_individual = [
        'rank' => ltrim($m[2], '*'),
        'name' => trim($m[1]),
        'age' => preg_match('/^\d{1,2}$/', $m[3]) ? (int) $m[3] : null,
        'qualified' => false,
        'relay' => null,
        '_awaiting_team' => true,
        '_awaiting_result_only' => true,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^([^,]+,\s+.+?)(\*?\d+)\s+([A-Z0-9\-]+)$/', $line, $m) &&
      !preg_match('/\d+\.\d{2}/', $m[1]) &&
      !preg_match('/^\(?Event\b/i', $line)
    ) {
      $pending_individual = [
        'rank' => ltrim($m[2], '*'),
        'name' => trim($m[1]),
        'age' => null,
        'team' => trim($m[3]),
        'qualified' => false,
        'relay' => null,
        '_awaiting_result_only' => true,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^([^,]+,\s+.+?)(\*?\d+)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)$/', $line, $m) &&
      !preg_match('/^\(?Event\b/i', $line)
    ) {
      $pending_individual = [
        'rank' => ltrim($m[2], '*'),
        'name' => trim($m[1]),
        'age' => null,
        'team' => null,
        'seed_time' => null,
        'result_time' => in_array($m[3], ['DQ', 'DFS']) ? null : $m[3],
        'note' => in_array($m[3], ['DQ', 'DFS']) ? $m[3] : null,
        'qualified' => false,
        'relay' => null,
        '_awaiting_team' => true,
        '_awaiting_result_only' => false,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^(.+?)(\*?\d+)\s+(FR|SO|JR|SR|\d+)$/', $line, $m) &&
      !str_contains($line, ',') &&
      !preg_match('/\d+\.\d{2}/', $m[1]) &&
      !preg_match('/^\(?Event\b/i', $line)
    ) {
      $pending_individual = [
        'rank' => ltrim($m[2], '*'),
        'name' => trim($m[1]),
        'age' => preg_match('/^\d{1,2}$/', $m[3]) ? (int) $m[3] : null,
        'qualified' => false,
        'relay' => null,
        '_awaiting_team' => true,
        '_awaiting_result_only' => true,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^(.+?)(\*?\d+)\s+([A-Z0-9\-]+)$/', $line, $m) &&
      !str_contains($line, ',') &&
      !preg_match('/\d+\.\d{2}/', $m[1]) &&
      !preg_match('/^\(?Event\b/i', $line)
    ) {
      $pending_individual = [
        'rank' => ltrim($m[2], '*'),
        'name' => trim($m[1]),
        'age' => null,
        'team' => trim($m[3]),
        'qualified' => false,
        'relay' => null,
        '_awaiting_result_only' => true,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if ($stacked_layout && $pending_individual) {
      if (($pending_individual['_awaiting_team'] ?? false) && $is_school_year($line)) {
        if (preg_match('/^\d{1,2}$/', $line)) {
          $pending_individual['age'] = (int) $line;
        }
        $last_line_type = 'other';
        continue;
      }

      if (
        ($pending_individual['_awaiting_team'] ?? false) &&
        !$is_metric($line) &&
        !str_contains($line, '(') &&
        preg_match('/^[A-Z0-9\-]+$/', $line)
      ) {
        $pending_individual['team'] = $line;
        unset($pending_individual['_awaiting_team']);
        if (
          array_key_exists('result_time', $pending_individual) &&
          $pending_individual['result_time'] !== null &&
          !($pending_individual['_awaiting_result_only'] ?? false)
        ) {
          unset($pending_individual['_awaiting_result_only']);
          $pending_individual['round'] = $current_round;
          $current_results[] = $pending_individual;
          $pending_individual = null;
          $pending_team = null;
          $pending_seed_time = null;
        }
        $last_line_type = 'other';
        continue;
      }

      if (($pending_individual['_awaiting_result_only'] ?? false) && preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)) {
        $pending_individual['result_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
        $pending_individual['note'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : null;
        $pending_individual['points'] = $parse_points($m[2] ?? null);
        unset($pending_individual['_awaiting_result_only'], $pending_individual['_awaiting_team']);
        $pending_individual['round'] = $current_round;
        $current_results[] = $pending_individual;
        $pending_individual = null;
        $pending_team = null;
        $pending_seed_time = null;
        $last_line_type = 'other';
        continue;
      }

      if (!isset($pending_individual['seed_time']) && $is_metric($line)) {
        $pending_individual['seed_time'] = $line;
        $last_line_type = 'other';
        continue;
      }

      if (preg_match('/^((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)) {
        $pending_individual['result_time'] = in_array($m[1], ['DQ', 'DFS']) ? null : $m[1];
        $pending_individual['note'] = in_array($m[1], ['DQ', 'DFS']) ? $m[1] : null;
        $pending_individual['points'] = $parse_points($m[2] ?? null);
        $pending_individual['round'] = $current_round;
        $current_results[] = $pending_individual;
        $pending_individual = null;
        $pending_team = null;
        $pending_seed_time = null;
        $last_line_type = 'other';
        continue;
      }
    }

    if ($stacked_layout && preg_match('/^(.+?)---\s+([^,]+),\s+(.+?)\s+(DQ|DFS)$/', $line, $m)) {
      $current_results[] = [
        'rank' => null,
        'name' => trim($m[2]) . ' ' . trim($m[3]),
        'age' => null,
        'team' => trim($m[1]),
        'seed_time' => $pending_seed_time,
        'result_time' => null,
        'note' => $m[4],
        'qualified' => false,
        'relay' => null,
        'round' => $current_round,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      $pending_team &&
      $pending_seed_time &&
      preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s+(\d+(?:\s*\.\s*\d+)?))$/', $line, $m)
    ) {
      $current_results[] = [
        'rank' => ltrim($m[1], '*'),
        'name' => trim($m[2]) . ' ' . trim($m[3]),
        'age' => null,
        'team' => $pending_team,
        'seed_time' => $pending_seed_time,
        'result_time' => in_array($m[4], ['DQ', 'DFS']) ? null : $m[4],
        'note' => in_array($m[4], ['DQ', 'DFS']) ? $m[4] : null,
        'points' => $parse_points($m[5] ?? null),
        'qualified' => false,
        'relay' => null,
        'round' => $current_round,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      $pending_team &&
      preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|DQ|DFS)(?:\s+(\d+(?:\s*\.\s*\d+)?))?$/', $line, $m)
    ) {
      $current_results[] = [
        'rank' => ltrim($m[1], '*'),
        'name' => trim($m[2]) . ' ' . trim($m[3]),
        'age' => null,
        'team' => $pending_team,
        'seed_time' => null,
        'result_time' => in_array($m[4], ['DQ', 'DFS']) ? null : $m[4],
        'note' => in_array($m[4], ['DQ', 'DFS']) ? $m[4] : null,
        'points' => $parse_points($m[5] ?? null),
        'qualified' => false,
        'relay' => null,
        'round' => $current_round,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      $pending_team &&
      preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+((?:\d{1,2}:)?\d{1,2}\.\d{2}|\d+\.\d{2}|DQ|DFS)(?:\s+(.+))?$/', $line, $m)
    ) {
      $current_results[] = [
        'rank' => ltrim($m[1], '*'),
        'name' => trim($m[2]) . ' ' . trim($m[3]),
        'age' => null,
        'team' => $pending_team,
        'seed_time' => $pending_seed_time,
        'result_time' => in_array($m[4], ['DQ', 'DFS']) ? null : $m[4],
        'note' => in_array($m[4], ['DQ', 'DFS']) ? $m[4] : null,
        'points' => $parse_points($m[5] ?? null),
        'qualified' => false,
        'relay' => null,
        'round' => $current_round,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      preg_match('/^(\*?\d+)\s+([^,]+),\s+(.+?)\s+(.+)$/', $line, $m) &&
      !$is_metric(trim($m[4])) &&
      $parse_points($m[4]) === null
    ) {
      $pending_individual = [
        'rank' => ltrim($m[1], '*'),
        'name' => trim($m[2]) . ' ' . trim($m[3]),
        'age' => null,
        'team' => trim($m[4]),
        'qualified' => false,
        'relay' => null,
      ];
      $pending_team = null;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      !$in_relay &&
      !preg_match('/^\(?Event\s+/i', $line) &&
      !preg_match('/^(?:Name|Team|Prelim|Final|A - Final|B - Final|Scores\b|- Swim-off\b)/i', $line) &&
      !preg_match('/^\*?\d+/', $line) &&
      !preg_match('/^.+?,\s+.+?\*?\d+\s+(?:\d{1,2}:)?\d{1,2}\.\d{2}(?:\s+\d+(?:\.\d+)?)?$/', $line) &&
      !$is_metric($line) &&
      !str_contains($line, '(')
    ) {
      $pending_team = $line;
      $pending_seed_time = null;
      $last_line_type = 'other';
      continue;
    }

    if ($stacked_layout && !$in_relay && $pending_team && $is_metric($line) && $last_line_type !== 'split') {
      $pending_seed_time = $line;
      $last_line_type = 'other';
      continue;
    }

    $result = parse_result_line($line);

    if ($result) {

      if ($current_event && preg_match('/Time Trial/', $current_event['event_name'])) {
        $current_round = 'Time Trials';
      }

      $result['round'] = $current_round;
      $current_results[] = $result;
      $last_line_type = 'other';
      continue;
    }

    if (
      $stacked_layout &&
      $in_relay &&
      $is_metric($line) &&
      !empty($current_results)
    ) {
      $last = $current_results[count($current_results) - 1];
      if (($last['relay'] ?? null) && count($last['splits'] ?? []) >= 4) {
        $pending_seed_time = $line;
        $last_line_type = 'other';
        continue;
      }
    }

    $split = parse_split_line($line);
    if ($split && !empty($current_results)) {
      $last = &$current_results[count($current_results) - 1];
      if (!isset($last['splits'])) $last['splits'] = [];
      $last['splits'] = array_merge($last['splits'], $split);
      $last_line_type = 'split';
      continue;
    }

    $last_line_type = 'other';
  }

  $finalize_pending_relay();

  if ($current_event) {
    $current_event['results'] = $current_results;
    $events[] = $current_event;
  }

  return ['events' => $events];
}
