<?php
require_once __DIR__ . '/../../app/autoload.php'; $__nuruBase = \App\Core\Router::basePath();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $__nuruBase . '/html/material/authentication-login.php');
    exit;
}

require_once __DIR__ . '/config/role_helpers.php';
require_once __DIR__ . '/config/id_tokens.php';
if (!isset($pdo)) {
    require __DIR__ . '/config/pdo.php';
}

$roleHome = [
    'admin' => 'admin.php',
    'manager' => 'dashboard_2.php',
    'agent_coordinator' => 'dashboard_3.php',
    'agent_consultant' => 'dashboard_4.php',
    'buyer' => 'dashboard_1.php',
    'seller' => 'dashboard_5.php',
][currentRole()] ?? 'authentication-login.php';

// Defaults retained for the admin analytics page, which consumes these
// values after including the shared navigation.
$total_buyers = $total_sellers = $total_properties = 0;
$avg_price = 0;
$match_stats = ['total_matches' => 0, 'tier1' => 0, 'tier2' => 0, 'tier3' => 0, 'tier4' => 0, 'last_run' => 'Never'];
$buyers_by_region = $sellers_by_region = $latest_matches = [];
$buyer_pipeline = ['registered' => 0, 'with_income' => 0, 'with_budget' => 0];
$seller_pipeline = ['registered' => 0, 'with_property' => 0, 'available' => 0];
$notifications = ['new_buyers' => 0, 'new_sellers' => 0];

if (currentRole() === 'admin') {
    $total_buyers = (int)$pdo->query('SELECT COUNT(*) FROM buyers')->fetchColumn();
    $total_sellers = (int)$pdo->query('SELECT COUNT(*) FROM seller_applications')->fetchColumn();
    $total_properties = (int)$pdo->query("SELECT COUNT(*) FROM seller_properties WHERE property_status='available'")->fetchColumn();
    $avg_price = (float)$pdo->query('SELECT COALESCE(AVG(selling_price), 0) FROM seller_properties WHERE selling_price > 0')->fetchColumn();
    $buyers_by_region = $pdo->query('SELECT region, COUNT(*) AS c FROM buyers GROUP BY region')->fetchAll(PDO::FETCH_ASSOC);
    $sellers_by_region = $pdo->query('SELECT property_region AS region, COUNT(*) AS c FROM seller_properties GROUP BY property_region')->fetchAll(PDO::FETCH_ASSOC);
    $buyer_pipeline = [
        'registered' => $total_buyers,
        'with_income' => (int)$pdo->query('SELECT COUNT(*) FROM buyers WHERE monthly_income > 0')->fetchColumn(),
        'with_budget' => (int)$pdo->query('SELECT COUNT(*) FROM buyers WHERE down_payment > 0 OR loan_amount > 0')->fetchColumn(),
    ];
    $seller_pipeline = [
        'registered' => $total_sellers,
        'with_property' => (int)$pdo->query('SELECT COUNT(*) FROM seller_properties')->fetchColumn(),
        'available' => $total_properties,
    ];
}

if (isFullAccess()) {
    $notifications['new_buyers'] = (int)$pdo->query('SELECT COUNT(*) FROM buyers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
    $notifications['new_sellers'] = (int)$pdo->query('SELECT COUNT(*) FROM seller_applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
}

$buyers_by_region_json = json_encode($buyers_by_region, JSON_UNESCAPED_SLASHES);
$sellers_by_region_json = json_encode($sellers_by_region, JSON_UNESCAPED_SLASHES);
$latest_matches_json = json_encode($latest_matches, JSON_UNESCAPED_SLASHES);
$buyers_json = $buyers_by_region_json;
$sellers_json = $sellers_by_region_json;
$notificationTotal = $notifications['new_buyers'] + $notifications['new_sellers'];
?>
<script>
window.alert = function (message) {
  let notice = document.getElementById('nuruGlobalNotice');
  if (!notice) {
    notice = document.createElement('div');
    notice.id = 'nuruGlobalNotice';
    notice.setAttribute('role', 'alert');
    notice.setAttribute('aria-live', 'assertive');
    notice.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:2000;max-width:32rem';
    document.body.appendChild(notice);
  }
  notice.className = 'alert alert-info shadow';
  notice.textContent = String(message || '');
  window.clearTimeout(window.nuruNoticeTimer);
  window.nuruNoticeTimer = window.setTimeout(() => { notice.remove(); }, 6000);
};
window.nuruConfirmClick = function (button, label) {
  if (button.dataset.confirmed === 'true') return true;
  button.dataset.confirmed = 'true';
  button.dataset.originalText = button.textContent.trim();
  button.textContent = label || 'Click again to confirm';
  window.alert('Click the same button again to confirm this action.');
  window.setTimeout(() => {
    if (button.dataset.confirmed === 'true') {
      button.dataset.confirmed = 'false';
      button.textContent = button.dataset.originalText;
    }
  }, 5000);
  return false;
};
</script>
<header class="topbar">
  <nav class="navbar top-navbar navbar-expand-md navbar-dark">
    <div class="navbar-header">
      <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)" aria-label="Toggle sidebar"><i class="ti-menu ti-close"></i></a>
      <a class="navbar-brand" href="<?= $__nuruBase ?>/html/material/<?= htmlspecialchars($roleHome) ?>" aria-label="Nuru dashboard">
        <b class="logo-icon"><img src="<?= $__nuruBase ?>/assets/images/logo-light-icon.png" alt="Nuru" class="light-logo" style="width:140px"></b>
      </a>
      <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i class="ti-more"></i></a>
    </div>
    <div class="navbar-collapse collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto"><li class="nav-item"><a class="nav-link sidebartoggler d-none d-md-block waves-effect waves-dark" href="javascript:void(0)" aria-label="Toggle sidebar"><i class="ti-menu"></i></a></li></ul>
      <ul class="navbar-nav align-items-center">
        <?php if (isFullAccess()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle waves-effect waves-dark" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Recent activity"><i class="mdi mdi-bell-outline"></i><?php if ($notificationTotal > 0): ?><span class="badge bg-danger rounded-pill ms-1"><?= $notificationTotal ?></span><?php endif; ?></a>
          <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px">
            <h6 class="dropdown-header px-0">Last 7 days</h6>
            <div class="d-flex justify-content-between py-2"><span>New buyers</span><strong><?= $notifications['new_buyers'] ?></strong></div>
            <div class="d-flex justify-content-between py-2"><span>New seller applications</span><strong><?= $notifications['new_sellers'] ?></strong></div>
          </div>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle waves-effect waves-dark d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu"><img src="<?= $__nuruBase ?>/assets/images/users/profile.png" alt="" width="30" class="profile-pic rounded-circle"><span class="d-none d-lg-inline ms-2"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Account') ?></span></a>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="px-3 py-2 border-bottom"><strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Nuru user') ?></strong><div class="small text-muted"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div></div>
            <a class="dropdown-item" href="<?= $__nuruBase ?>/html/material/change-password.php"><i data-feather="key" class="feather-sm text-warning me-2"></i>Change password</a>
            <a class="dropdown-item" href="<?= $__nuruBase ?>/html/material/config/logout.php"><i data-feather="log-out" class="feather-sm text-danger me-2"></i>Logout</a>
          </div>
        </li>
      </ul>
    </div>
  </nav>
</header>
