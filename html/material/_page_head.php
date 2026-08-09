<?php require_once __DIR__ . '/../../app/autoload.php'; $__nuruBase = \App\Core\Router::basePath(); ?>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex,nofollow" />
<?php $resolvedAppName = htmlspecialchars((string)($nuruSettings['app_name'] ?? 'Nuru Real Estate'), ENT_QUOTES, 'UTF-8'); ?>
<title><?= $resolvedAppName ?><?= isset($pageTitle) ? ' - ' . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : '' ?></title>
<link rel="icon" type="image/png" sizes="16x16" href="<?= $__nuruBase ?>/assets/images/favicon.png" />
<link rel="stylesheet" href="<?= $__nuruBase ?>/assets/libs/apexcharts/dist/apexcharts.css" />
<link href="<?= $__nuruBase ?>/assets/extra-libs/css-chart/css-chart.css" rel="stylesheet" />
<link href="<?= $__nuruBase ?>/assets/libs/jvectormap/jquery-jvectormap.css" rel="stylesheet" />
<link href="<?= $__nuruBase ?>/dist/css/style.min.css" rel="stylesheet" />
