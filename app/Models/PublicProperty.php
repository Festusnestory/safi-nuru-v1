<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Public-site property queries, moved as-is from public/bootstrap.php
 * (publicProperties()/publicPropertyById()/propertyTypes()/publicPropertyImage())
 * so the marketing site's SQL/behavior stays identical while going through
 * the same App\Models layer as the admin side.
 */
final class PublicProperty
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function search(array $filters = [], ?int $limit = null): array
    {
        $where = ["sp.property_status = 'available'", "sa.status IN ('approved','completed')"];
        $params = [];
        if (!empty($filters['id'])) {
            $where[] = 'sp.id = :id';
            $params[':id'] = (int) $filters['id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(sp.property_town LIKE :q OR sp.property_suburb LIKE :q OR sp.property_region LIKE :q OR sp.property_detail_type LIKE :q)';
            $params[':q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['type'])) {
            $where[] = 'sp.property_detail_type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['region'])) {
            $where[] = 'sp.property_region = :region';
            $params[':region'] = $filters['region'];
        }
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $where[] = 'sp.selling_price <= :max_price';
            $params[':max_price'] = (float) $filters['max_price'];
        }
        $sql = "SELECT sp.*, sa.application_number, sst.sale_type, sst.property_type,
                (SELECT spi.file_path FROM seller_property_images spi WHERE spi.propertyId = sp.id ORDER BY spi.is_primary DESC, spi.image_order ASC, spi.id ASC LIMIT 1) AS primary_image,
                (SELECT spi.alt_text FROM seller_property_images spi WHERE spi.propertyId = sp.id ORDER BY spi.is_primary DESC, spi.image_order ASC, spi.id ASC LIMIT 1) AS image_alt
            FROM seller_properties sp
            INNER JOIN seller_applications sa ON sa.id = sp.application_id
            LEFT JOIN seller_sale_type sst ON sst.application_id = sp.application_id
            WHERE " . implode(' AND ', $where) . " ORDER BY COALESCE(sp.listing_date, sp.created_at) DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min($limit, 24));
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $error) {
            error_log('Public property query failed: ' . $error->getMessage());
            return [];
        }
    }

    public function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $rows = $this->search(['id' => $id], 1);
        return $rows[0] ?? null;
    }

    public static function types(): array
    {
        return ['Single Residential', 'General Residential', 'Farm', 'Commercial/Business', 'Institutional'];
    }

    public static function imagePath(?string $path): string
    {
        if (!$path) {
            return 'public/assets/images/nuru-hero.png';
        }
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($normalized, '..')) {
            return 'public/assets/images/nuru-hero.png';
        }
        if (str_starts_with($normalized, 'html/material/')) {
            return $normalized;
        }
        if (str_starts_with($normalized, 'uploads/')) {
            return 'html/material/' . $normalized;
        }
        return 'html/material/uploads/seller/property_images/' . basename($normalized);
    }
}
