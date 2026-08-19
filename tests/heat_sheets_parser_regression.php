<?php

require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/parser/heat_sheets_parser.php';

function assert_heat_sheet_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL .
            'Expected: ' . var_export($expected, true) . PHP_EOL .
            'Actual:   ' . var_export($actual, true)
        );
    }
}

$heat_sheet = <<<'TEXT'
UBC Aquatic Centre - Site License HY-TEK's MEET MANAGER 8.0 - 2:26 PM 2026-08-16 Page 1
2026 Junior Pan Paciϐic Championships - 2026-08-17 to 2026-08-20
Meet Program - Day 1 Prelims
Event 1 Women 13-18 50 LC Meter Butterfly
Lane Name Age Team Seed Time
Heat 1 of 1 Prelims
0 _____
1 WHEDDON, KATHERINE 17 BER 30.91 _____
2 CHEONG, MEGAN 17 SGP 28.28 _____
TEXT;

$parsed = process_heat_sheet($heat_sheet);
assert_heat_sheet_same(1, count($parsed['events']), 'Event was not parsed');
assert_heat_sheet_same(1, count($parsed['events'][0]['heats']), 'Heat with blank result columns was discarded');
assert_heat_sheet_same(
    [
        'lane' => 1,
        'name' => 'WHEDDON, KATHERINE',
        'age' => 17,
        'team' => 'BER',
        'seed_time' => '30.91',
    ],
    $parsed['events'][0]['heats'][0]['swimmers'][0],
    'Swimmer with blank result column was parsed incorrectly'
);
assert_heat_sheet_same(2, count($parsed['events'][0]['heats'][0]['swimmers']), 'Empty lane was treated as a swimmer');

echo "Heat-sheet parser regression tests passed.\n";
