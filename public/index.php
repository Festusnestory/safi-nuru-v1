<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BuyerController;
use App\Controllers\Admin\SellerController;
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

$router->any('/admin/buyers-list', function (): void {
    (new BuyerController())->list();
});

$router->any('/admin/buyer-admin-form', function (): void {
    (new BuyerController())->form();
});

$router->any('/admin/admin-buyer-processor', function (): void {
    (new BuyerController())->store();
});

$router->any('/admin/buyers-profile', function (): void {
    (new BuyerController())->profile();
});

$router->any('/admin/approve-buyer', function (): void {
    (new BuyerController())->approve();
});

$router->any('/admin/delete-buyer', function (): void {
    (new BuyerController())->reject();
});

$router->any('/admin/sellers-list', function (): void {
    (new SellerController())->list();
});

$router->any('/admin/sellers-profile', function (): void {
    (new SellerController())->profile();
});

$router->any('/admin/seller-admin-form', function (): void {
    (new SellerController())->formPage();
});

$router->any('/admin/seller-admin-processor', function (): void {
    (new SellerController())->store();
});

$router->any('/admin/review-seller-application', function (): void {
    (new SellerController())->review();
});

$router->any('/admin/properties-available', function (): void {
    (new \App\Controllers\Admin\PropertyController())->available();
});

$router->any('/admin/properties-sold', function (): void {
    (new \App\Controllers\Admin\PropertyController())->sold();
});

$router->any('/admin/property-admin-form', function (): void {
    (new \App\Controllers\Admin\PropertyController())->form();
});

$router->dispatch();
