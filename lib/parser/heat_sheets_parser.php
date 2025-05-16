<?php

/**
 * Heat Sheets (Meet Program) Parser for SwimSnap
 * Parses HY-TEK Meet Program text into structured JSON.
 * Updated: 2025-05-06
 *
 */

/**
 * Fallback swimmer parser for Crystal Reports PDFs
 * Format: mm8heatsheet2colNoTstd.rpt (e.g., B____ after time or leading _____)
 */
function parse_swimmer_line_fallback($line)
{
    // Normalize non-standard characters
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Matches: 1 _____ McKenna Kay 11 Greater Somerset-NJ 28.91
    if (preg_match('/^(\d+)\s+_+\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([\d:.]+)(?:\s+([A-Z_]+))?$/', $line, $m)) {
        $note = trim($m[6] ?? '');
        if ($note === '_____') $note = '';
        return [
            "lane" => intval($m[1]),
            "name" => trim($m[2]),
            "age" => intval($m[3]),
            "team" => trim($m[4]),
            "seed_time" => $m[5] . ($note ? " ($note)" : "")
        ];
    }

    // Matches: 1 Sloane O'Beirne 11 New Jersey Race-NJ 31.23 B____
    if (preg_match('/^(\d+)\s+(.+?)\s+(\d{1,2})\s+(.+?)\s+([\d:.]+)\s+([A-Z]+_+)$/', $line, $m)) {
        $note = trim($m[6] ?? '');
        if ($note === '_____') $note = '';
        return [
            "lane" => intval($m[1]),
            "name" => trim($m[2]),
            "age" => intval($m[3]),
            "team" => trim($m[4]),
            "seed_time" => $m[5] . ($note ? " ($note)" : "")
        ];
    }

    return null;
}

/**
 * Fallback swimmer parser for Crystal Reports PDFs
 * Format: mm8heatsheet2colNoTstd.rpt (e.g., B____ after time or leading _____)
 */
function parse_team_line_fallback($line)
{
    $line = preg_replace('/[‐‑‒–—―﹘﹣＿]/u', '_', $line);

    // Case: 1 _____ Team Name A 3:52.45 B____
    if (preg_match('/^(\d+)\s+_+\s+(.+?)\s+([A-Z])\s+([\d:.]+)(?:\s+([A-Z_]+))?$/', $line, $m)) {
        $note = trim($m[5] ?? '');
        if ($note === '_____') $note = '';
        return [
            "lane" => intval($m[1]),
            "team_name" => trim($m[2]),
            "relay_team" => $m[3],
            "seed_time" => $m[4] . ($note ? " ($note)" : "")
        ];
    }

    // Case: 1 Team Name A 3:52.45 B____
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z])\s+([\d:.]+)\s+([A-Z_]+)$/', $line, $m)) {
        $note = trim($m[5] ?? '');
        if ($note === '_____') $note = '';
        return [
            "lane" => intval($m[1]),
            "team_name" => trim($m[2]),
            "relay_team" => $m[3],
            "seed_time" => $m[4] . ($note ? " ($note)" : "")
        ];
    }

    return null;
}


function parse_swimmer_standard($line)
{
    if (preg_match('/^(\d+)\s+(.+?)\s+(\d{1,2})\s+([A-Z0-9\-]+)\s+((?:[A-Z]{0,4})?[\d:.]+[A-Z]{0,2}|NT)(?:\s+([A-Z]+))?$/', $line, $m)) {
        $seed_time = $m[5];
        if (isset($m[6])) {
            $seed_time .= ' (' . $m[6] . ')';
        }
        return [
            "lane" => intval($m[1]),
            "name" => trim($m[2]),
            "age" => intval($m[3]),
            "team" => trim($m[4]),
            "seed_time" => $seed_time
        ];
    }
    return null;
}

function parse_swimmer_gender_age($line)
{
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z]{1,3}\d{1,2})\s+([A-Z]+-[A-Z]+)\s+((?:[A-Z]{0,4})?[\d:.]+[A-Z]{0,2}|NT)(?:\s+([A-Z]+))?$/', $line, $m)) {
        if (preg_match('/(\d{1,2})$/', $m[3], $a)) {
            $seed_time = $m[5];
            if (isset($m[6])) {
                $seed_time .= ' (' . $m[6] . ')';
            }
            return [
                "lane" => intval($m[1]),
                "name" => trim($m[2]),
                "age" => intval($a[1]),
                "team" => trim($m[4]),
                "seed_time" => $seed_time
            ];
        }
    }
    return null;
}


