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
