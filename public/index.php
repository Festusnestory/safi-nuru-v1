<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\Admin\AuthController;
use App\Core\Router;

$router = new Router();

$router->any('/login', function (): void {
    (new AuthController())->loginPage();
});

$router->any('/logout', function (): void {
    (new AuthController())->logout();
});

$router->any('/buyer', function (): void {
    require \NURU_MATERIAL . '/buyer/index.php';
});

$router->any('/seller', function (): void {
    require \NURU_MATERIAL . '/seller/index.php';
});

$router->any('/agent', function (): void {
    require \NURU_MATERIAL . '/agent/index.php';
});

$router->dispatch();
