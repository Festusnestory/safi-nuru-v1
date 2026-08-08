<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal front-controller router for the new clean-URL routes (starting
 * with /login, /logout). Existing pages keep working at their current
 * html/material/*.php and root *.php URLs via thin shims - this only
 * handles the small, growing set of routes registered against public/index.php.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function any(string $path, callable $handler): void
    {
        $this->get($path, $handler);
        $this->post($path, $handler);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requested = (string) ($_GET['__route'] ?? '');
        $path = '/' . trim($requested, '/');

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $handler();
    }

    /**
     * Absolute base path of the app (e.g. "/nuru-v1"), derived from
     * SCRIPT_NAME regardless of whether the current request landed here via
     * public/index.php or a legacy html/material/*.php file - both markers
     * are checked so shared views render correct links either way.
     */
    public static function basePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        foreach (['/public/index.php', '/html/material'] as $marker) {
            $pos = strpos($script, $marker);
            if ($pos !== false) {
                return rtrim(substr($script, 0, $pos), '/');
            }
        }
        return rtrim(dirname($script), '/');
    }
}
