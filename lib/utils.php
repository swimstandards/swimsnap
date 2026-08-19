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
    foreach (['!n/j/Y', '!Y-m-d'] as $format) {
        $dt = DateTime::createFromFormat($format, $date);
        $errors = DateTime::getLastErrors();
        if ($dt && ($errors === false || (!$errors['warning_count'] && !$errors['error_count']))) {
            return $dt->format('Y-m-d');
        }
    }

    return null;
}

function normalize_hytek_text(string $content): string
{
    // PDF text extraction sometimes substitutes the Greek beta symbol for "f".
    return str_replace('ϐ', 'f', $content);
}

function parse_hytek_meet_title(string $line): ?array
{
    // Some PDF copy operations append a visible backslash at each visual line end.
    $line = rtrim($line, " \t\n\r\0\x0B\\");
    $date_pattern = '(?:\d{1,2}/\d{1,2}/\d{4}|\d{4}-\d{2}-\d{2})';

    if (preg_match('~^(.+?)\s*-\s*(' . $date_pattern . ')\s+to\s+(' . $date_pattern . ')$~i', $line, $matches)) {
        return [
            'meet_name' => trim($matches[1]),
            'meet_start_date' => format_date_to_iso($matches[2]),
            'meet_end_date' => format_date_to_iso($matches[3]),
        ];
    }

    if (preg_match('~^(.+?)\s*-\s*(' . $date_pattern . ')$~', $line, $matches)) {
        $date = format_date_to_iso($matches[2]);
        return [
            'meet_name' => trim($matches[1]),
            'meet_start_date' => $date,
            'meet_end_date' => $date,
        ];
    }

    return null;
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

function normalize_event_name(string $event_name): string
{
    $event_name = trim(preg_replace('/\s+/', ' ', $event_name));

    $normalizations = [
        '/\b500\s+Yard\s+Freestyle\s+Relay\s+200-150-100-50\b/i' => '500 Yard Freestyle Relay',
    ];

    foreach ($normalizations as $pattern => $replacement) {
        $event_name = preg_replace($pattern, $replacement, $event_name);
    }

    return trim($event_name);
}

function extract_event_info(string $line): ?array
{
    // Normalize whitespace
    $line = trim(preg_replace('/\s+/', ' ', $line));

    // Remove surrounding parentheses if present
    $line = trim($line, "()");

    $normalize_event_number = static function (string $value) {
        return ctype_digit($value) ? (int)$value : $value;
    };

    // Match combined relay headers such as:
    // "Event 9 / 10 Women / Men 200 Medley Relay"
    if (preg_match('/^Event\s+(\d+[A-Za-z]?\s*\/\s*\d+[A-Za-z]?)\s+((?:Boys|Girls|Men|Women)\s*\/\s*(?:Boys|Girls|Men|Women))\s+(.+)/i', $line, $matches)) {
        return [
            'event_number' => preg_replace('/\s*\/\s*/', ' / ', $matches[1]),
            'event_name' => normalize_event_name($matches[3]),
            'gender' => preg_replace('/\s*\/\s*/', ' / ', $matches[2])
        ];
    }

    // Match "Event 1 Women 100 Free"
    if (preg_match('/^Event\s+(\d+[A-Za-z]?)\s+(Boys|Girls|Men|Women|Mixed)\s+(.+)/i', $line, $matches)) {
        return [
            'event_number' => $normalize_event_number($matches[1]),
            'event_name' => normalize_event_name($matches[3]),
            'gender' => $matches[2]
        ];
    }

    // Match "#1 Girls 50 Free"
    if (preg_match('/^#(\d+[A-Za-z]?)\s+(Boys|Girls|Men|Women|Mixed)\s+(.+)/i', $line, $matches)) {
        return [
            'event_number' => $normalize_event_number($matches[1]),
            'event_name' => normalize_event_name($matches[3]),
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

function format_metadata_datetime($value, string $format = 'M j, Y'): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->setTimezone(new DateTimeZone('America/New_York'))->format($format);
    }

    if (is_object($value) && method_exists($value, 'toDateTime')) {
        return $value->toDateTime()->setTimezone(new DateTimeZone('America/New_York'))->format($format);
    }

    return is_string($value) ? smartFormatDate($value, $format) : '';
}
