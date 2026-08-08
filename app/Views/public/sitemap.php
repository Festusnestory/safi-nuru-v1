<?php
/** @var array $urls */
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><?php foreach($urls as [$url,$priority]): ?><url><loc><?= e($url) ?></loc><priority><?= $priority ?></priority></url><?php endforeach; ?></urlset>
