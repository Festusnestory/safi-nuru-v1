<?php
declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\View;
use App\Models\PublicProperty;

/**
 * Public marketing site. Reuses public/bootstrap.php for session start, DB
 * connect, and the renderHeader()/renderFooter() page-chrome functions -
 * those already behave like a shared layout, so they're called directly
 * around each View rather than reimplemented.
 */
final class SiteController
{
    private \PDO $pdo;

    public function __construct()
    {
        global $pdo;
        require \NURU_ROOT . '/public/bootstrap.php';
        $this->pdo = $pdo;
    }

    public function home(): void
    {
        $featured = (new PublicProperty($this->pdo))->search([], 3);
        \renderHeader('Find your place in Namibia', 'Discover carefully presented homes, land and commercial property with trusted local guidance.', 'home');
        View::render('public.home', ['featured' => $featured]);
        \renderFooter();
    }

    public function about(): void
    {
        \renderHeader('About', 'Nuru brings local knowledge, careful guidance and a human approach to Namibian real estate.', 'about');
        View::render('public.about');
        \renderFooter();
    }

    public function properties(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'type' => in_array($_GET['type'] ?? '', PublicProperty::types(), true) ? $_GET['type'] : '',
            'region' => trim((string) ($_GET['region'] ?? '')),
            'max_price' => filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT) ?: '',
        ];
        $properties = (new PublicProperty($this->pdo))->search($filters);
        try {
            $regions = $this->pdo->query(
                "SELECT DISTINCT sp.property_region
                 FROM seller_properties sp
                 INNER JOIN seller_applications sa ON sa.id = sp.application_id
                 WHERE sp.property_status = 'available'
                   AND sa.status IN ('approved', 'completed')
                 ORDER BY sp.property_region"
            )->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            $regions = [];
        }
        \renderHeader('Properties', 'Browse available homes, land, farms and commercial property across Namibia.', 'properties');
        View::render('public.properties', [
            'filters' => $filters,
            'properties' => $properties,
            'regions' => $regions,
        ]);
        \renderFooter();
    }

    public function propertyDetail(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $model = new PublicProperty($this->pdo);
        $property = $id ? $model->find($id) : null;
        $images = [];
        if ($property) {
            $stmt = $this->pdo->prepare('SELECT file_path, alt_text FROM seller_property_images WHERE propertyId = :id ORDER BY is_primary DESC, image_order, id');
            $stmt->execute([':id' => $id]);
            $images = $stmt->fetchAll();
        }

        if (!$property) {
            http_response_code(404);
            \renderHeader('Property not found', 'The requested property is no longer available.', 'properties');
            View::render('public.property-not-found');
            \renderFooter();
            return;
        }

        $location = trim(implode(', ', array_filter([$property['property_suburb'], $property['property_town'], $property['property_region']])));
        \renderHeader($property['property_detail_type'] . ' in ' . $property['property_town'], 'Available property in ' . $location . ' listed at N$ ' . number_format((float) $property['selling_price'], 0) . '.', 'properties');
        View::render('public.property-detail', [
            'property' => $property,
            'images' => $images,
            'location' => $location,
        ]);
        \renderFooter();
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $base = \publicBaseUrl();
        $urls = [[$base . '/', '1.0'], [$base . '/properties.php', '0.9'], [$base . '/about.php', '0.7'], [$base . '/contact.php', '0.7']];
        foreach ((new PublicProperty($this->pdo))->search() as $property) {
            $urls[] = [$base . '/property.php?id=' . (int) $property['id'], '0.8'];
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        View::render('public.sitemap', ['urls' => $urls]);
    }
}
