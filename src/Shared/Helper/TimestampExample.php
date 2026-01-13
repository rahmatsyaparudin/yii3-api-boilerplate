<?php

declare(strict_types=1);

namespace App\Shared\Helper;

/**
 * Example usage of timestamp helpers.
 */
final class TimestampExample
{
    public static function demonstrateFormats(): void
    {
        echo "=== Timestamp Helper Usage ===\n\n";

        // Static helper usage
        echo "Static Helper:\n";
        echo 'Local time: ' . TimestampHelper::now() . "\n";
        echo 'UTC time:   ' . TimestampHelper::utcNow() . "\n\n";

        // Format existing DateTime
        $now = new \DateTime();
        echo "Formatting existing DateTime:\n";
        echo 'Local format: ' . TimestampHelper::format($now) . "\n";
        echo 'UTC format:   ' . TimestampHelper::formatUtc($now) . "\n\n";

        // Expected output examples:
        echo "Expected formats:\n";
        echo "Local: 2025-01-13 18:30:00\n";
        echo "UTC:   2025-01-13T11:30:00Z\n";
    }

    public static function demonstrateChangeLog(): array
    {
        // Example change log structure with UTC timestamps
        return [
            'change_log' => [
                'created_at' => TimestampHelper::utcNow(),
                'created_by' => 123,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ],
        ];
    }
}
