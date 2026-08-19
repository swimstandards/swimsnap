<?php

require_once __DIR__ . '/../lib/utils.php';

function assert_metadata_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL .
            'Expected: ' . var_export($expected, true) . PHP_EOL .
            'Actual:   ' . var_export($actual, true)
        );
    }
}

assert_metadata_same(
    [
        'meet_name' => '2026 Junior Pacific Championships',
        'meet_start_date' => '2026-08-17',
        'meet_end_date' => '2026-08-20',
    ],
    parse_hytek_meet_title(normalize_hytek_text('2026 Junior Paciϐic Championships - 2026-08-17 to 2026-08-20\\')),
    'ISO HY-TEK date range or copied beta character was not normalized'
);

assert_metadata_same(
    [
        'meet_name' => 'Example Invitational',
        'meet_start_date' => '2026-08-17',
        'meet_end_date' => '2026-08-20',
    ],
    parse_hytek_meet_title('Example Invitational - 8/17/2026 to 8/20/2026'),
    'Traditional HY-TEK date range changed'
);

assert_metadata_same(
    [
        'meet_name' => 'One Day Meet',
        'meet_start_date' => '2026-08-17',
        'meet_end_date' => '2026-08-17',
    ],
    parse_hytek_meet_title('One Day Meet - 2026-08-17'),
    'Single ISO HY-TEK date was not parsed'
);

echo "Meet metadata regression tests passed.\n";