function parse_team_line_standard($line)
{
    /**
     * Standard relay team line
     * Format: lane team_name relay_letter seed_time
     * Example: 3 SwimRVA-VA A 3:52.45
     */
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z])\s+([\d:.]+)/', $line, $matches)) {
        return [
            "lane" => intval($matches[1]),
            "team_name" => trim($matches[2]),
            "relay_team" => trim($matches[3]),
            "seed_time" => trim($matches[4])
        ];
    }
    return null;
}


function parse_swimmer_line($line)
{
    // Try standard swimmer line format
    $parsed = parse_swimmer_standard($line);
    if ($parsed) return $parsed;

    // Try swimmer line with gender+age combo
    $parsed = parse_swimmer_gender_age($line);
    if ($parsed) return $parsed;

    // Try fallback for Crystal Reports-style PDF (e.g., mm8heatsheet2colNoTstd.rpt)
    return parse_swimmer_line_fallback($line);
}


function parse_team_line($line)
{
    // Try standard swimmer line format
    $parsed = parse_team_line_standard($line);
    if ($parsed) return $parsed;

    // Try fallback for Crystal Reports-style PDF (e.g., mm8heatsheet2colNoTstd.rpt)
    return parse_team_line_fallback($line);
}


function parse_heat_header($line)
{
    if (preg_match('/^Heat (\d+)/', $line, $matches)) {
        return [
            "heat_number" => intval($matches[1]),
            "swimmers" => [],
            "teams" => []
        ];
    }
    return null;
}

function process_heat_sheet($content)
{
    // Normalize all line endings
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $lines = explode("\n", $content);

    $events = [];
    $current_event = null;
    $current_heat = null;

    $in_alternates = false; // 🔁 Alternate block flag
    $alt_counter = 1;       // 🔁 Alternate numbering

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip common headers
        if (
            $line === '' ||
            str_contains($line, "HY-TEK") ||
            str_contains($line, "Meet Program") ||
            str_contains($line, "Sanction")
        ) {
            continue;
        }

        // Detect start of Alternates section
        if (stripos($line, 'Alternates') === 0) {
            $in_alternates = true;
            $alt_counter = 1;
            continue;
        }

        // Event start
        if (preg_match('/^(Event\s+\d+|#\d+)\s+(Boys|Girls|Men|Women|Mixed)\b/', $line)) {
            $in_alternates = false; // 🔁 Reset alternates on new event
            if ($current_event) {
                if ($current_heat && (!empty($current_heat['swimmers']) || !empty($current_heat['teams']))) {
                    $current_event['heats'][] = $current_heat;
                }
                $events[] = $current_event;
            }
            $current_event = extract_event_info($line);
            $current_event['heats'] = [];
            $current_heat = null;
            continue;
        }

        // Heat header
        if (preg_match('/^Heat \d+/', $line)) {
            $in_alternates = false; // 🔁 Reset alternates on new heat
            if ($current_event && $current_heat && (!empty($current_heat['swimmers']) || !empty($current_heat['teams']))) {
                $current_event['heats'][] = $current_heat;
            }
            $current_heat = parse_heat_header($line);
            continue;
        }

        // Swimmer or team line
        if ($current_event && $current_heat) {
            $swimmer = parse_swimmer_line($line);
            if ($swimmer) {
                if ($in_alternates) {
                    $swimmer['lane'] = 'Alt. ' . $alt_counter++; // 👈 append Alt. to lane
                }
                $current_heat['swimmers'][] = $swimmer;
                continue;
            }

            $team = parse_team_line($line);
            if ($team) {
                if ($in_alternates) {
                    $team['lane'] = 'Alt. ' . $alt_counter++; // 👈 append Alt. to lane
                }
                $current_heat['teams'][] = $team;
                continue;
            }
        }
    }

    // Final heat and event flush
    if ($current_event) {
        if ($current_heat && (!empty($current_heat['swimmers']) || !empty($current_heat['teams']))) {
            $current_event['heats'][] = $current_heat;
        }
        $events[] = $current_event;
    }

    return ["events" => $events];
}
