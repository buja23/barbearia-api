<?php
echo 'opcache: ' . (function_exists('opcache_get_status') && opcache_get_status() !== false ? 'enabled' : 'disabled') . PHP_EOL;

// Check which files containing the deprecated constant are in the autoload classmap
$classmap = include '/var/www/html/vendor/composer/autoload_classmap.php';
$matches = [];
foreach ($classmap as $class => $path) {
    if (str_contains($class, 'MySqlSchema') || str_contains($class, 'MySqlConnector') || str_contains($class, 'DatabaseManager')) {
        $matches[$class] = str_replace('/var/www/html/', '', $path);
    }
}
echo "Relevant classes in autoload:\n";
foreach ($matches as $class => $path) {
    echo "  $class => $path\n";
}
