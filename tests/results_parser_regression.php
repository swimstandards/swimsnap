<?php

require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/parser/results_parser.php';

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL .
            'Expected: ' . var_export($expected, true) . PHP_EOL .
            'Actual:   ' . var_export($actual, true)
        );
    }
}

$fixture_expectations = [
    '2025-dyna-imx-imr-2025-04-25-dynamo.txt' => ['row', 84, 1818],
    '2025-tyr-pro-swim-series-fort-lauderdale-2025-04-30-usa.txt' => ['row', 40, 2446],
    'pro-bowl-pentathlon-2025-pro-bowl-challenge-2025-02-01-occoquan.txt' => ['row', 50, 431],
    'wednesday-women-s-prelim-2025-ncsa-spring-swimming-championships-2025-03-18-fast.txt' => ['row', 7, 451],
];

foreach ($fixture_expectations as $filename => [$layout, $event_count, $result_count]) {
    $path = __DIR__ . '/../example/raw/results/' . $filename;
    $parsed = process_results(file_get_contents($path));
    $actual_result_count = array_sum(array_map(
        static fn(array $event): int => count($event['results'] ?? []),
        $parsed['events']
    ));

    assert_same($layout, $parsed['parser']['layout'], "$filename layout changed");
    assert_same($event_count, count($parsed['events']), "$filename event count changed");
    assert_same($result_count, $actual_result_count, "$filename result count changed");
}

// Masters-style vertical extraction. The second event intentionally omits
// headings to prove that adapter selection persists across event boundaries.
$stacked_masters = <<<'TEXT'
Example Club HY-TEK's MEET MANAGER 7.0 - Page 1
Example Masters Meet - 1/1/2026
Results
Women 18-24 50 Yard Freestyle
Name Team Finals Time
Age
Seed Time
24
Example Masters-13
28.00
1 Swimmer, First 27.45
Women 18-24 100 Yard Freestyle
24
Example Masters-13
1:00.00
1 Swimmer, First 1:01.46
TEXT;

$parsed_masters = process_results($stacked_masters);
assert_same('stacked', $parsed_masters['parser']['layout'], 'Masters layout was not detected');
assert_same(2, count($parsed_masters['events']), 'Masters event count changed');
assert_same(1, count($parsed_masters['events'][0]['results']), 'First Masters event did not parse');
assert_same(1, count($parsed_masters['events'][1]['results']), 'Stacked layout did not persist');

// Rank/time-first relay extraction. Every entry must retain its own team,
// letter, time, and points instead of being shifted to the following relay.
$stacked_relays = <<<'TEXT'
Example Club HY-TEK's MEET MANAGER 3.0 - Page 1
Example Relay Meet - 1/1/2026
Results
Girls 12 & Under 200 Yard Freestyle Relay
Team Finals Time
Relay
1 1:50.00 40
AAA
A
2 1:55.00 34
BBB
A
TEXT;

$parsed_relays = process_results($stacked_relays);
$relays = $parsed_relays['events'][0]['results'];
assert_same('stacked', $parsed_relays['parser']['layout'], 'Relay layout was not detected');
assert_same(2, count($relays), 'Stacked relay count changed');
assert_same(['1', 'AAA', 'A', '1:50.00', 40], [
    $relays[0]['rank'],
    $relays[0]['team'],
    $relays[0]['relay'],
    $relays[0]['finals_time'],
    $relays[0]['points'],
], 'First stacked relay was shifted');

assert_same(
    ['26.33', '54.99', '1:24.89', '1:54.15'],
    parse_split_line('26.33 54.99 1:24.89 1:54.15'),
    'Bare cumulative splits were not preserved'
);

echo "Results parser regression checks passed.\n";
