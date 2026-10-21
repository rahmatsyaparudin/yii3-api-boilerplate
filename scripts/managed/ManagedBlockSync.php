<?php

declare(strict_types=1);

/**
 * Marker-delimited managed block synchronization.
 *
 * Used by skeleton-update.php to keep skeleton-owned fragments inside
 * project-owned config files (routes.php, access.php) fresh without
 * overwriting the whole file. A managed block looks like:
 *
 *     // === SKELETON MANAGED X — do not edit between markers ===
 *     ...skeleton-owned lines...
 *     // === END SKELETON MANAGED X ===
 *
 * Sync rules:
 * - Markers found → the whole block is replaced with the template
 *   (update-in-place, so a newer skeleton refreshes the block).
 * - No markers → the template is inserted before the last anchor match.
 * - No anchor → nothing is written; caller prints a manual hint.
 *
 * The markers are derived from the template's first and last lines, so
 * template and markers can never drift apart.
 */
final class ManagedBlockSync
{
    public const REPLACED       = 'replaced';
    public const INSERTED       = 'inserted';
    public const UNCHANGED      = 'unchanged';
    public const MISSING_ANCHOR = 'missing-anchor';

    /**
     * @param string $content       Current file content.
     * @param string $block         Template — must start with the start
     *                              marker and end with the end marker.
     * @param string $anchorPattern Regex matching the insertion point;
     *                              the block is inserted before the LAST
     *                              match (used only when markers absent).
     *
     * @return array{content: string, action: string}
     */
    public static function sync(string $content, string $block, string $anchorPattern): array
    {
        $block = \rtrim($block);
        $lines = \preg_split('/\r?\n/', $block);

        if ($lines === false || \count($lines) < 2) {
            return ['content' => $content, 'action' => self::MISSING_ANCHOR];
        }

        $start = $lines[0];
        $end   = $lines[\count($lines) - 1];

        // 1. Markers present → replace the whole block (update-in-place).
        $pattern = '/' . \preg_quote($start, '/') . '.*?' . \preg_quote($end, '/') . '/s';

        if (\preg_match($pattern, $content) === 1) {
            $updated = (string) \preg_replace_callback(
                $pattern,
                static fn (): string => $block,
                $content
            );

            return [
                'content' => $updated,
                'action'  => $updated === $content ? self::UNCHANGED : self::REPLACED,
            ];
        }

        // 2. No markers → insert before the last anchor match.
        if (\preg_match_all($anchorPattern, $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            [, $position] = \end($matches[0]);
            $updated = \substr($content, 0, $position)
                . $block . "\n"
                . \substr($content, $position);

            return ['content' => $updated, 'action' => self::INSERTED];
        }

        return ['content' => $content, 'action' => self::MISSING_ANCHOR];
    }
}
