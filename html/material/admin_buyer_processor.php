<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/autoload.php';
(new \App\Controllers\Admin\BuyerController())->store();
