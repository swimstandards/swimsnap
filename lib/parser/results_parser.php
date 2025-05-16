<?php

/**
 * Meet Results Parser for SwimSnap
 * Parses HY-TEK Results text into structured JSON.
 * Updated: 2025-04-30
 */

require_once __DIR__ . '/../utils.php';

function parse_result_line($line)
{

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

  return null;
}

function parse_relay_line($line)
{
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

  foreach ($lines as $line) {
    $line = trim($line);

    if (empty($line) || str_contains($line, 'HY-TEK') || str_starts_with($line, 'Sanction #:') || str_starts_with($line, 'Page')) {
      continue;
    }

    if (strcasecmp(trim($line), 'Results') === 0) {
      continue;
    }

    // 🔥 Support simple event headers like "#101 Girls 11-12 100 Free"
    if (preg_match('/^#?(\d+)\s+([MF]ixed|Girls|Boys)\s+(.*)$/i', $line, $m)) {
      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = [
        'event_number' => (int)$m[1],
        'gender' => $m[2],
        'event_name' => trim($m[3]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = false;
      continue;
    }

    // Fallback event header: "Girls 8 & Under 100 LC Meter Freestyle"
    if (preg_match('/^(Girls|Boys|Mixed)\s+(\d.*)$/i', $line, $m)) {
      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = [
        'event_number' => null,
        'gender' => ucfirst(strtolower($m[1])),
        'event_name' => trim($m[2]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = false;
      continue;
    }

    // Additional fallback: "Women/Men 50 LC Meter Freestyle"
    if (preg_match('/^(Women|Men)\s+(\d.*)$/i', $line, $m)) {
      $gender = ucfirst(strtolower($m[1]));
      $event_name = trim($m[2]);

      // 🔁 Skip if same event (e.g. repeated due to page break or round split)
      if ($current_event && $current_event['gender'] === $gender && $current_event['event_name'] === $event_name) {
        continue;
      }

      // ✅ Otherwise treat as new event
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
      $in_relay = false;
      continue;
    }

    // Finals, Prelims round indicator
    if (preg_match('/^(Prelims|Preliminaries|Finals|Swim[- ]?Off|Time Trials?)/i', $line, $m)) {
      $current_round = $m[1];
      continue;
    }
    // Add support for rounds like "A - Final", "B - Final", etc.
    if (preg_match('/^([A-Z])\s*-\s*Final/i', $line, $m)) {
      $current_round = $m[1] . ' Final';
      continue;
    }

    // Standard "Event 1 Boys 200 Free" format
    if (preg_match('/^\(?Event\s+(\d+)/', $line)) {
      $info = extract_event_info($line);
      if ($current_event && $current_event['event_number'] === $info['event_number']) continue;

      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = $info + ['results' => []];
      $current_results = [];
      $in_relay = false;
      continue;
    }

    if (preg_match('/^Team\s+Relay.*Finals\s+Time$/i', $line)) {
      $in_relay = true;
      continue;
    }

    if (str_starts_with($line, 'Team Relay Seed Time')) {
      $in_relay = true;
      continue;
    }

    if ($in_relay) {
      $relay = parse_relay_line($line);
      if ($relay) {
        $relay['round'] = $current_round;
        $current_results[] = $relay;
        continue;
      }
    }

    $result = parse_result_line($line);

    if ($result) {
      $result['round'] = $current_round;
      $current_results[] = $result;
      continue;
    }

    $split = parse_split_line($line);
    if ($split && !empty($current_results)) {
      $last = &$current_results[count($current_results) - 1];
      if (!isset($last['splits'])) $last['splits'] = [];
      $last['splits'] = array_merge($last['splits'], $split);
    }
  }

  if ($current_event) {
    $current_event['results'] = $current_results;
    $events[] = $current_event;
  }

  return ['events' => $events];
}
