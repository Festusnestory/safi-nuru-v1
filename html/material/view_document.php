<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/role_helpers.php';

// Documents contain IDs, proof of income, and other regulated personal
// data. Only operational staff may retrieve them, and responses must not be
// cached or MIME-sniffed by a browser.
if (!sessionHasAuthoritativeRole(['admin', 'manager', 'agent_coordinator'])) {
    http_response_code(403);
    exit('Access denied.');
}

require __DIR__ . '/config/pdo.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$requestedPath = isset($_GET['file']) ? rawurldecode((string)$_GET['file']) : '';
$requestedPath = str_replace('\\', '/', $requestedPath);

if ($requestedPath === '' || str_contains($requestedPath, "\0") || !preg_match('#^uploads/(buyers|seller|agents|agent)/#', $requestedPath)) {
    http_response_code(400);
    exit('Invalid request.');
}

$uploadsRoot = realpath(__DIR__ . '/uploads');
if ($uploadsRoot === false) {
    http_response_code(404);
    exit('File not found.');
}

$relativePath = substr($requestedPath, strlen('uploads/'));
$resolvedPath = realpath($uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
$uploadsPrefix = $uploadsRoot . DIRECTORY_SEPARATOR;

if ($resolvedPath === false || !is_file($resolvedPath) || !str_starts_with($resolvedPath, $uploadsPrefix)) {
    http_response_code(404);
    exit('File not found.');
}

// A coordinator may only retrieve documents belonging to a buyer or seller
// that is explicitly assigned to their agent profile. The page that linked
// the file is not an authorization boundary; direct URL requests must be
// checked independently here.
if (currentRole() === 'agent_coordinator') {
    $agentId = resolveAgentId($pdo, (int)$_SESSION['user_id']);
    $authorized = false;

    if ($agentId !== null && str_starts_with($requestedPath, 'uploads/buyers/')) {
        $buyerStmt = $pdo->prepare('SELECT buyer_id FROM buyer_documents WHERE file_path = :path LIMIT 1');
        $buyerStmt->execute([':path' => $requestedPath]);
        $buyerId = $buyerStmt->fetchColumn();
        if ($buyerId !== false) {
            $ownsBuyer = $pdo->prepare("SELECT 1 FROM buyers b
                WHERE b.id = :buyer_id AND (b.assigned_agent_id = :agent_id OR b.loaded_by = :loaded_by OR EXISTS (
                    SELECT 1 FROM agent_task_allocations ata
                    WHERE ata.agent_id = :task_agent_id AND ata.allocation_type = 'buyer' AND ata.entity_id = b.id
                )) LIMIT 1");
            $ownsBuyer->execute([':buyer_id' => (int)$buyerId, ':agent_id' => $agentId, ':loaded_by' => (int)$_SESSION['user_id'], ':task_agent_id' => $agentId]);
            $authorized = (bool)$ownsBuyer->fetchColumn();
        }
    }

    if ($agentId !== null && str_starts_with($requestedPath, 'uploads/seller/')) {
        $applicationId = null;
        $sellerFileSources = [
            ['seller_documents', 'file_path'],
            ['seller_additional_documents', 'file_path'],
            ['seller_property_images', 'file_path'],
            ['seller_property_images', 'thumbnail_path'],
            ['seller_property_videos', 'file_path'],
            ['seller_property_videos', 'thumbnail_path'],
            ['seller_declarations', 'signature_file_path'],
            ['seller_marital_status', 'marriage_certificate_file'],
        ];
        foreach ($sellerFileSources as [$table, $column]) {
            $fileStmt = $pdo->prepare("SELECT application_id FROM {$table} WHERE {$column} = :path LIMIT 1");
            $fileStmt->execute([':path' => $requestedPath]);
            $found = $fileStmt->fetchColumn();
            if ($found !== false) {
                $applicationId = (int)$found;
                break;
            }
        }
        if ($applicationId !== null) {
            $ownsSeller = $pdo->prepare("SELECT 1 FROM seller_applications sa
                LEFT JOIN seller_personal_details spd ON spd.application_id = sa.id
                WHERE sa.id = :application_id AND (
                    sa.assigned_agent_id = :agent_id OR spd.loaded_by = :loaded_by OR EXISTS (
                        SELECT 1 FROM agent_task_allocations ata
                        WHERE ata.allocation_type = 'seller'
                          AND ata.agent_id = :task_agent_id
                          AND (
                              ata.entity_reference = sa.application_number
                              OR ata.entity_id IN (
                                  SELECT sp.id FROM seller_properties sp WHERE sp.application_id = sa.id
                              )
                          )
                    )
                ) LIMIT 1");
            $ownsSeller->execute([':application_id' => $applicationId, ':agent_id' => $agentId, ':loaded_by' => (int)$_SESSION['user_id'], ':task_agent_id' => $agentId]);
            $authorized = (bool)$ownsSeller->fetchColumn();
        }
    }

    if (!$authorized) {
        http_response_code(403);
        exit('Access denied.');
    }
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($resolvedPath) ?: 'application/octet-stream';
$inlineMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'video/mp4'];
$disposition = in_array($mime, $inlineMimeTypes, true) ? 'inline' : 'attachment';
$filename = basename($resolvedPath);

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($filename, "\\\"") . '"');
header('Content-Length: ' . (string)filesize($resolvedPath));

readfile($resolvedPath);
exit;
