<?php
declare(strict_types=1);

require_once __DIR__ . '/../html/material/config/pdo.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function publicBaseUrl(): string
{
    $configured = trim((string)(getenv('NURU_PUBLIC_URL') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $script = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    $basePath = $script === '/' || $script === '.' ? '' : rtrim($script, '/');
    return $scheme . '://' . $host . $basePath;
}

function csrfPublicToken(): string
{
    if (empty($_SESSION['nuru_public_csrf'])) {
        $_SESSION['nuru_public_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['nuru_public_csrf'];
}

/**
 * Property queries/formatting live in App\Models\PublicProperty now (same
 * SQL, moved verbatim). require_once here (rather than in every call site)
 * since public/bootstrap.php's own callers use plain `require`, not the
 * app/autoload.php entry point.
 */
require_once __DIR__ . '/../app/autoload.php';

function publicPropertyImage(?string $path): string
{
    return \App\Models\PublicProperty::imagePath($path);
}

function publicProperties(PDO $pdo, array $filters = [], ?int $limit = null): array
{
    return (new \App\Models\PublicProperty($pdo))->search($filters, $limit);
}

function publicPropertyById(PDO $pdo, int $id): ?array
{
    return (new \App\Models\PublicProperty($pdo))->find($id);
}

function propertyTypes(): array
{
    return \App\Models\PublicProperty::types();
}

function renderHeader(string $title, string $description, string $active = ''): void
{
    $appName = $GLOBALS['nuruSettings']['app_name'] ?? 'Nuru Real Estate';
    $canonical = publicBaseUrl() . (parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#14271f">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="<?= e(publicBaseUrl()) ?>/public/assets/images/nuru-hero.png">
  <title><?= e($title) ?> | <?= e($appName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/assets/css/site.css?v=<?= (int)filemtime(__DIR__ . '/assets/css/site.css') ?>">
  <link rel="stylesheet" href="public/assets/css/site-fixes.css?v=<?= (int)filemtime(__DIR__ . '/assets/css/site-fixes.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" data-header>
  <a class="brand" href="index.php" aria-label="Nuru home"><span class="brand-mark" aria-hidden="true">N</span><span>NURU<small>REAL ESTATE</small></span></a>
  <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu" data-nav-toggle><span></span><span></span><span></span><span class="sr-only">Open menu</span></button>
  <nav id="site-nav" class="site-nav" aria-label="Main navigation" data-nav>
    <a class="<?= $active === 'home' ? 'active' : '' ?>" href="index.php">Home</a>
    <a class="<?= $active === 'properties' ? 'active' : '' ?>" href="properties.php">Properties</a>
    <a class="<?= $active === 'about' ? 'active' : '' ?>" href="about.php">About</a>
    <a class="<?= $active === 'contact' ? 'active' : '' ?>" href="contact.php">Contact</a>
    <a class="portal-link" href="html/material/authentication-login.php">Client portal <span aria-hidden="true">↗</span></a>
  </nav>
</header>
<?php
}

function renderFooter(): void
{
    ?>
<footer class="site-footer">
  <div><a class="brand brand-light" href="index.php"><span class="brand-mark">N</span><span>NURU<small>REAL ESTATE</small></span></a><p>Property guidance built around clarity, local knowledge, and outcomes that last.</p></div>
  <div><h2>Explore</h2><a href="properties.php">Properties</a><a href="about.php">Our approach</a><a href="contact.php">Contact us</a></div>
  <div><h2>Start here</h2><a href="html/material/buyer/index.php">Buyer application</a><a href="html/material/seller/index.php">List a property</a><a href="html/material/agent/index.php">Join as an agent</a></div>
  <div><h2>Portal</h2><a href="html/material/authentication-login.php">Sign in</a><p>Windhoek, Namibia<br>Mon–Fri · 08:00–17:00</p></div>
  <div class="footer-bottom"><span>© <?= date('Y') ?> Nuru Real Estate. All rights reserved.</span><span>Built for Namibia.</span></div>
</footer>
<script src="public/assets/js/site.js?v=<?= (int)filemtime(__DIR__ . '/assets/js/site.js') ?>" defer></script>
</body>
</html>
<?php
}
