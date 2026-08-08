<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected \PDO $pdo;

    public function __construct()
    {
        Bootstrap::init();
        $this->pdo = Database::connection();
    }

    protected function requireRole(array $allowedRoles): void
    {
        Auth::requireRole($allowedRoles);
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
