<?php
// Script to find where PDO::MYSQL_ATTR_SSL_CA is being accessed
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (str_contains($errstr, 'MYSQL_ATTR_SSL_CA')) {
        echo "DEPRECATED at: $errfile:$errline\n";
        echo debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) ? implode("\n", array_map(fn($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?'), debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))) : '';
        echo "\n---\n";
    }
    return false;
});

// Boot just enough of Laravel to trigger the config loading
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make('config'); // force config loading

echo "Done\n";
