<?php
declare(strict_types=1);
require __DIR__ . '/public/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$base = publicBaseUrl();
$urls = [[$base . '/', '1.0'], [$base . '/properties.php', '0.9'], [$base . '/about.php', '0.7'], [$base . '/contact.php', '0.7']];
foreach (publicProperties($pdo) as $property) { $urls[] = [$base . '/property.php?id=' . (int)$property['id'], '0.8']; }
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><?php foreach($urls as [$url,$priority]): ?><url><loc><?= e($url) ?></loc><priority><?= $priority ?></priority></url><?php endforeach; ?></urlset>
