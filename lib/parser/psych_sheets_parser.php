<?php

/**
 * Psych Sheet Parser for SwimSnap
 * Parses HY-TEK Psych Sheet into structured JSON.
 * Updated: 2025-05-06 (restored flexible parsing logic)
 */

/**
 * Parses relay seed lines that may contain leading or trailing notes.
 *
 * Examples this handles:
 *   1 _____ SwimRVA-VA A 3:52.45
 *   2 B_____ Metro Area Life-NJ A 3:45.83
 *   3 SwimRVA-VA A 3:52.45 JRNW
 *   4 _____ Sunrise Swim Club-NE A 3:45.62 JRNW
 */
function parse_relay_line_with_note($line)
{
    // Normalize non-standard underscore/dash characters
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Case 1: Relay with leading note (e.g., 1 _____ Team Name A 3:52.45 JRNW)
    if (preg_match('/^(\d+)\s+([A-Z_]{3,})\s+(.+?)\s+([A-Z])\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[2]);
        if ($note === '_____') $note = '';
        if (isset($m[6]) && preg_match('/[A-Z]/', $m[6])) {
            $note = $m[6]; // Prefer trailing note if valid
        }

        return [
            "rank" => $m[1],
            "team" => trim($m[3]),
            "relay" => $m[4],
            "seed_time" => $m[5] . ($note ? " ($note)" : "")
        ];
    }

    // Case 2: Relay with no leading note (e.g., 2 Rockville Montgo-PV A 3:55.67 JRNW)
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z])\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[5] ?? '');
        if ($note === '_____') $note = '';

        return [
            "rank" => $m[1],
            "team" => trim($m[2]),
            "relay" => $m[3],
            "seed_time" => $m[4] . ($note ? " ($note)" : "")
        ];
    }

    return null;
}

/**
 * Parses individual swimmer seed lines with optional notes (before or after time).
 *
 * Examples this handles:
 *   1 _____ Joelle Van Duzer 14 Greater Holyoke-NE 17:31.60
 *   2 B_____ Stella Somohano 12 Lakeland Hills-NJ 5:41.07 _
 *   3 FUTM Guettler, Chris M15 SPA 52.20
 *   4 14 Radakovic, Raj M15 SPA 56.99 JRNW
 */
function parse_swimmer_line_with_note($line)
{
    // Normalize weird underscores and dashes
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Case 1: With code and gender (FUTM Guettler, Chris M15 ...)
    if (preg_match('/^(\d+)\s+([A-Z_\d]{3,})\s+(.+?)([MWF])(\d{1,2})\s+([A-Z\-]+)\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[8] ?? '');
        if ($note === '_____') $note = '';

        return [
            "rank" => $m[1],
            "name" => trim($m[3]),
            "age" => (int)$m[5],
            "team" => trim($m[6]),
            "seed_time" => $m[7] . ($note ? " ($note)" : "")
        ];
    }

    // Case 2: No code, but gender stuck (Monkelis, ArnaM18 ...)
    else if (preg_match('/^(\d+)\s+(.+?)([MFW])(\d{1,2})\s+([A-Z\-]+)\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[7] ?? '');
        if ($note === '_____') $note = '';

        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "age" => (int)$m[4],
            "team" => trim($m[5]),
            "seed_time" => $m[6] . ($note ? " ($note)" : "")
        ];
    }

    // Case 3: With code, no gender (FUTM Kim Castagna 12 SPA 55.10)
    else if (preg_match('/^(\d+)\s+([A-Z_\d]{3,})\s+(.+?)\s+(\d{1,2})\s+([A-Z\-]+)\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[7] ?? '');
        if ($note === '_____') $note = '';

        return [
            "rank" => $m[1],
            "name" => trim($m[3]),
            "age" => (int)$m[4],
            "team" => trim($m[5]),
            "seed_time" => $m[6] . ($note ? " ($note)" : "")
        ];
    }

    // Case 4: No code, no gender (_____ Kim Castagna 12 Metro Area Life-NJ ...)
    else if (preg_match('/^(\d+)\s+_____+\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([\d:.]+)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $note = trim($m[6] ?? '');
        if ($note === '_____') $note = '';

        return [
            "rank" => $m[1],
            "name" => trim($m[2]),
            "age" => (int)$m[3],
            "team" => trim($m[4]),
            "seed_time" => $m[5] . ($note ? " ($note)" : "")
        ];
    }

    return null;
}

/**
 * Matches relay seed line using:
 *
 *     /^
 *         (\d+)                      # (1) Rank
 *         \s+(.+?)                   # (2) Team name (non-greedy, can include spaces)
 *         \s+([A-Z])                 # (3) Relay letter (A/B/etc.)
 *         \s+(NT|(?:\d{1,2}:)?\d{1,2}\.\d{2}[YLS]?)  # (4) Time or NT with optional course suffix
 *         (?:\s+(.*))?               # (5) Optional note (e.g., *)
 *     $/x
 *
 * Captures rank, team, relay letter, time, and optional note.
 * e.g.:
 * 12 Occoquan Swimmin A 4:41.05
 * 13 Arlington Aquati B 3:58.40Y
 * 
 */

function parse_relay_seed($line)
{
    $relayPattern = '/^(\d+)\s+(.+?)\s+([A-Z])\s+(NT|(?:\d{1,2}:)?\d{1,2}\.\d{2}[YLS]?)(?:\s+(.*))?$/';
    if (preg_match($relayPattern, $line, $m)) {
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

/**
 * Regex pattern to match psych sheet swimmer lines with full team names.
 *
 * Matches lines like:
 *   1 Vanyo, Sofie 16 Atlantic Coast A 2:18.33
 *   1 Young, Sara 12 Rockville Montgo-PV 53.35
 *
 * Explanation:
 *   (1) (\d+)                      → Rank number at line start
 *   (2) ([^,]+,\s+.+?)             → Full swimmer name (Last, First ...)
 *   (3) (\d{1,2})                  → Age
 *   (4) (.+?)                      → Team name (may or may not include -LSC)
 *   (5) ((?:NT|(?:\d{1,2}:)?\d{1,2}\.\d{2})(?:[YLS]?)) → Seed time or "NT", optional YLS
 *   (6) (?:\s+(.*))?               → Optional trailing note (e.g., "*", "B")
 */
function parse_swimmer_full_team($line)
{
    $pattern = '/^(\d+)\s+([^,]+,\s+.+?)\s+(\d{1,2})\s+(.+?)\s+((?:NT|(?:\d{1,2}:)?\d{1,2}\.\d{2})(?:[YLS]?))(?:\s+(.*))?$/';

    if (preg_match($pattern, $line, $m)) {
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
        $line = preg_replace('/\s+/', ' ', $line);

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
        } elseif (
            stripos($line, "Name Age Team Seed Time") !== false || stripos($line, "Name Seed Age Team Time") !== false
        ) {
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
