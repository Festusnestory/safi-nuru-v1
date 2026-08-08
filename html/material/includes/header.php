<?php
/**
 * Minimal Bootstrap 5 chrome for the self-service application portal pages
 * (agent/, buyer/, seller/), which use Bootstrap 5 rather than the main
 * admin theme's bundled Bootstrap 4 assets.
 */
$portalUser = class_exists('PortalAuth') ? PortalAuth::getCurrentUser() : null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="../admin.php">Nuru Real Estate Portal</a>
        <div class="d-flex align-items-center text-light">
            <?php if ($portalUser): ?>
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($portalUser['full_name'] ?: $portalUser['email']) ?></span>
                <a class="btn btn-outline-light btn-sm" href="../config/logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
