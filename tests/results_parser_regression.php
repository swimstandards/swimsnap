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

$quality_detector = new ResultsPasteQualityDetector();
$normal_paste = <<<'TEXT'
North Carolina Swimming - For Office Use Only License HY-TEK's MEET MANAGER 3.0 - Page 1
Example Meet - 12/10/2010
Results
Girls 8 & Under 25 Yard Freestyle
Name Age Team Finals Time
1 Katie Rauch 8 ATOM-NC 15.95 20
2 Isabel Pennington 8 GYW-NC 16.08 17
TEXT;
assert_same(
    'normal',
    $quality_detector->analyze($normal_paste)['quality'],
    'Normal row-oriented Chrome paste was rejected'
);
$parsed_normal_paste = process_results($normal_paste);
assert_same(2, count($parsed_normal_paste['events'][0]['results']), 'Normal HY-TEK rows did not parse');
assert_same(
    ['Katie Rauch', 8, 'ATOM-NC', '15.95', 20],
    [
        $parsed_normal_paste['events'][0]['results'][0]['name'],
        $parsed_normal_paste['events'][0]['results'][0]['age'],
        $parsed_normal_paste['events'][0]['results'][0]['team'],
        $parsed_normal_paste['events'][0]['results'][0]['result_time'],
        $parsed_normal_paste['events'][0]['results'][0]['points'],
    ],
    'Normal HY-TEK row fields changed'
);

$combined_finals_and_prelims = <<<'TEXT'
Example Club HY-TEK's MEET MANAGER 8.0 - Page 1
Example Championship - 1/1/2026
Results
Boys 13-14 100 Yard Freestyle
Name Age Team Finals Time
A - Final
1 Hagen Dietrich 13 WST-NC 53.58 20
25.42 53.58
B - Final
11 Connor Parke 13 ATOM-NC 1:01.68 6
29.13 1:01.68
Boys 13-14 100 Yard Freestyle
Name Age Team Prelim Time
Preliminaries
1 Hagen Dietrich 13 WST-NC 53.73 q
25.48 53.73
2 Noah McRea 14 CVAC-NC 54.38 q
26.32 54.38
TEXT;

$parsed_combined_rounds = process_results($combined_finals_and_prelims);
assert_same(1, count($parsed_combined_rounds['events']), 'Repeated finals/prelims header split one event');
assert_same(
    ['A Final', 'B Final', 'Preliminaries', 'Preliminaries'],
    array_column($parsed_combined_rounds['events'][0]['results'], 'round'),
    'Combined event rounds were not retained'
);

$scrambled_paste = <<<'TEXT'
North Carolina Swimming - For Office Use Only License HY-TEK's MEET MANAGER 3.0 - Page 1
Example Meet - 12/10/2010
Results
Girls 8 & Under 25 Yard Freestyle
Finals Time
11 12 Isabelle Sanz
Elizabeth Hill
8
7
NMA-NC
BAD-NC
23.88 6
Name Age Team
TEXT;
assert_same(
    'scrambled',
    $quality_detector->analyze($scrambled_paste)['quality'],
    'Scrambled clipboard columns were not detected'
);

