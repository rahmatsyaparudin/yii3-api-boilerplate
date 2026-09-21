#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Composer Script: Compare Skeleton Versions
 *
 * Compares the project's installed skeleton version
 * (scripts/skeleton.version) against the version shipped by the
 * rahmatsyaparudin/yii3-api-boilerplate package in vendor/, so you
 * can tell whether `composer skeleton:update` has something to sync.
 */

$projectRoot = dirname(__DIR__);

$readVersion = static function (string $file): ?string {
    if (!file_exists($file)) {
        return null;
    }

    $version = trim((string) file_get_contents($file));

    return $version === '' ? null : $version;
};

$projectVersion = $readVersion($projectRoot . '/scripts/skeleton.version');
$vendorVersion  = $readVersion(
    $projectRoot . '/vendor/rahmatsyaparudin/yii3-api-boilerplate/scripts/skeleton.version',
);

echo "🦴 Skeleton version check\n";
echo '   Project (scripts/skeleton.version): ' . ($projectVersion ?? 'not installed') . "\n";
echo '   Vendor  (boilerplate package):      ' . ($vendorVersion ?? 'not installed') . "\n";

if ($vendorVersion === null) {
    echo "\n⚠️  Boilerplate package not found in vendor.\n";
    echo "   Add it to require-dev first: composer require --dev rahmatsyaparudin/yii3-api-boilerplate:dev-main\n";

    exit(1);
}

if ($projectVersion === null) {
    echo "\n📥 Skeleton not installed yet. Run 'composer skeleton:update' to install {$vendorVersion}.\n";

    exit(0);
}

if ($projectVersion === $vendorVersion) {
    echo "\n✅ Up to date ({$projectVersion}).\n";

    exit(0);
}

echo "\n🔄 Update available: {$projectVersion} → {$vendorVersion}\n";
echo "   Run 'composer skeleton:update' to sync.\n";

exit(0);
