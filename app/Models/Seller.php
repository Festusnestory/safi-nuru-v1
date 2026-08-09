<?php
declare(strict_types=1);

namespace App\Models;

final class Seller
{
    public function __construct(private \PDO $pdo)
    {
    }

    /**
     * Seller applications for the list page. Mirrors the inline query in
     * html/material/sellerlist_table.php exactly: agent_coordinator sees only
     * applications they loaded, are assigned to, or have a task allocation
     * against; everyone else (admin/manager) sees all of them.
     */
    public function listForRole(?string $role, int $userId, int $agentId): array
    {
        $params = [];
        $whereClause = '';

        if ($role === 'agent_coordinator') {
            $whereClause = 'WHERE sp.loaded_by = :loaded_by
                OR sa.assigned_agent_id = :agent_id
                OR EXISTS (
                    SELECT 1 FROM seller_properties seller_property
                    JOIN agent_task_allocations allocation
                      ON allocation.allocation_type = \'seller\' AND allocation.entity_id = seller_property.id
                    WHERE seller_property.application_id = sa.id AND allocation.agent_id = :task_agent_id
                )';
            $params[':loaded_by'] = $userId;
            $params[':agent_id'] = $agentId;
            $params[':task_agent_id'] = $agentId;
        }

        $sql = "
            SELECT
                sp.id,
                sp.application_id,
                sp.application_id AS application_number,
                sa.application_number AS reference_number,
                sa.status AS application_status,
                CONCAT(sp.first_name, ' ', sp.surname) AS full_name,
                sra.email,
                sra.mobile_number AS phone,
                sra.region,
                sra.town,
                sp.created_at
            FROM seller_personal_details sp
            INNER JOIN seller_residential_address sra
                ON sra.application_id = sp.application_id
            INNER JOIN seller_applications sa
                ON sa.id = sp.application_id
            $whereClause
            ORDER BY sp.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function fetchRow(string $sql, array $params): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    private function fetchAllRows(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Bare seller_applications row, used both for the profile page and the review flow. */
    public function findApplication(int $id): ?array
    {
        return $this->fetchRow('SELECT * FROM seller_applications WHERE id = :id', ['id' => $id]);
    }

    /**
     * True when an agent_coordinator is allowed to view this application:
     * assigned directly, has a task allocation against one of its properties,
     * or loaded the personal details themselves. Mirrors the access check
     * inline in html/material/sellers_profile.php exactly (same principle as
     * checklist.php per that file's comment).
     */
    public function isVisibleToAgent(int $applicationId, ?int $assignedAgentId, ?int $agentId, int $userId): bool
    {
        // Mirrors the original's `?? -1` sentinel exactly: a null agent id
        // must never accidentally match a null/zero assigned_agent_id.
        if ((int) ($assignedAgentId ?? 0) === (int) ($agentId ?? -1)) {
            return true;
        }

        $hasAssignedTask = (bool) $this->fetchScalar(
            "SELECT 1 FROM seller_properties sp
                JOIN agent_task_allocations ata ON ata.allocation_type = 'seller' AND ata.entity_id = sp.id
                WHERE sp.application_id = ? AND ata.agent_id = ? LIMIT 1",
            [$applicationId, $agentId ?? 0]
        );
        if ($hasAssignedTask) {
            return true;
        }

        return (bool) $this->fetchScalar(
            'SELECT 1 FROM seller_personal_details WHERE application_id = ? AND loaded_by = ? LIMIT 1',
            [$applicationId, $userId]
        );
    }

    private function fetchScalar(string $sql, array $params): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Full nested application profile shown on sellers_profile.php: personal
     * details, marital status, next of kin, residential address,
     * declarations, sale type, properties (with images/videos), development
     * breakdown (with live remaining-unit counts), documents and additional
     * documents. Field-for-field copy of the original page's assembly logic.
     */
    public function profile(int $sellerId, array $applicationData): array
    {
        $personal = $this->fetchRow('SELECT * FROM seller_personal_details WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $address = $this->fetchRow('SELECT * FROM seller_residential_address WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $marital = $this->fetchRow('SELECT * FROM seller_marital_status WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $nok = $this->fetchRow('SELECT * FROM seller_next_of_kin WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $declarations = $this->fetchRow('SELECT * FROM seller_declarations WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $saleType = $this->fetchRow('SELECT * FROM seller_sale_type WHERE application_id = :id', ['id' => $sellerId]) ?: [];
        $properties = $this->fetchAllRows('SELECT * FROM seller_properties WHERE application_id = :id', ['id' => $sellerId]);

        // Development Breakdown (Property Development sale type only) -
        // remaining stock per house type is computed live from currently
        // matched/sold generated units, not a stored counter.
        $developments = $this->fetchAllRows(
            'SELECT * FROM seller_developments WHERE application_id = :id ORDER BY development_index',
            ['id' => $sellerId]
        );
        foreach ($developments as &$development) {
            $development['house_types'] = $this->fetchAllRows("
                SELECT ht.*,
                       ht.number_of_units - COALESCE((
                           SELECT COUNT(*) FROM seller_properties sp
                           WHERE sp.development_house_type_id = ht.id
                             AND sp.property_status IN ('under_offer','sold')
                       ), 0) AS units_remaining
                FROM seller_development_house_types ht
                WHERE ht.development_id = :development_id
                ORDER BY ht.house_type_index
            ", ['development_id' => $development['id']]);
        }
        unset($development);

        $application = [
            'application_id' => $applicationData['id'],
            'application_number' => $applicationData['application_number'],
            'status' => $applicationData['status'],
            'submission_date' => $applicationData['submission_date'],
            'review_date' => $applicationData['review_date'],
            'approved_date' => $applicationData['approved_date'],
            'rejection_reason' => $applicationData['rejection_reason'],
            'application_created_at' => $applicationData['created_at'],
            'application_updated_at' => $applicationData['updated_at'],

            'personal_details' => [
                'surname' => $personal['surname'] ?? '',
                'first_name' => $personal['first_name'] ?? '',
                'middle_name' => $personal['middle_name'] ?? '',
                'maiden_name' => $personal['maiden_name'] ?? '',
                'date_of_birth' => $personal['date_of_birth'] ?? '',
                'age' => $personal['age'] ?? '',
                'id_type' => $personal['id_type'] ?? '',
                'id_number' => $personal['id_number'] ?? '',
                'nationality' => $personal['nationality'] ?? '',
                'gender' => $personal['gender'] ?? '',
                'loaded_by' => $personal['loaded_by'] ?? '',
            ],

            'marital_status' => [
                'marital_status' => $marital['marital_status'] ?? '',
                'spouse_surname' => $marital['spouse_surname'] ?? '',
                'spouse_first_name' => $marital['spouse_first_name'] ?? '',
                'spouse_date_of_birth' => $marital['spouse_date_of_birth'] ?? '',
                'spouse_id_type' => $marital['spouse_id_type'] ?? '',
                'spouse_id_number' => $marital['spouse_id_number'] ?? '',
                'spouse_nationality' => $marital['spouse_nationality'] ?? '',
            ],

            'next_of_kin' => [
                'nok_surname' => $nok['nok_surname'] ?? '',
                'nok_first_name' => $nok['nok_first_name'] ?? '',
                'nok_contact_number' => $nok['nok_contact_number'] ?? '',
                'nok_email' => $nok['nok_email'] ?? '',
                'nok_erf_no' => $nok['nok_erf_no'] ?? '',
                'nok_street_name' => $nok['nok_street_name'] ?? '',
                'nok_suburb' => $nok['nok_suburb'] ?? '',
                'nok_location' => $nok['nok_location'] ?? '',
                'nok_region' => $nok['nok_region'] ?? '',
                'nok_town' => $nok['nok_town'] ?? '',
            ],

            'residential_address' => [
                'erf_no' => $address['erf_no'] ?? '',
                'street' => $address['street_name'] ?? '',
                'suburb' => $address['suburb'] ?? '',
                'location' => $address['location'] ?? '',
                'region' => $address['region'] ?? '',
                'town' => $address['town'] ?? '',
                'email' => $address['email'] ?? '',
                'mobile_number' => $address['mobile_number'] ?? '',
                'po_box' => $address['po_box'] ?? '',
            ],

            'declarations' => [
                'certification_declaration' => $declarations['certification_declaration'] ?? 0,
                'authorization_declaration' => $declarations['authorization_declaration'] ?? 0,
                'indemnification_declaration' => $declarations['indemnification_declaration'] ?? 0,
                'commission_fees_declaration' => $declarations['commission_fees_declaration'] ?? 0,
                'property_rights_declaration' => $declarations['property_rights_declaration'] ?? 0,
                'signature_location' => $declarations['signature_location'] ?? '',
                'signature_date' => $declarations['signature_date'] ?? '',
                'signature_type' => $declarations['signature_type'] ?? '',
                'signature_file_path' => $declarations['signature_file_path'] ?? '',
                'otp_verified_at' => $declarations['otp_verified_at'] ?? null,
            ],

            'sale_type' => [
                'sale_type' => $saleType['sale_type'] ?? '',
                'developer_name' => $saleType['developer_name'] ?? '',
                'property_type' => $saleType['property_type'] ?? '',
            ],

            'properties' => [],
            'documents' => [],
            'additional_documents' => [],
            'developments' => $developments,
        ];

        foreach ($properties as $prop) {
            $propId = $prop['id'];
            $images = $this->fetchAllRows('SELECT * FROM seller_property_images WHERE propertyId = :prop_id', ['prop_id' => $propId]);
            $videos = $this->fetchAllRows('SELECT * FROM seller_property_videos WHERE propertyId = :prop_id', ['prop_id' => $propId]);

            $application['properties'][] = [
                'property_id' => $propId,
                'property_detail_type' => $prop['property_detail_type'] ?? '',
                'land_type' => $prop['land_type'] ?? '',
                'land_size' => $prop['land_size'] ?? '',
                'selling_price' => $prop['selling_price'] ?? '',
                'property_street_name' => $prop['property_street_name'] ?? '',
                'images' => $images,
                'videos' => $videos,
            ];
        }

        $docs = $this->fetchAllRows('SELECT * FROM seller_documents WHERE application_id = :id', ['id' => $sellerId]);
        foreach ($docs as $doc) {
            $application['documents'][] = [
                'document_type' => $doc['document_type'] ?? '',
                'file_path' => $doc['file_path'] ?? '',
                'original_filename' => $doc['original_filename'] ?? '',
                'is_verified' => $doc['is_verified'] ?? 0,
            ];
        }

        $addDocs = $this->fetchAllRows('SELECT * FROM seller_additional_documents WHERE application_id = :id', ['id' => $sellerId]);
        foreach ($addDocs as $doc) {
            $application['additional_documents'][] = [
                'document_name' => $doc['document_name'] ?? '',
                'file_path' => $doc['file_path'] ?? '',
                'original_filename' => $doc['original_filename'] ?? '',
            ];
        }

        return $application;
    }

    /** Locks the application row for the approve/reject decision. Must run inside a transaction. */
    public function lockApplicationForReview(int $id): ?array
    {
        return $this->fetchRow(
            'SELECT id, application_number, status, user_id FROM seller_applications WHERE id = :id FOR UPDATE',
            ['id' => $id]
        );
    }

    public function approve(int $id): void
    {
        $this->pdo->prepare("UPDATE seller_applications SET status = 'approved', review_date = NOW(), approved_date = NOW(), rejection_reason = NULL WHERE id = :id")
            ->execute([':id' => $id]);
        $this->pdo->prepare("UPDATE seller_properties SET property_status = 'available' WHERE application_id = :id AND property_status = 'pending_review'")
            ->execute([':id' => $id]);
    }

    public function reject(int $id, string $reason): void
    {
        $this->pdo->prepare("UPDATE seller_applications SET status = 'rejected', review_date = NOW(), rejection_reason = :reason WHERE id = :id")
            ->execute([':reason' => $reason, ':id' => $id]);
        $this->pdo->prepare("UPDATE seller_properties SET property_status = 'withdrawn', status_deadline = NULL WHERE application_id = :id AND property_status IN ('pending_review', 'available')")
            ->execute([':id' => $id]);
    }

    public function setLinkedUserActive(int $userId, int $isActive): void
    {
        $this->pdo->prepare("UPDATE admin_users SET is_active = :active WHERE id = :id AND role = 'seller'")
            ->execute([':active' => $isActive, ':id' => $userId]);
    }
}
