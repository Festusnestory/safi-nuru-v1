<?php
declare(strict_types=1);

if (!defined('NURU_ROOT')) {
    define('NURU_ROOT', dirname(__DIR__));
    define('NURU_MATERIAL', NURU_ROOT . '/html/material');
    define('NURU_APP', __DIR__);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = NURU_APP . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
