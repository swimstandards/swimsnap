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

$course_suffix_relay = <<<'TEXT'
Example Club HY-TEK's MEET MANAGER 8.0 - Page 1
Example Relay Meet - 1/1/2026
Results
Event 1 Men 400 LC Meter Freestyle Relay
Team Relay Seed Time Finals Time
18 North Carolina Aquatic Club-NC A 3:04.88Y 3:33.13 J
TEXT;

$parsed_course_suffix = process_results($course_suffix_relay);
$course_suffix_result = $parsed_course_suffix['events'][0]['results'][0];
assert_same(
    ['North Carolina Aquatic Club-NC A', '3:04.88Y', '3:33.13'],
    [
        $course_suffix_result['team'],
        $course_suffix_result['seed_time'],
        $course_suffix_result['finals_time'],
    ],
    'Relay seed course suffix was absorbed into the team'
);

$record_result = parse_result_line(
    '1 Shi, Justin 16 Eagle Swim Team Inc.-MD 53.90 53.50# TYR'
);
assert_same(
    ['1', 'Shi Justin', 16, 'Eagle Swim Team Inc.-MD', '53.90', '53.50', '# TYR'],
    [
        $record_result['rank'],
        $record_result['name'],
        $record_result['age'],
        $record_result['team'],
        $record_result['seed_time'],
        $record_result['result_time'],
        $record_result['note'],
    ],
    'Attached record marker caused an individual result to be skipped'
);

assert_same(
    ['26.33', '28.66', '29.90', '29.26'],
    parse_split_line('26.33 54.99 1:24.89 1:54.15'),
    'Bare cumulative splits were not normalized to lap times'
);

assert_same(
    ['29.88', '31.85', '32.72', '32.95'],
    parse_split_line('29.88 1:01.73 (31.85) 1:34.45 (32.72) 2:07.40 (32.95)'),
    'Mixed cumulative/lap splits were not normalized'
);

assert_same(
    ['32.85', '32.94', '32.79', '32.20'],
    parse_split_line('2:40.25 (32.85) 3:13.19 (32.94) 3:45.98 (32.79) 4:18.18 (32.20)'),
    'Continuation lap splits retained cumulative checkpoints'
);

echo "Results parser regression checks passed.\n";
