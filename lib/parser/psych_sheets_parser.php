<?php

/**
 * Psych Sheet Parser for SwimSnap
 * Parses HY-TEK Psych Sheet into structured JSON.
 * Updated: 2025-05-06 (restored flexible parsing logic)
 */

function parse_relay_line_with_note($line)
{
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Match with optional single-letter note
    // Example: 2 B_____ Metro Area Life-NJ A 3:45.83
    if (preg_match('/^(\d+)\s+([A-Z])_+\s+(.+?)\s+([A-Z])\s+([\d:.]+)$/', $line, $m)) {
        return [
            "rank" => $m[1],
            "team" => trim($m[3]),
            "relay" => $m[4],
            "seed_time" => $m[5] . "(" . $m[2] . ")"
        ];
    }

    // No note, just underscores
    if (preg_match('/^(\d+)\s+_+\s+(.+?)\s+([A-Z])\s+([\d:.]+)$/', $line, $m)) {
        return [
            "rank" => $m[1],
            "team" => trim($m[2]),
            "relay" => $m[3],
            "seed_time" => $m[4]
        ];
    }

    return null;
}

function parse_swimmer_line_with_note($line)
{
    // Normalize weird dashes/underscores
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Case: rank + B_____ prefix, ignore trailing underscore
    if (preg_match('/^(\d+)\s+([A-Z])_+\s+(.+?)\s+(\d{1,2})\s+(.+?-[A-Z]{2})\s+([\d:.]+)(?:\s+_+)?$/', $line, $m)) {
        return [
            "rank" => $m[1],
            "name" => trim($m[3]),
            "age" => (int)$m[4],
            "team" => trim($m[5]),
            "seed_time" => $m[6] . "(" . $m[2] . ")"
        ];
    }

    // Case: rank + only underscores, ignore trailing underscore
    if (preg_match('/^(\d+)\s+_+\s+(.+?)\s+(\d{1,2})\s+(.+?-[A-Z]{2})\s+([\d:.]+)(?:\s+_+)?$/', $line, $m)) {
        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "age" => (int)$m[3],
            "team" => trim($m[4]),
            "seed_time" => $m[5]
        ];
    }

    return null;
}

function parse_relay_seed($line)
{
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z])\s+([\d:.]+)(?:\s+(.*))?$/', $line, $m)) {
        $seed_time = $m[4];
        // empty of not letter, then skip note
        $note = (isset($m[5]) && preg_match('/[A-Za-z]/', $m[5])) ? trim($m[5]) : "";
        if ($note) {
            $seed_time .= "(" . $note . ")";
        }
        return [
            "rank" => $m[1],
            "team" => trim($m[2]),
            "relay" => $m[3],
            "seed_time" => $seed_time
        ];
    }
    return null;
}

function parse_swimmer_full_team($line)
{
    // e.g. 1 Young, Sara 12 Rockville Montgo-PV 53.35
    if (preg_match('/^(\d+)\s+([^,]+,\s+.+?)\s+(\d{1,2})\s+(.+?-[A-Z]{2})\s+((?:NT|(?:\d{1,2}:)?\d{1,2}\.\d{2})(?:[YLS]?))(?:\s+(.*))?$/', $line, $m)) {
        $seed_time = $m[5];
        $note = isset($m[6]) ? trim($m[6]) : "";
        if ($note) {
            $seed_time .= "(" . $note . ")";
        }
        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "age" => (int)$m[3],
            "team" => $m[4],
            "seed_time" => $seed_time
        ];
    }
    return null;
}

function parse_swimmer_abbr_team($line)
{
    // e.g. 6 Herrera, Sienna 12 LCAC-GA 54.57
    if (preg_match('/^(\d+)\s+([^,]+,\s+.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+((?:NT|(?:\d{1,2}:)?\d{1,2}\.\d{2})(?:[YLS]?))(?:\s+(.*))?$/', $line, $m)) {
        $seed_time = $m[5];
        $note = isset($m[6]) ? trim($m[6]) : "";
        if ($note) {
            $seed_time .= "(" . $note . ")";
        }
        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "age" => (int)$m[3],
            "team" => $m[4],
            "seed_time" => $seed_time
        ];
    }
    return null;
}

function parse_swimmer_gender_age($line)
{
    // e.g. 1 Cahill, Theo A M10 YORK-PV 2:14.23Y _____
    // e.g. 1 Bartlett, Rio A W10 NCAPB-PV NTY
    if (preg_match('/^(\d+)\s+([^,]+,\s+.+?)\s+([MW]\d{1,2})\s+([A-Z0-9\-]+)\s+((?:NT|(?:\d{1,2}:)?\d{1,2}\.\d{2})(?:[YLS]?))(?:\s+(.*))?$/', $line, $m)) {
        $seed_time = $m[5];
        $note = isset($m[6]) ? trim($m[6]) : "";
        if ($note) {
            $seed_time .= "(" . $note . ")";
        }
        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "gender" => $m[3][0] === 'M' ? 'Male' : 'Female',
            "age" => (int)substr($m[3], 1),
            "team" => $m[4],
            "seed_time" => $seed_time
        ];
    }
    return null;
}

function parse_swimmer_line($line)
{
    if ($parsed = parse_swimmer_line_with_note($line)) return $parsed;
    if ($parsed = parse_swimmer_gender_age($line)) return $parsed;
    if ($parsed = parse_swimmer_full_team($line)) return $parsed;
    if ($parsed = parse_swimmer_abbr_team($line)) return $parsed;

    return null;
}

function process_psych_sheet($content)
{
    $lines = explode("\n", $content);
    $events = [];
    $current_event = null;
    $relay_seed_mode = false;
    $individual_seed_mode = false;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || stripos($line, "HY-TEK") !== false || str_starts_with($line, "Sanction #:")) {
            continue;
        }

        // Event header
        // if (preg_match('/(Event \d+|#\d+)\s+(Boys|Girls|Women|Men|Mixed)/i', $line)) {
        if (preg_match('/^(?:Event\s+|#)(\d+)\s+(Boys|Girls|Women|Men|Mixed)\s+(.*)$/i', $line, $m)) {
            if ($current_event !== null) {
                $events[] = $current_event;
            }
            $current_event = extract_event_info($line);
            $relay_seed_mode = false;
            $individual_seed_mode = false;
        } elseif (stripos($line, "Team Relay Seed Time") !== false) {
            $relay_seed_mode = true;
            $individual_seed_mode = false;
        } elseif (stripos($line, "Name Age Team Seed Time") !== false || stripos($line, "Name Seed Age Team Time") !== false) {
            $individual_seed_mode = true;
            $relay_seed_mode = false;
        } elseif ($relay_seed_mode && $current_event) {
            $seed = parse_relay_line_with_note($line); // fallback
            if (!$seed) {
                $seed = parse_relay_seed($line);
            }
            if ($seed) {
                $current_event["seeds"][] = $seed;
            }
        } elseif ($individual_seed_mode && $current_event) {
            $seed = parse_swimmer_line($line);
            if ($seed) {
                $current_event["seeds"][] = $seed;
            }
        }
    }

    if ($current_event !== null) {
        $events[] = $current_event;
    }

    // pr_pre($events);

    return ["events" => $events];
}