$fixture_expectations = [
    '2025-dyna-imx-imr-2025-04-25-dynamo.txt' => ['row', 84, 1902],
    '2025-tyr-pro-swim-series-fort-lauderdale-2025-04-30-usa.txt' => ['row', 40, 2446],
    'pro-bowl-pentathlon-2025-pro-bowl-challenge-2025-02-01-occoquan.txt' => ['row', 50, 481],
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
assert_same(
    [24, 'Example Masters-13', '28.00'],
    [
        $parsed_masters['events'][0]['results'][0]['age'],
        $parsed_masters['events'][0]['results'][0]['team'],
        $parsed_masters['events'][0]['results'][0]['seed_time'],
    ],
    'Stacked Masters metadata was dropped'
);
assert_same(
    'unknown',
    $quality_detector->analyze($stacked_masters)['quality'],
    'Supported stacked Masters results were rejected as scrambled'
);

$stacked_masters_non_finish = <<<'TEXT'
Example Club HY-TEK's MEET MANAGER 7.0 - Page 1
Example Masters Meet - 1/1/2026
Results
Women 18-24 100 Yard Freestyle
Name Team Finals Time
Age
Seed Time
24
Example Masters-13
1:01.78
--- Swimmer, First NS
22
Other Masters-13
1:10.00--- Swimmer, Second DQ
TEXT;
$parsed_masters_non_finish = process_results($stacked_masters_non_finish);
assert_same(
    [
        [24, 'Example Masters-13', '1:01.78', 'NS'],
        [22, 'Other Masters-13', '1:10.00', 'DQ'],
    ],
    array_map(
        static fn(array $result): array => [
            $result['age'],
            $result['team'],
            $result['seed_time'],
            $result['note'],
        ],
        $parsed_masters_non_finish['events'][0]['results']
    ),
    'Stacked Masters non-finish rows were dropped'
);

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

$older_relay_report = process_results(file_get_contents(
    __DIR__ . '/../raw/results/3a-state-swimming-diving-championships-7467cc.txt'
));
$older_event = $older_relay_report['events'][0]['results'];
assert_same(39, count($older_event), 'HY-TEK 7 relay rows were not all parsed');
assert_same(
    ['Charlotte Catholic', '1:49.64', '1:46.96', 'D'],
    [$older_event[0]['team'], $older_event[0]['seed_time'], $older_event[0]['finals_time'], $older_event[0]['note']],
    'HY-TEK 7 relay final/prelim columns were misassigned'
);

$record_result = parse_result_line(
    '1 Shi, Justin 16 Eagle Swim Team Inc.-MD 53.90 53.50# TYR'
);

assert_same(
    ['23.58', '49.79', '23.48', '49.63', '23.71', '49.95', '22.53', '47.16'],
    parse_split_line('23.58 49.79 23.48 49.63 23.71 49.95 22.53 47.16'),
    'Interleaved HY-TEK relay cumulative checkpoints were retained as laps'
);

$older_400_relay = process_results(file_get_contents(
    __DIR__ . '/../raw/results/3a-state-swimming-diving-championships-7467cc.txt'
));
foreach ($older_400_relay['events'] as $event) {
    foreach ($event['results'] as $result) {
        if (($result['team'] ?? '') === 'Charlotte Catholic' && ($result['finals_time'] ?? '') === '3:35.78') {
            assert_same(8, count($result['splits'] ?? []), 'Next HY-TEK 7 relay split line was attached to prior result');
        }
    }
}

$older_school_rows = process_results(<<<'TEXT'
Triangle Aquatic Center - Site License Hy-Tek's MEET MANAGER 7.0 - Page 1
Results
Event 20 Men 100 Yard Backstroke
Name School Finals Time Prelim Time
A - Final
1 Lambert, Zack Hickory Ridge 54.18 52.13
25.50 26.63
2 Bittner, Bobby 52.78 West Carteret 53.83
25.61 27.17
TEXT);
assert_same(
    ['Lambert, Zack', 'Hickory Ridge', '52.13', '54.18'],
    [
        $older_school_rows['events'][0]['results'][0]['name'],
        $older_school_rows['events'][0]['results'][0]['team'],
        $older_school_rows['events'][0]['results'][0]['seed_time'],
        $older_school_rows['events'][0]['results'][0]['result_time'],
    ],
    'HY-TEK 7 school row layout was misparsed'
);

$masters_relays = process_results(file_get_contents(
    __DIR__ . '/../raw/results/2019-sunbelt-championship-meet-fcf890.txt'
));
$mixed_400 = null;
foreach ($masters_relays['events'] as $event) {
    if (($event['gender'] ?? '') === 'Mixed' && ($event['event_name'] ?? '') === '55+ 400 Yard Freestyle Relay') {
        $mixed_400 = $event['results'][0] ?? null;
        break;
    }
}
assert_same(
    ['SwimMAC Masters - Charlotte-13', 'NT', '5:09.79', 8],
    [$mixed_400['team'], $mixed_400['seed_time'], $mixed_400['finals_time'], count($mixed_400['splits'] ?? [])],
    'Masters NT relay rows or splits were not parsed'
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
