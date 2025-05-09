<?php

/**
 * Heat Sheets (Meet Program) Parser for SwimSnap
 * Parses HY-TEK Meet Program text into structured JSON.
 * Updated: 2025-05-06
 *
 */


function parse_swimmer_line($line)
{
    // Try standard swimmer line format
    $parsed = parse_swimmer_standard($line);
    if ($parsed) return $parsed;

    // Try swimmer line with gender+age combo
    return parse_swimmer_gender_age($line);
}

function parse_swimmer_standard($line)
{
    if (preg_match('/^(\d+)\s+(.+?)\s+(\d{1,2})\s+([A-Z]+-[A-Z]+)(?:\s+((?:[A-Z]{0,4})?[\d:.]+[A-Z]{0,2}|NT))?$/', $line, $m)) {
        return [
            "lane" => intval($m[1]),
            "name" => trim($m[2]),
            "age" => intval($m[3]),
            "team" => trim($m[4]),
            "seed_time" => isset($m[5]) ? $m[5] : null
        ];
    }
    return null;
}

function parse_swimmer_gender_age($line)
{
    if (preg_match('/^(\d+)\s+(.+?)\s+([A-Z]{1,3}\d{1,2})\s+([A-Z]+-[A-Z]+)(?:\s+((?:[A-Z]{0,4})?[\d:.]+[A-Z]{0,2}|NT))?$/', $line, $m)) {
        if (preg_match('/(\d{1,2})$/', $m[3], $a)) {
            return [
                "lane" => intval($m[1]),
                "name" => trim($m[2]),
                "age" => intval($a[1]),
                "team" => trim($m[4]),
                "seed_time" => isset($m[5]) ? $m[5] : null
            ];
        }
    }
    return null;
}

function parse_team_line($line)
{
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

        // Event start
        if (preg_match('/^(Event\s+\d+|#\d+)\s+(Boys|Girls|Men|Women|Mixed)\b/', $line)) {
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
                $current_heat['swimmers'][] = $swimmer;
                continue;
            }

            $team = parse_team_line($line);
            if ($team) {
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
