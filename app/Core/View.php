<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * Renders app/Views/{dot.path}.php as a full HTML document (the legacy
     * pages this replaces are each a complete <html>...</html> document, not
     * a fragment - so views here follow the same shape rather than a
     * separate layout-wrapping scheme). $data keys become local variables in
     * the view, matching how the original pages used variables like
     * $properties/$words directly in their inline HTML.
     */
    public static function render(string $template, array $data = []): void
    {
        $path = NURU_APP . '/Views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$template} ({$path})");
        }
        // Legacy partials this view may require (top-bar.php, left-sidebar.php,
        // _page_head.php) read $pdo/$nuruSettings as plain variables inherited
        // from the including page's top-level scope - `global` here restores
        // that same visibility for code nested inside this method.
        global $pdo, $nuruSettings;
        extract($data, EXTR_SKIP);
        require $path;
    }
}
