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
  $stacked_layout = false;
  $pending_team = null;
  $pending_seed_time = null;
  $pending_individual = null;
  $pending_relay = null;
  $last_line_type = 'other';

  $reset_stacked_state = static function () use (&$stacked_layout, &$pending_team, &$pending_seed_time, &$pending_individual, &$pending_relay, &$last_line_type) {
    $stacked_layout = false;
    $pending_team = null;
    $pending_seed_time = null;
    $pending_individual = null;
    $pending_relay = null;
    $last_line_type = 'other';
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
      strcasecmp($line, 'Prelim Time Points') === 0 ||
      strcasecmp($line, 'Seed Time') === 0 ||
      strcasecmp($line, 'Seed Time Points') === 0 ||
      strcasecmp($line, 'Name School Finals Time') === 0 ||
      strcasecmp($line, 'Name School Finals Score Points') === 0
    ) {
      $stacked_layout = true;
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
        'event_name' => trim($m[3]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = false;
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
        'event_name' => trim($m[2]),
        'results' => []
      ];
      $current_results = [];
      $in_relay = false;
      $reset_stacked_state();
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
      $in_relay = false;
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
      $last_line_type = 'other';
      continue;
    }

    if ($stacked_layout && preg_match('/^-\s*Swim-off$/i', $line)) {
      $current_round = 'Swim-Off';
      $last_line_type = 'other';
      continue;
    }

    // Standard "Event 1 Boys 200 Free" format
    if (preg_match('/^\(?Event\s+(\d+)/', $line)) {
      $finalize_pending_relay();
      $info = extract_event_info($line);
      if (!$info) continue;

      if ($current_event && $current_event['event_number'] === $info['event_number']) continue;

      if ($current_event) {
        $current_event['results'] = $current_results;
        $events[] = $current_event;
      }

      $current_event = $info + ['results' => []];
      $current_results = [];
      $in_relay = false;
      $reset_stacked_state();
      continue;
    }

    if (preg_match('/^Team\s+Relay.*Finals\s+Time$/i', $line)) {
      $in_relay = true;
      $last_line_type = 'other';
      continue;
    }

    if (str_starts_with($line, 'Team Relay Seed Time')) {
      $in_relay = true;
      $last_line_type = 'other';
      continue;
    }

    if ($stacked_layout && preg_match('/^\d+\)\s+/', $line)) {
      $finalize_pending_relay();
      $last_line_type = 'swimmer_list';
      continue;
    }

    if ($in_relay && $stacked_layout) {
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

      if ($is_metric($line) && $last_line_type !== 'swimmer_list' && $last_line_type !== 'split') {
        $pending_seed_time = $line;
        $last_line_type = 'other';
        continue;
      }

      $relay = parse_relay_line($line);
      if ($relay) {
        $relay['round'] = $current_round;
        $current_results[] = $relay;
        $last_line_type = 'other';
        continue;
      }
    }

    if ($stacked_layout && $pending_individual) {
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
