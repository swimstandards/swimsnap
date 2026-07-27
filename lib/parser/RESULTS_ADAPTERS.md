# Results parser adapters

HY-TEK PDFs can produce different text orders even when the visible reports
look alike. The results parser therefore selects a document-level adapter
before it assembles events and results.

## Pipeline

1. `ResultsLayoutAdapterRegistry` scores the source headings.
2. The winning adapter normalizes line endings and extraction artifacts.
3. The adapter supplies the initial document layout mode.
4. `process_results()` performs shared event, round, result, relay, and split
   assembly.
5. The return value includes `parser.layout` and `parser.confidence` for
   diagnostics.

## Current adapters

- `stacked`: one logical result is spread across successive lines.
- `row`: most fields for one result appear on the same line.
- `auto`: compatibility fallback when no known signature is strong enough.

Layout selection is document-scoped. Per-event resets must clear pending rows
without discarding the selected layout because HY-TEK often omits column
headings after the first event.

## Adding a layout

Implement `ResultsLayoutAdapter`, give it heading/content signatures with
specific confidence weights, normalize only extraction-level artifacts, and
register it before `AutoResultsLayoutAdapter`.

Do not add meet names or slugs as signatures. Adapters represent reusable
extraction dialects, not individual meets.

Add at least one raw fixture and assert:

- selected adapter;
- event count;
- populated event count;
- result count;
- representative individual and relay rows.

As layout-specific row assemblers are extracted from `process_results()`, they
should live beside their adapter while retaining the canonical result keys
used by the templates.

## New paste policy

`ResultsPasteQualityDetector` is intentionally narrower than the compatibility
parser. New uploads with strong evidence of vertically scrambled columns are
rejected with instructions to copy the PDF directly from Google Chrome.
Normal row-oriented text is accepted, and unknown layouts are not rejected
solely because they lack a known signature.

This keeps existing stored documents readable without treating arbitrary
scrambled clipboard text as a supported input contract.
