<?php

function pr_pre($data): void
{
    echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
}

function load_meta_json()
{
    $meta_path = META_DIR . 'meta.json';
    if (file_exists($meta_path)) {
        return json_decode(file_get_contents($meta_path), true) ?: [];
    }
    return [];
}

function save_meta_json($all_meta)
{
    $meta_path = META_DIR . 'meta.json';
    file_put_contents($meta_path, json_encode($all_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}


function format_date_to_iso($date)
{
    $dt = DateTime::createFromFormat('n/j/Y', $date);
    return $dt ? $dt->format('Y-m-d') : null;
}

function slugify(string $text): string
{
    // Normalize whitespace and remove "QT"
    $text = preg_replace('/\s+/', ' ', trim($text));
    $text = preg_replace('/\bQT\b/i', '', $text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    return strtolower(trim($text, '-'));
}

function shorten_title(string $full_title, int $max_words = 5): string
{
    $parts = preg_split('/\s+/', $full_title);
    return count($parts) > $max_words
        ? implode(' ', array_slice($parts, 0, $max_words)) . '…'
        : $full_title;
}

function extract_event_info(string $line): ?array
{
    // Normalize whitespace
    $line = trim(preg_replace('/\s+/', ' ', $line));

    // Remove surrounding parentheses if present
    $line = trim($line, "()");

    // Match "Event 1 Women 100 Free"
    if (preg_match('/^Event\s+(\d+)\s+(Boys|Girls|Men|Women|Mixed)\s+(.+)/i', $line, $matches)) {
        return [
            'event_number' => (int)$matches[1],
            'event_name' => trim($matches[3]),
            'gender' => $matches[2]
        ];
    }

    // Match "#1 Girls 50 Free"
    if (preg_match('/^#(\d+)\s+(Boys|Girls|Men|Women|Mixed)\s+(.+)/i', $line, $matches)) {
        return [
            'event_number' => (int)$matches[1],
            'event_name' => trim($matches[3]),
            'gender' => $matches[2]
        ];
    }

    return null;
}

function get_build_version(): string
{
    $version_file = __DIR__ . '/../version.php';

    if (file_exists($version_file)) {
        include $version_file;
        return $build_version ?? 'unknown';
    }

    if (is_dir(__DIR__ . '/../.git')) {
        $branch = shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null');
        if (is_string($branch) && trim($branch)) {
            return 'branch:' . trim($branch);
        }
    }

    return 'dev';
}

function sort_strokes_by_standard_order(array $strokes): array
{
    $stroke_order = ['Free', 'Back', 'Breast', 'Fly', 'IM', 'Free Relay', 'Medley Relay'];

    usort($strokes, function ($a, $b) use ($stroke_order) {
        $indexA = array_search($a, $stroke_order);
        $indexB = array_search($b, $stroke_order);
        return ($indexA === false ? 999 : $indexA) - ($indexB === false ? 999 : $indexB);
    });

    return $strokes;
}

function smartFormatDate($input, $format = 'M j, Y', $timezone = 'America/New_York')
{
    if (empty($input)) return 'N/A';

    $input = trim($input);

    try {
        $tz = new DateTimeZone($timezone);

        // Millisecond timestamp
        if (preg_match('/^\d{13}$/', $input)) {
            $timestampInSeconds = (int)($input / 1000);
            $dt = new DateTime('@' . $timestampInSeconds);
            $dt->setTimezone($tz);
        }
        // 10-digit Unix timestamp
        elseif (preg_match('/^\d{10}$/', $input)) {
            $dt = new DateTime('@' . $input);
            $dt->setTimezone($tz);
        }
        // Date-only string (like 5/15/2025)
        elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $input)) {
            // Append time to force interpretation as local
            $dt = DateTime::createFromFormat('n/j/Y H:i:s', $input . ' 00:00:00', $tz);
        }
        // Other formats (ISO, etc.)
        elseif (strtotime($input) !== false) {
            $dt = new DateTime($input, $tz);
        } else {
            return 'N/A';
        }

        return $dt->format($format);
    } catch (Exception $e) {
        return 'N/A';
    }
}
