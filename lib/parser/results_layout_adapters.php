<?php

/**
 * Document-level adapters for HY-TEK result text extraction layouts.
 *
 * These adapters deliberately operate before event parsing. Their job is to
 * identify the extraction dialect once, normalize its input, and provide the
 * initial parser mode. Event/result assembly remains shared so introducing a
 * new layout does not duplicate meet semantics.
 */

final class ResultsLayoutDocument
{
    public function __construct(
        public readonly string $layout,
        public readonly array $lines,
        public readonly bool $stacked,
        public readonly bool $persistsAcrossEvents,
        public readonly int $confidence
    ) {
    }
}

interface ResultsLayoutAdapter
{
    public function name(): string;

    /**
     * Return a non-negative confidence score. Zero means unsupported.
     */
    public function confidence(array $lines): int;

    public function prepare(array $lines, int $confidence): ResultsLayoutDocument;
}

abstract class AbstractResultsLayoutAdapter implements ResultsLayoutAdapter
{
    protected function normalizedLines(array $lines): array
    {
        return array_map(static function ($line): string {
            $line = str_replace(["\r", "\f"], '', (string) $line);
            $line = str_replace("\u{00A0}", ' ', $line);
            return rtrim($line);
        }, $lines);
    }

    protected function contains(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) {
            if (preg_match($pattern, trim((string) $line))) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Fields from a single result are emitted on successive lines.
 *
 * Typical sequence:
 *   age
 *   team
 *   seed time
 *   rank/name/final time
 */
final class StackedResultsLayoutAdapter extends AbstractResultsLayoutAdapter
{
    public function name(): string
    {
        return 'stacked';
    }

    public function confidence(array $lines): int
    {
        $score = 0;
        $patterns = [
            '/^Name Finals Time$/i' => 45,
            '/^Seed Time(?: Points)?$/i' => 30,
            '/^Yr School$/i' => 35,
            '/^Name School Finals (?:Time|Score)(?: Points)?(?: Prelim Time)?$/i' => 45,
            '/^Team Relay (?:Finals|Prelim|Seed)/i' => 45,
            '/^Team Finals Time$/i' => 45,
            '/^RelayTeam Finals Time$/i' => 45,
        ];

        foreach ($patterns as $pattern => $weight) {
            if ($this->contains($lines, $pattern)) {
                $score += $weight;
            }
        }

        // A vertically extracted Masters report often splits this heading
        // into three independent lines.
        if (
            $this->contains($lines, '/^Name Team Finals Time$/i') &&
            $this->contains($lines, '/^Age$/i') &&
            $this->contains($lines, '/^Seed Time$/i')
        ) {
            $score += 80;
        }

        return $score;
    }

    public function prepare(array $lines, int $confidence): ResultsLayoutDocument
    {
        $persists_across_events =
            $this->contains($lines, '/^Name Team Finals Time$/i') &&
            $this->contains($lines, '/^Age$/i') &&
            $this->contains($lines, '/^Seed Time$/i');

        return new ResultsLayoutDocument(
            $this->name(),
            $this->normalizedLines($lines),
            true,
            $persists_across_events,
            $confidence
        );
    }
}

/**
 * Each result is substantially present on one line.
 */
final class RowResultsLayoutAdapter extends AbstractResultsLayoutAdapter
{
    public function name(): string
    {
        return 'row';
    }

    public function confidence(array $lines): int
    {
        $score = 0;
        $patterns = [
            '/^Name Age Team Finals Time$/i' => 60,
            '/^Name Age Team Seed Time Finals Time$/i' => 70,
            '/^YrName School Finals (?:Time|Score)(?: Points)?$/i' => 60,
            '/^YrName SchoolName Finals (?:Time|Score)(?: Points)?$/i' => 60,
        ];

        foreach ($patterns as $pattern => $weight) {
            if ($this->contains($lines, $pattern)) {
                $score += $weight;
            }
        }

        return $score;
    }

    public function prepare(array $lines, int $confidence): ResultsLayoutDocument
    {
        return new ResultsLayoutDocument(
            $this->name(),
            $this->normalizedLines($lines),
            false,
            false,
            $confidence
        );
    }
}

/**
 * Conservative compatibility adapter when no known heading signature wins.
 */
final class AutoResultsLayoutAdapter extends AbstractResultsLayoutAdapter
{
    public function name(): string
    {
        return 'auto';
    }

    public function confidence(array $lines): int
    {
        return 1;
    }

    public function prepare(array $lines, int $confidence): ResultsLayoutDocument
    {
        return new ResultsLayoutDocument(
            $this->name(),
            $this->normalizedLines($lines),
            false,
            false,
            $confidence
        );
    }
}

final class ResultsLayoutAdapterRegistry
{
    /** @var ResultsLayoutAdapter[] */
    private array $adapters;

    public function __construct(?array $adapters = null)
    {
        $this->adapters = $adapters ?? [
            new StackedResultsLayoutAdapter(),
            new RowResultsLayoutAdapter(),
            new AutoResultsLayoutAdapter(),
        ];
    }

    public function prepare(string $content): ResultsLayoutDocument
    {
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $selected = null;
        $selected_score = -1;

        foreach ($this->adapters as $adapter) {
            $score = $adapter->confidence($lines);
            if ($score > $selected_score) {
                $selected = $adapter;
                $selected_score = $score;
            }
        }

        if (!$selected) {
            $selected = new AutoResultsLayoutAdapter();
            $selected_score = 1;
        }

        return $selected->prepare($lines, $selected_score);
    }
}

final class ResultsPasteQualityDetector
{
    /**
     * Classify only strong clipboard-layout evidence. "unknown" remains
     * uploadable so a new but valid HY-TEK row format is not rejected merely
     * because it has not appeared in the regression corpus.
     */
    public function analyze(string $content): array
    {
        $lines = array_values(array_filter(array_map(
            static fn(string $line): string => trim(preg_replace('/\s+/', ' ', $line)),
            preg_split('/\r\n|\n|\r/', $content) ?: []
        ), static fn(string $line): bool => $line !== ''));

        $normal_header_patterns = [
            '/^Name Age Team (?:Seed Time )?(?:Prelim Time )?Finals Time(?: Points)?$/i',
            '/^Name (?:Yr|Year) (?:Team|School) (?:Seed Time )?(?:Prelim Time )?Finals Time(?: Points)?$/i',
            '/^Team Relay (?:Seed Time )?(?:Prelim Time )?Finals Time(?: Points)?$/i',
        ];
        $normal_headers = 0;
        foreach ($lines as $line) {
            foreach ($normal_header_patterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $normal_headers++;
                    break;
                }
            }
        }

        $has_line = static function (array $values, string $pattern): bool {
            foreach ($values as $value) {
                if (preg_match($pattern, $value)) {
                    return true;
                }
            }
            return false;
        };

        $scrambled_signatures = 0;
        if (
            $has_line($lines, '/^(?:Prelim Time )?Finals Time(?: Points)?$/i') &&
            $has_line($lines, '/^Name (?:Age|Yr|Year) (?:Team|School)$/i')
        ) {
            $scrambled_signatures++;
        }
        if (
            $has_line($lines, '/^Name Finals Time$/i') &&
            $has_line($lines, '/^(?:Age Team|Yr School)$/i')
        ) {
            $scrambled_signatures++;
        }
        if ($normal_headers > 0) {
            return [
                'quality' => 'normal',
                'normal_headers' => $normal_headers,
                'scrambled_signatures' => $scrambled_signatures,
            ];
        }

        if ($scrambled_signatures > 0) {
            return [
                'quality' => 'scrambled',
                'normal_headers' => 0,
                'scrambled_signatures' => $scrambled_signatures,
            ];
        }

        return [
            'quality' => 'unknown',
            'normal_headers' => 0,
            'scrambled_signatures' => 0,
        ];
    }
}
