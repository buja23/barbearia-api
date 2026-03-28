<?php

/**
 * Patches vendor/laravel/framework/config/database.php to replace the deprecated
 * PDO::MYSQL_ATTR_SSL_CA constant with the PHP 8.4+ Pdo\Mysql::ATTR_SSL_CA,
 * keeping a fallback for PHP < 8.4 so the project runs on any PHP 8 version.
 *
 * Run automatically via Composer post-install-cmd / post-update-cmd.
 * Can also be run manually: php scripts/patch-vendor-pdo.php
 */

$file = __DIR__ . '/../vendor/laravel/framework/config/database.php';

if (!file_exists($file)) {
    echo "patch-vendor-pdo: framework config not found at $file, skipping.\n";
    exit(0);
}

$contents = file_get_contents($file);

// Already patched — nothing to do
if (str_contains($contents, 'Pdo\\Mysql::ATTR_SSL_CA') || str_contains($contents, 'Pdo\Mysql::ATTR_SSL_CA')) {
    echo "patch-vendor-pdo: already patched, skipping.\n";
    exit(0);
}

$patched = str_replace(
    'PDO::MYSQL_ATTR_SSL_CA',
    '(PHP_VERSION_ID >= 80400 ? \\Pdo\\Mysql::ATTR_SSL_CA : 1011)',
    $contents
);

if ($patched === $contents) {
    echo "patch-vendor-pdo: nothing to replace, skipping.\n";
    exit(0);
}

file_put_contents($file, $patched);
echo "patch-vendor-pdo: patched $file\n";
